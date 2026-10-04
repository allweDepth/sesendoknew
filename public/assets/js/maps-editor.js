(function () {
  "use strict";

  window.createMapsShapeEditor = function ({ page, map, loadLayer, getRow, onSaved, clearSelection }) {
    const panel = page.querySelector("#mapsShapeEditor");
    if (!panel) return null;
    const el = (id) => page.querySelector(`#${id}`);
    const preview = L.featureGroup();
    const handles = L.featureGroup();
    const draft = L.featureGroup();
    let active = false;
    let busy = false;
    let editEnabled = true;
    let source = null;
    let sourceEntry = null;
    let features = [];
    let fields = [];
    let selected = -1;
    let drawing = false;
    let points = [];
    let opening = false;
    let selectedNode = null;
    let continuation = null;
    let cutMode = false;
    let history = [];
    function updateEditMode() {
      const allowed = new Set(["mapsEditorClose", "mapsEditorDiscard", "mapsEditorSave", "mapsEditOn", "mapsEditOff"]);
      panel.querySelectorAll("input,select,button").forEach((control) => {
        if (allowed.has(control.id)) return;
        if (!editEnabled) {
          if (control.dataset.editDisabled == null) control.dataset.editDisabled = control.disabled ? "1" : "0";
          control.disabled = true;
        } else if (control.dataset.editDisabled != null) {
          control.disabled = control.dataset.editDisabled === "1";
          delete control.dataset.editDisabled;
        }
      });
      el("mapsEditOn").checked = editEnabled; el("mapsEditOff").checked = !editEnabled;
      if (window.jQuery?.fn.checkbox) {
        window.jQuery(el("mapsEditOn").parentElement).checkbox(editEnabled ? "set checked" : "set unchecked");
        window.jQuery(el("mapsEditOff").parentElement).checkbox(editEnabled ? "set unchecked" : "set checked");
      }
      el("mapsEditorModeStatus").textContent = editEnabled ? "Aktif edit: perubahan masih sementara. Pilih Simpan edit atau Tidak simpan." : "Off edit: hanya lihat. Perubahan sementara belum disimpan; pilih Simpan edit atau Tidak simpan.";
      el("mapsEditorDeleteFeature").disabled = !editEnabled || selected < 0;
      el("mapsEditorUndoEdit").disabled = !editEnabled || !history.length;
      el("mapsEditorGeometry").disabled = !editEnabled || features.length > 0 || drawing;
      renderHandles();
    }
    function remember() {
      history.push({ features: clone(features), fields: clone(fields), selected });
      if (history.length > 10) history.shift();
      el("mapsEditorUndoEdit").disabled = false;
    }
    const error = (message = "") => {
      el("mapsEditorError").textContent = message;
      el("mapsEditorError").classList.toggle("hidden", !message);
    };
    const hint = (message) => { el("mapsEditorHint").textContent = message; };
    const clone = (value) => JSON.parse(JSON.stringify(value));
    const family = (type) => type.includes("Polygon") ? "Polygon" : type.includes("Line") ? "LineString" : type;

    function refreshPreview() {
      preview.clearLayers();
      features.forEach((feature, index) => {
        const group = L.geoJSON(feature, {
          style: { color: index === selected ? "#f59e0b" : "#2185d0", weight: index === selected ? 4 : 2, fillOpacity: .25 },
          pointToLayer: (_, latlng) => L.circleMarker(latlng, { radius: 7, color: index === selected ? "#f59e0b" : "#2185d0", weight: 3, fillOpacity: .5 }),
          onEachFeature: (_, layer) => {
            layer.options.bubblingMouseEvents = false;
            layer.on("click", () => { if (!drawing && !busy) selectFeature(index); });
          }
        });
        preview.addLayer(group);
      });
      const operand = el("mapsEditorOperand");
      const value = operand.value;
      operand.replaceChildren(new Option("Pilih poligon lain", ""));
      features.forEach((feature, index) => { if (index !== selected && feature.geometry.type.includes("Polygon")) operand.add(new Option(`Feature ${index + 1} · ${feature.properties.NAMA || feature.properties.Nama || feature.properties.ID || ''}`, String(index))); });
      operand.value = value;
      el("mapsEditorCount").textContent = `${features.length} feature`;
      el("mapsEditorDeleteFeature").disabled = selected < 0 || busy || !editEnabled;
    }

    // Return coordinate arrays with their path, retaining multipart boundaries and holes.
    function coordinateParts(value, path = [], result = []) {
      if (!Array.isArray(value)) return result;
      if (typeof value[0] === "number") result.push({ points: [value], path, single: true });
      else if (Array.isArray(value[0]) && typeof value[0][0] === "number") result.push({ points: value, path, single: false });
      else value.forEach((part, index) => coordinateParts(part, [...path, index], result));
      return result;
    }
    function atPath(value, path) { return path.reduce((part, key) => part[key], value); }

    function renderHandles() {
      handles.clearLayers();
      if (selected < 0 || drawing || !editEnabled) return;
      const geometry = features[selected].geometry;
      coordinateParts(geometry.coordinates).forEach((part) => {
        const closed = geometry.type.includes("Polygon");
        const count = part.points.length - (closed ? 1 : 0);
        for (let i = 0; i < count; i++) {
          const point = part.points[i];
          const marker = L.marker([point[1], point[0]], {
            draggable: true, bubblingMouseEvents: false,
            icon: L.divIcon({ className: "maps-vertex", iconSize: [12, 12], iconAnchor: [6, 6] }),
            title: `Vertex ${i + 1}: geser untuk mengubah; klik kanan untuk menghapus`
          });
          marker.on("click", () => {
            selectedNode = { path: part.path, index: i };
            hint(`Node ${i + 1} dipilih. Divide garis akan memotong di node ini; Lanjut awal/akhir melanjutkan bagian garis ini.`);
          });
          marker.on("dragstart", remember);
          marker.on("drag", () => {
            if (busy || !editEnabled) return;
            const position = marker.getLatLng();
            point[0] = ((position.lng + 180) % 360 + 360) % 360 - 180;
            point[1] = Math.max(-90, Math.min(90, position.lat));
            if (closed && i === 0) part.points[part.points.length - 1] = [...point];
            refreshPreview();
          });
          marker.on("dragend", renderHandles);
          marker.on("contextmenu", (event) => {
            L.DomEvent.preventDefault(event.originalEvent);
            if (busy || !editEnabled) return;
            if (part.single || part.points.length <= (closed ? 4 : geometry.type === "MultiPoint" ? 1 : 2)) {
              hint("Vertex minimum geometri tidak dapat dihapus. Gunakan Hapus feature untuk menghapus seluruh objek.");
              return;
            }
            remember();
            part.points.splice(i, 1);
            if (closed) part.points[part.points.length - 1] = [...part.points[0]];
            refreshPreview(); renderHandles();
          });
          handles.addLayer(marker);
          if (!part.single && geometry.type !== "MultiPoint" && i < part.points.length - 1) {
            const next = part.points[i + 1];
            const midpoint = L.marker([(point[1] + next[1]) / 2, (point[0] + next[0]) / 2], {
              bubblingMouseEvents: false,
              icon: L.divIcon({ className: "maps-midpoint", iconSize: [8, 8], iconAnchor: [4, 4] }),
              title: "Klik untuk menambah vertex"
            });
            midpoint.on("click", () => {
              if (busy || !editEnabled) return;
              const position = midpoint.getLatLng();
              remember();
              atPath(geometry.coordinates, part.path).splice(i + 1, 0, [position.lng, position.lat]);
              refreshPreview(); renderHandles();
            });
            handles.addLayer(midpoint);
          }
        }
      });
    }

    function renderAttributes() {
      const target = el("mapsEditorAttributes");
      target.replaceChildren();
      if (selected < 0) { target.textContent = "Pilih feature pada peta untuk mengedit atribut."; return; }
      const feature = features[selected];
      fields.forEach((field) => {
        const wrapper = document.createElement("div"); wrapper.className = "field";
        const label = document.createElement("label"); label.textContent = field.name;
        const input = document.createElement(field.type === "L" ? "select" : "input");
        input.id = `mapsAttribute-${field.name}`; label.htmlFor = input.id;
        const value = feature.properties[field.name];
        if (field.type === "L") {
          [["", "Kosong"], ["true", "Ya"], ["false", "Tidak"]].forEach(([v, text]) => input.add(new Option(text, v)));
          input.value = value == null ? "" : String(value);
        } else {
          input.type = field.type === "N" ? "number" : field.type === "D" ? "date" : "text";
          if (field.type === "N") input.step = String(10 ** -(field.decimals || 0));
          input.value = value == null ? "" : field.type === "D" ? String(value).replace(/^(\d{4})(\d{2})(\d{2})$/, "$1-$2-$3") : String(value);
        }
        input.addEventListener("input", () => {
          if (busy || !editEnabled) return;
          feature.properties[field.name] = input.value === "" ? null : field.type === "N" ? Number(input.value) : field.type === "L" ? input.value === "true" : input.value;
        });
        input.addEventListener("change", () => {
          if (field.type === "L" && !busy && editEnabled) feature.properties[field.name] = input.value === "" ? null : input.value === "true";
        });
        wrapper.append(label, input); target.append(wrapper);
      });
    }

    function selectFeature(index) {
      selected = index; selectedNode = null;
      refreshPreview(); renderHandles(); renderAttributes();
      if (!editEnabled) updateEditMode();
      hint("Geser vertex putih untuk mengedit. Klik titik kecil di tengah sisi untuk menambah vertex; klik kanan vertex untuk menghapus.");
    }

    function addFieldRow(field = { name: "", type: "C", width: 80, decimals: 0 }, original = field.name) {
      const row = document.createElement("tr"); row.dataset.original = original;
      ["name", "type", "width", "decimals"].forEach((key) => {
        const td = document.createElement("td");
        const input = document.createElement(key === "type" ? "select" : "input");
        input.dataset.key = key; input.setAttribute("aria-label", `${key === "name" ? "Nama" : key === "type" ? "Tipe" : key === "width" ? "Panjang" : "Desimal"} field`);
        if (key === "type") [["C", "Teks"], ["N", "Angka"], ["L", "Boolean"], ["D", "Tanggal"]].forEach(([v, text]) => input.add(new Option(text, v)));
        else { input.type = key === "name" ? "text" : "number"; if (key === "name") input.maxLength = 10; else { input.min = key === "width" ? "1" : "0"; input.max = key === "width" ? "254" : "8"; input.step = "1"; } }
        input.value = String(field[key]); td.append(input); row.append(td);
      });
      const td = document.createElement("td"); const remove = document.createElement("button");
      remove.className = "ui mini basic icon button"; remove.type = "button"; remove.setAttribute("aria-label", "Hapus field"); remove.innerHTML = '<i class="trash icon"></i>';
      remove.addEventListener("click", () => { if (!busy) row.remove(); }); td.append(remove); row.append(td);
      el("mapsEditorFields").append(row);
    }

    function applyFields() {
      const definitions = [...el("mapsEditorFields").children].map((row) => {
        const values = Object.fromEntries([...row.querySelectorAll("[data-key]")].map((input) => [input.dataset.key, input.value]));
        return { name: values.name.trim(), type: values.type, width: Number(values.width), decimals: Number(values.decimals), original: row.dataset.original };
      });
      const unique = new Set();
      if (!definitions.length || definitions.length > 64) throw new Error("Tentukan 1–64 field.");
      definitions.forEach((field) => {
        if (!/^[A-Za-z_][A-Za-z0-9_]{0,9}$/.test(field.name) || unique.has(field.name.toUpperCase())) throw new Error("Nama field harus unik dan maksimal 10 karakter (huruf, angka, underscore).");
        unique.add(field.name.toUpperCase());
        if (field.type === "L") { field.width = 1; field.decimals = 0; }
        else if (field.type === "D") { field.width = 8; field.decimals = 0; }
        else if (!Number.isInteger(field.width) || field.width < 1 || field.width > (field.type === "C" ? 254 : 20)
          || !Number.isInteger(field.decimals) || field.decimals < 0 || field.decimals > 8 || (field.type === "N" && field.decimals > field.width - 2)) throw new Error("Panjang/desimal field tidak valid.");
        if (field.type === "C") field.decimals = 0;
      });
      features.forEach((feature) => {
        feature.properties = Object.fromEntries(definitions.map((field) => [field.name, feature.properties[field.original] ?? null]));
      });
      fields = definitions.map(({ original, ...field }) => field);
      el("mapsEditorFields").replaceChildren(); fields.forEach((field) => addFieldRow(field));
      const divideSelect = el("mapsEditorDivideField");
      const divideValue = divideSelect.value;
      divideSelect.replaceChildren(new Option("Pilih field", "")); fields.forEach((field) => divideSelect.add(new Option(field.name, field.name)));
      divideSelect.value = divideValue;
      renderAttributes();
    }

    function stopDrawing() {
      drawing = false; points = []; continuation = null; cutMode = false; draft.clearLayers();
      el("mapsEditorFinish").disabled = true; el("mapsEditorUndo").disabled = true;
      el("mapsEditorDraw").disabled = false; el("mapsEditorCancelDraw").hidden = true;
      map.getContainer().classList.remove("maps-drawing");
      map.doubleClickZoom.enable();
      el("mapsEditorGeometry").disabled = features.length > 0;
    }

    function showDraft() {
      draft.clearLayers();
      points.forEach(([lng, lat]) => draft.addLayer(L.circleMarker([lat, lng], { radius: 4, color: "#f59e0b", interactive: false })));
      if (points.length > 1) draft.addLayer(L.polyline(points.map(([lng, lat]) => [lat, lng]), { color: "#f59e0b", dashArray: "5 5", interactive: false }));
      el("mapsEditorUndo").disabled = points.length <= (continuation ? 1 : 0);
    }

    function finishDrawing() {
      if (continuation) {
        if (points.length < 2) { hint("Klik minimal satu titik baru untuk melanjutkan garis."); return; }
        remember();
        const index = continuation.index;
        features[index] = window.MapsGeometry.continueLine(features[index], continuation.path, continuation.atStart, points.slice(1));
        stopDrawing(); selectFeature(index); return;
      }
      if (cutMode) { hint("Klik titik kedua garis pemotong pada peta."); return; }
      const type = el("mapsEditorGeometry").value;
      const minimum = type === "Polygon" ? 3 : type === "LineString" ? 2 : 1;
      if (points.length < minimum) { hint(`Buat minimal ${minimum} titik sebelum selesai.`); return; }
      const coordinates = type === "Point" ? points[0] : type === "MultiPoint" ? clone(points) : type === "Polygon" ? [[...clone(points), [...points[0]]]] : clone(points);
      const props = Object.fromEntries(fields.map((field) => [field.name, field.name.toUpperCase() === "ID" && field.type === "N" ? features.length + 1 : null]));
      remember();
      features.push({ type: "Feature", geometry: { type, coordinates }, properties: props });
      stopDrawing(); selectFeature(features.length - 1);
    }

    function close(force = false) {
      if (busy) return;
      if (active && !force && !window.confirm("Tutup editor dan abaikan perubahan SHP yang belum disimpan?")) return;
      stopDrawing(); preview.remove(); handles.remove(); draft.remove();
      if (sourceEntry) sourceEntry.layer.addTo(map);
      active = false; panel.hidden = true; sourceEntry = null; source = null;
      clearSelection();
    }

    async function open(id = null, feature = null) {
      if (opening || busy) return;
      opening = true;
      try {
        if (active) { close(); if (active) return; }
        let row = null; let entry = null;
        if (id != null) {
          row = getRow(id); entry = await loadLayer(id);
          if (!row) throw new Error("Layer tidak ditemukan.");
          if (!entry.editable) throw new Error("Editor mendukung SHP 2D. Layer Z/M atau null-shape perlu diekspor menjadi SHP 2D sebelum diedit.");
        }
        editEnabled = true; updateEditMode();
        clearSelection(); source = row; sourceEntry = entry;
        features = entry ? clone(entry.geoJson.features) : [];
        fields = entry?.fields.length ? entry.fields.map((f) => ({ name: f.name, type: "NFI".includes(f.type) ? "N" : ["L", "D"].includes(f.type) ? f.type : "C", width: f.width, decimals: f.decimals || 0 })) : [{ name: "ID", type: "N", width: 10, decimals: 0 }, { name: "NAMA", type: "C", width: 80, decimals: 0 }];
        selected = entry && feature ? entry.geoJson.features.indexOf(feature) : -1;
        el("mapsEditorName").value = row?.nama_layer || "";
        el("mapsEditorTitle").textContent = row ? "Edit SHP" : "Gambar SHP";
        const type = features.length ? family(features[0].geometry.type) : "Point";
        const geometrySelect = el("mapsEditorGeometry");
        geometrySelect.querySelector('option[value="MultiPoint"]')?.remove();
        if (type === "MultiPoint") geometrySelect.add(new Option("MultiPoint", "MultiPoint"));
        geometrySelect.value = type; geometrySelect.disabled = features.length > 0;
        el("mapsEditorFields").replaceChildren(); fields.forEach((field) => addFieldRow(field));
        if (entry) entry.layer.remove();
        preview.addTo(map); handles.addTo(map); draft.addTo(map);
        history = []; el("mapsEditorUndoEdit").disabled = true;
        const divideSelect = el("mapsEditorDivideField"); divideSelect.replaceChildren(new Option("Pilih field", "")); fields.forEach((field) => divideSelect.add(new Option(field.name, field.name)));
        active = true; panel.hidden = false; panel.scrollTop = 0; error();
        el("mapsEditorSave").innerHTML = row ? '<i class="save icon"></i>Simpan edit' : '<i class="save icon"></i>Simpan SHP ke OPD';
        updateEditMode();
        refreshPreview(); renderHandles(); renderAttributes();
        el("mapsEditorDivideStatus").textContent = "";
        hint("Klik Gambar feature untuk menambah objek, atau pilih feature yang ada untuk mengedit geometri dan atribut.");
        if (entry?.layer.getBounds().isValid()) map.fitBounds(entry.layer.getBounds(), { padding: [40, 40], maxZoom: 16 });
      } catch (e) { error(e.message); panel.hidden = false; }
      finally { opening = false; }
    }

    el("mapsNewShape")?.addEventListener("click", () => open());
    el("mapsEditorClose").addEventListener("click", () => close());
    el("mapsEditorDiscard").addEventListener("click", () => close(true));
    ["mapsEditOn", "mapsEditOff"].forEach((id) => el(id).addEventListener("change", () => {
      if (busy) return;
      if (drawing) stopDrawing();
      editEnabled = el("mapsEditOn").checked; updateEditMode();
    }));
    el("mapsEditorAddField").addEventListener("click", () => { if (!busy && editEnabled) addFieldRow(); });
    el("mapsEditorApplyFields").addEventListener("click", () => { try { remember(); applyFields(); error(); } catch (e) { error(e.message); } });
    el("mapsEditorDraw").addEventListener("click", () => {
      try {
        applyFields(); error(); selected = -1; drawing = true; points = []; handles.clearLayers();
        refreshPreview(); renderAttributes(); map.doubleClickZoom.disable();
        map.getContainer().classList.add("maps-drawing");
        el("mapsEditorFinish").disabled = false; el("mapsEditorDraw").disabled = true; el("mapsEditorCancelDraw").hidden = false;
        hint("Klik peta untuk menambahkan titik. Untuk garis/poligon, klik Selesai setelah titik terakhir.");
      } catch (e) { error(e.message); }
    });
    el("mapsEditorFinish").addEventListener("click", finishDrawing);
    el("mapsEditorUndo").addEventListener("click", () => { points.pop(); showDraft(); });
    el("mapsEditorDeleteFeature").addEventListener("click", () => {
      if (selected < 0 || busy || !editEnabled) return;
      remember();
      features.splice(selected, 1); selected = -1; refreshPreview(); renderHandles(); renderAttributes();
      el("mapsEditorGeometry").disabled = features.length > 0;
    });
    async function save(divideField = "") {
      if (busy) return;
      try {
        if (drawing) throw new Error("Klik Selesai untuk menyelesaikan geometri sebelum menyimpan.");
        if (!features.length) throw new Error("Gambar minimal satu feature.");
        const invalid = [...panel.querySelectorAll("input")].find((input) => !input.checkValidity());
        if (invalid) { invalid.reportValidity(); return; }
        applyFields();
        const name = el("mapsEditorName").value.trim();
        if (!name) throw new Error("Isi nama layer terlebih dahulu.");
        busy = true; error();
        panel.querySelectorAll("input,select,button").forEach((control) => { control.dataset.wasDisabled = control.disabled ? "1" : "0"; control.disabled = true; });
        el("mapsEditorSave").classList.add("loading");
        // Freeze vertices while the snapshot is being saved.
        handles.eachLayer((marker) => marker.dragging?.disable());
        const body = new URLSearchParams({ id: String(source?.id || 0), revision: source?.revision || "", divide_field: divideField, nama_layer: name, fields: JSON.stringify(fields), geojson: JSON.stringify({ type: "FeatureCollection", features }), _csrf: window.CSRF_TOKEN || "" });
        const response = await fetch("/maps/geometry", { method: "POST", credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest" }, body });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || "SHP gagal disimpan.");
        busy = false; close(true);
        await onSaved(data.data.id, data.data.ids || []);
      } catch (e) { error(e.message); }
      finally {
        busy = false; el("mapsEditorSave").classList.remove("loading");
        panel.querySelectorAll("input,select,button").forEach((control) => { if (control.dataset.wasDisabled != null) { control.disabled = control.dataset.wasDisabled === "1"; delete control.dataset.wasDisabled; } });
        if (active) { renderHandles(); updateEditMode(); }
      }
    }
    el("mapsEditorSave").addEventListener("click", () => save());
    function requireSelected() {
      if (selected < 0 || !features[selected]) throw new Error("Pilih feature yang akan diedit pada peta.");
      if (drawing) throw new Error("Selesaikan atau batalkan gambar terlebih dahulu.");
      return features[selected];
    }
    function replaceSelected(output, removeIndex = null) {
      remember();
      const index = selected;
      features.splice(index, 1, ...output);
      if (removeIndex != null) features.splice(removeIndex > index ? removeIndex + output.length - 1 : removeIndex, 1);
      selectFeature(Math.min(index - (removeIndex != null && removeIndex < index ? 1 : 0), features.length - 1));
      error();
    }
    function runOperation(action) {
      if (busy || !editEnabled) return;
      try { action(); error(); } catch (e) { error(e.message); }
    }
    function continueSelected(atStart) {
      const feature = requireSelected();
      if (!["LineString", "MultiLineString"].includes(feature.geometry.type)) throw new Error("Pilih feature garis untuk dilanjutkan.");
      if (feature.geometry.type === "MultiLineString" && !selectedNode) throw new Error("Klik node pada bagian garis yang ingin dilanjutkan.");
      const path = feature.geometry.type === "LineString" ? [] : selectedNode.path;
      const line = atPath(feature.geometry.coordinates, path);
      continuation = { index: selected, path, atStart };
      points = [clone(atStart ? line[0] : line[line.length - 1])];
      drawing = true; cutMode = false; handles.clearLayers(); map.doubleClickZoom.disable();
      el("mapsEditorFinish").disabled = false; el("mapsEditorDraw").disabled = true; el("mapsEditorCancelDraw").hidden = false;
      map.getContainer().classList.add("maps-drawing"); showDraft();
      hint(`Melanjutkan ${atStart ? 'awal' : 'akhir'} garis: klik titik baru lalu Selesai.`);
    }
    el("mapsEditorContinueStart").addEventListener("click", () => runOperation(() => continueSelected(true)));
    el("mapsEditorContinueEnd").addEventListener("click", () => runOperation(() => continueSelected(false)));
    el("mapsEditorSplitLine").addEventListener("click", () => runOperation(() => {
      const feature = requireSelected();
      if (!selectedNode) throw new Error("Klik node tengah tempat garis akan dibagi. Tambahkan node melalui titik tengah sisi jika diperlukan.");
      replaceSelected(window.MapsGeometry.splitLine(feature, selectedNode.path, selectedNode.index));
    }));
    el("mapsEditorSplitPolygon").addEventListener("click", () => runOperation(() => {
      if (!requireSelected().geometry.type.includes("Polygon")) throw new Error("Pilih feature poligon.");
      cutMode = true; drawing = true; points = []; handles.clearLayers(); map.doubleClickZoom.disable();
      el("mapsEditorDraw").disabled = true; el("mapsEditorCancelDraw").hidden = false;
      map.getContainer().classList.add("maps-drawing");
      hint("Klik dua titik untuk garis pemotong lurus. Garis diperpanjang melewati seluruh poligon; pemotongan berlangsung setelah klik kedua.");
    }));
    el("mapsEditorExplode").addEventListener("click", () => runOperation(() => replaceSelected(window.MapsGeometry.explodeFeature(requireSelected()))));
    el("mapsEditorApplyBoolean").addEventListener("click", () => runOperation(() => {
      const feature = requireSelected();
      const value = el("mapsEditorOperand").value;
      const index = Number(value);
      if (value === "" || index === selected || !features[index]) throw new Error("Pilih feature B yang berbeda.");
      const output = window.MapsGeometry.booleanFeatures(feature, features[index], el("mapsEditorBoolean").value);
      replaceSelected([output], index);
    }));
    el("mapsEditorUndoEdit").addEventListener("click", () => {
      if (busy || !editEnabled || !history.length) return;
      stopDrawing();
      const previous = history.pop(); features = previous.features; fields = previous.fields; selected = previous.selected; selectedNode = null;
      el("mapsEditorFields").replaceChildren(); fields.forEach((field) => addFieldRow(field));
      if (features.length) el("mapsEditorGeometry").value = family(features[0].geometry.type);
      el("mapsEditorGeometry").disabled = features.length > 0;
      refreshPreview(); renderHandles(); renderAttributes(); el("mapsEditorUndoEdit").disabled = !history.length;
      error(); hint("Edit terakhir dibatalkan.");
    });
    el("mapsEditorCancelDraw").addEventListener("click", () => {
      if (busy || !editEnabled) return;
      stopDrawing(); refreshPreview(); renderHandles(); hint("Gambar dibatalkan; geometri sebelumnya dipertahankan.");
    });
    el("mapsEditorDivideLayer").addEventListener("click", () => runOperation(() => {
      if (drawing) throw new Error("Selesaikan gambar terlebih dahulu.");
      applyFields();
      const field = el("mapsEditorDivideField").value;
      const groups = window.MapsGeometry.divideLayer(features, field);
      if (!window.confirm(`Simpan ${groups.length} layer SHP terpisah berdasarkan ${field}? Layer asal tetap tersedia.`)) return;
      save(field);
    }));
    return {
      open,
      get active() { return active; },
      mapClick(latlng) {
        if (!active) return false;
        if (busy) return true;
        if (!editEnabled) { selected = -1; refreshPreview(); renderAttributes(); updateEditMode(); return false; }
        if (drawing) {
          points.push([((latlng.lng + 180) % 360 + 360) % 360 - 180, latlng.lat]);
          if (cutMode && points.length === 2) {
            try {
              const output = window.MapsGeometry.splitPolygon(features[selected], points[0], points[1]);
              stopDrawing(); replaceSelected(output);
            } catch (e) { stopDrawing(); renderHandles(); error(e.message); }
          } else if (!cutMode && !continuation && el("mapsEditorGeometry").value === "Point") finishDrawing();
          else showDraft();
          el("mapsEditorGeometry").disabled = true;
        } else { selected = -1; refreshPreview(); renderHandles(); renderAttributes(); }
        return true;
      },
      destroy() { preview.remove(); handles.remove(); draft.remove(); }
    };
  };
})();
