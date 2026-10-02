(function () {
  "use strict";

  const MAX_FILE_BYTES = 32 * 1024 * 1024;
  const MAX_FEATURES = 50000;
  const MAX_POINTS = 500000;
  const COLORS = ["#138a72", "#2563eb", "#e87924", "#9333ea", "#0891b2", "#dc2626"];
  const bases = {
    osm: () => L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap contributors</a>'
    }),
    "esri-terrain": () => L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}", {
      maxZoom: 19,
      attribution: "Tiles &copy; Esri — Sources: Esri, USGS, NOAA"
    }),
    "esri-satellite": () => L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", {
      maxZoom: 19,
      attribution: "Imagery &copy; Esri and its contributors"
    })
  };

  function readInt(view, offset, littleEndian = true) {
    if (offset < 0 || offset + 4 > view.byteLength) throw new Error("Struktur shapefile tidak lengkap.");
    return view.getInt32(offset, littleEndian);
  }

  function parseShapefile(buffer, projectionText) {
    if (buffer.byteLength < 100) throw new Error("File .shp terlalu kecil atau tidak valid.");
    const view = new DataView(buffer);
    if (readInt(view, 0, false) !== 9994 || readInt(view, 28) !== 1000) {
      throw new Error("Format file tidak dikenali sebagai shapefile yang valid.");
    }

    const features = [];
    let offset = 100;
    let pointCount = 0;
    while (offset < view.byteLength) {
      if (offset + 8 > view.byteLength) throw new Error("Record shapefile terpotong.");
      const contentBytes = readInt(view, offset + 4, false) * 2;
      const contentStart = offset + 8;
      const contentEnd = contentStart + contentBytes;
      if (contentBytes < 4 || contentEnd > view.byteLength) throw new Error("Panjang record shapefile tidak valid.");
      const type = readInt(view, contentStart);
      const feature = { type, parts: [] };

      if ([1, 11, 21].includes(type)) {
        if (contentBytes < 20) throw new Error("Record titik shapefile tidak lengkap.");
        feature.parts.push([[view.getFloat64(contentStart + 4, true), view.getFloat64(contentStart + 12, true)]]);
        pointCount++;
      } else if ([8, 18, 28].includes(type)) {
        if (contentBytes < 40) throw new Error("Record MultiPoint shapefile tidak lengkap.");
        const count = readInt(view, contentStart + 36);
        const firstPoint = contentStart + 40;
        if (count < 0 || firstPoint + count * 16 > contentEnd) throw new Error("Jumlah titik shapefile tidak valid.");
        const points = [];
        for (let i = 0; i < count; i++) {
          const p = firstPoint + i * 16;
          points.push([view.getFloat64(p, true), view.getFloat64(p + 8, true)]);
        }
        feature.parts.push(points);
        pointCount += count;
      } else if ([3, 5, 13, 15, 23, 25].includes(type)) {
        if (contentBytes < 44) throw new Error("Record garis/poligon shapefile tidak lengkap.");
        const partCount = readInt(view, contentStart + 36);
        const count = readInt(view, contentStart + 40);
        const partsStart = contentStart + 44;
        const pointsStart = partsStart + partCount * 4;
        if (partCount < 0 || count < 0 || partCount > count || pointsStart + count * 16 > contentEnd) {
          throw new Error("Jumlah bagian atau titik shapefile tidak valid.");
        }
        const starts = [];
        for (let i = 0; i < partCount; i++) starts.push(readInt(view, partsStart + i * 4));
        const points = [];
        for (let i = 0; i < count; i++) {
          const p = pointsStart + i * 16;
          points.push([view.getFloat64(p, true), view.getFloat64(p + 8, true)]);
        }
        starts.forEach((start, i) => {
          const end = starts[i + 1] ?? points.length;
          if (start < 0 || end < start || end > points.length) throw new Error("Indeks bagian shapefile tidak valid.");
          feature.parts.push(points.slice(start, end));
        });
        pointCount += count;
      } else if (type !== 0) {
        throw new Error(`Tipe geometri shapefile ${type} belum didukung.`);
      }

      if (pointCount > MAX_POINTS) throw new Error("Shapefile berisi terlalu banyak titik untuk ditampilkan di browser.");
      features.push(feature);
      if (features.length > MAX_FEATURES) throw new Error("Shapefile berisi terlalu banyak objek untuk ditampilkan di browser.");
      offset = contentEnd;
    }
    if (features.length === 0) throw new Error("Shapefile tidak berisi objek geometri.");

    let transform;
    if (projectionText.trim()) {
      if (typeof window.proj4 !== "function") throw new Error("Library konversi sistem koordinat tidak tersedia.");
      try {
        const convert = window.proj4(projectionText, "EPSG:4326");
        transform = (point) => convert.forward(point);
        transform([0, 0]);
      } catch (_) {
        throw new Error("Sistem koordinat pada .prj tidak dikenali. Ekspor shapefile ke WGS 84 (EPSG:4326) lalu coba lagi.");
      }
    } else {
      transform = ([x, y]) => {
        if (Math.abs(x) > 180 || Math.abs(y) > 90) {
          throw new Error("File tidak memiliki .prj dan koordinatnya bukan derajat WGS 84. Sertakan .prj atau ekspor ke EPSG:4326.");
        }
        return [x, y];
      };
    }

    features.forEach((feature) => {
      feature.parts = feature.parts.map((part) => part.map((point) => {
        const converted = transform(point);
        if (!Number.isFinite(converted[0]) || !Number.isFinite(converted[1]) ||
            Math.abs(converted[0]) > 180 || Math.abs(converted[1]) > 90) {
          throw new Error("Hasil konversi koordinat tidak valid. Periksa file .prj pendamping.");
        }
        return converted;
      }));
    });
    return features;
  }

  function parseDbf(buffer, expectedRecords) {
    if (buffer.byteLength < 33) throw new Error("File .dbf pendamping tidak valid.");
    const view = new DataView(buffer);
    const recordCount = view.getUint32(4, true);
    const headerLength = view.getUint16(8, true);
    const recordLength = view.getUint16(10, true);
    if (recordCount < expectedRecords || headerLength < 33 || recordLength < 1 || headerLength > buffer.byteLength) {
      throw new Error("File .dbf tidak cocok atau strukturnya tidak valid.");
    }
    const decoder = new TextDecoder("windows-1252");
    const fields = [];
    for (let offset = 32; offset + 32 <= headerLength && view.getUint8(offset) !== 0x0d; offset += 32) {
      let name = "";
      for (let i = 0; i < 11 && view.getUint8(offset + i) !== 0; i++) name += String.fromCharCode(view.getUint8(offset + i));
      const width = view.getUint8(offset + 16);
      if (name && width) fields.push({ name, type: String.fromCharCode(view.getUint8(offset + 11)), width });
    }

    const records = [];
    for (let index = 0; index < expectedRecords; index++) {
      const recordStart = headerLength + index * recordLength;
      if (recordStart + recordLength > buffer.byteLength) throw new Error("File .dbf terpotong sebelum semua atribut terbaca.");
      const deleted = view.getUint8(recordStart) === 0x2a;
      let cursor = recordStart + 1;
      const record = {};
      for (const field of fields) {
        const value = decoder.decode(new Uint8Array(buffer, cursor, field.width)).trim();
        cursor += field.width;
        if (deleted || value === "") continue;
        if ("NFI".includes(field.type) && Number.isFinite(Number(value))) record[field.name] = Number(value);
        else if (field.type === "L") record[field.name] = ["Y", "T"].includes(value.toUpperCase());
        else record[field.name] = value;
      }
      records.push(record);
    }
    return records;
  }

  function signedArea(ring) {
    let area = 0;
    for (let i = 0; i < ring.length; i++) {
      const current = ring[i];
      const next = ring[(i + 1) % ring.length];
      area += current[0] * next[1] - next[0] * current[1];
    }
    return area / 2;
  }

  function toGeoJson(features, records) {
    return {
      type: "FeatureCollection",
      features: features.map((feature, index) => {
        const shapeType = feature.type % 10;
        let geometry = null;
        if ((shapeType === 1 || shapeType === 8) && feature.parts.length) {
          const points = feature.parts.flat();
          if (points.length) geometry = shapeType === 1
            ? { type: "Point", coordinates: points[0] }
            : { type: "MultiPoint", coordinates: points };
        } else if ((shapeType === 3 || shapeType === 5) && feature.parts.length) {
          if (shapeType === 3) {
            geometry = feature.parts.length === 1
              ? { type: "LineString", coordinates: feature.parts[0] }
              : { type: "MultiLineString", coordinates: feature.parts };
          } else {
            const polygons = [];
            feature.parts.forEach((ring) => {
              if (ring.length < 4) return;
              if (signedArea(ring) < 0 || !polygons.length) polygons.push([ring]);
              else polygons[polygons.length - 1].push(ring);
            });
            geometry = polygons.length === 1
              ? { type: "Polygon", coordinates: polygons[0] }
              : { type: "MultiPolygon", coordinates: polygons };
          }
        }
        return { type: "Feature", geometry, properties: records[index] || {} };
      }).filter((feature) => feature.geometry)
    };
  }

  function esc(value) {
    return String(value).replace(/[&<>"']/g, (char) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[char]);
  }

  function featurePopup(feature) {
    const entries = Object.entries(feature.properties || {}).slice(0, 50);
    if (!entries.length) return "<div>Objek tanpa atribut.</div>";
    return `<div class="maps-feature-popup"><table>${entries.map(([key, value]) =>
      `<tr><th>${esc(key)}</th><td>${esc(value)}</td></tr>`).join("")}</table></div>`;
  }

  async function jsonRequest(url, options = {}) {
    const { headers: requestHeaders, ...requestOptions } = options;
    const response = await fetch(url, {
      ...requestOptions,
      credentials: "same-origin",
      headers: { "X-Requested-With": "XMLHttpRequest", ...(requestHeaders || {}) }
    });
    const payload = await response.json();
    if (!response.ok || payload.success !== true) throw new Error(payload.message || `Request gagal (${response.status}).`);
    return payload.data || {};
  }

  function initMapsPage() {
    const page = document.querySelector(".maps-page");
    if (!page || page.dataset.initialized === "true") return;
    page.dataset.initialized = "true";
    if (!window.L) throw new Error("Leaflet tidak berhasil dimuat.");

    const list = page.querySelector("#mapLayersList");
    const baseSelect = page.querySelector("#mapsBasemap");
    const form = page.querySelector("#shapefileForm");
    const errorBox = page.querySelector("#shapefileError");
    const statusBox = page.querySelector("#shapefileStatus");
    const coords = page.querySelector("#mapCoordinates");
    const googleViewport = page.querySelector("#googleMapsViewport");
    const byId = new Map();
    const loadedLayers = new Map();
    const map = L.map(page.querySelector("#mapsViewport"), { preferCanvas: true, zoomControl: false }).setView([-1.25, 119.35], 8);
    let activeBase = null;
    let googleLoader = null;
    let googleMap = null;
    let googleInfoWindow = null;
    let usingGoogle = false;
    let mapFitBounds = null;
    const showMessage = (target, message) => {
      errorBox.classList.add("hidden");
      statusBox.classList.add("hidden");
      if (message) {
        target.textContent = message;
        target.classList.remove("hidden");
      }
    };

    function setBase(name) {
      if (activeBase) map.removeLayer(activeBase);
      activeBase = bases[name]();
      activeBase.addTo(map);
    }

    function loadGoogleApi() {
      if (window.google?.maps) return Promise.resolve();
      if (googleLoader) return googleLoader;
      const key = String(window.MAPS_GOOGLE_API_KEY || "");
      if (!key) return Promise.reject(new Error("Google Maps Platform API key belum dikonfigurasi."));
      googleLoader = new Promise((resolve, reject) => {
        const script = document.createElement("script");
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&v=weekly`;
        script.async = true;
        script.onload = () => window.google?.maps
          ? resolve()
          : reject(new Error("Google Maps API gagal dimuat."));
        script.onerror = () => reject(new Error("Google Maps API gagal dimuat. Periksa API key dan pembatasan domain."));
        document.head.append(script);
      });
      return googleLoader;
    }

    function addGoogleLayer(id, entry) {
      if (!googleMap || entry.googleLayer) return;
      const color = COLORS[(id - 1) % COLORS.length];
      const dataLayer = new google.maps.Data({ map: googleMap });
      dataLayer.addGeoJson(entry.geoJson);
      dataLayer.setStyle({
        strokeColor: color,
        strokeWeight: 2,
        fillColor: color,
        fillOpacity: 0.3,
        clickable: true
      });
      dataLayer.addListener("click", (event) => {
        const properties = {};
        event.feature.forEachProperty((value, key) => { properties[key] = value; });
        googleInfoWindow.setContent(featurePopup({ properties }));
        googleInfoWindow.setPosition(event.latLng);
        googleInfoWindow.open({ map: googleMap });
      });
      entry.googleLayer = dataLayer;
    }

    async function selectBase(name) {
      if (name.startsWith("google-")) {
        await loadGoogleApi();
        const type = name.replace("google-", "");
        if (!googleMap) {
          const center = map.getCenter();
          googleMap = new google.maps.Map(googleViewport, {
            center: { lat: center.lat, lng: center.lng },
            zoom: map.getZoom(),
            mapTypeId: type,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
          });
          googleInfoWindow = new google.maps.InfoWindow();
        } else {
          googleMap.setMapTypeId(type);
        }
        page.querySelector("#mapsViewport").hidden = true;
        googleViewport.hidden = false;
        usingGoogle = true;
        loadedLayers.forEach((entry, id) => addGoogleLayer(id, entry));
        if (mapFitBounds) {
          googleMap.fitBounds([
            { lat: mapFitBounds.getSouth(), lng: mapFitBounds.getWest() },
            { lat: mapFitBounds.getNorth(), lng: mapFitBounds.getEast() }
          ]);
        }
      } else {
        if (usingGoogle && googleMap) {
          const center = googleMap.getCenter();
          if (center) map.setView([center.lat(), center.lng()], googleMap.getZoom(), { animate: false });
          loadedLayers.forEach((entry) => {
            if (entry.googleLayer) {
              entry.googleLayer.setMap(null);
              entry.googleLayer = null;
            }
          });
        }
        usingGoogle = false;
        googleViewport.hidden = true;
        page.querySelector("#mapsViewport").hidden = false;
        setBase(name);
        setTimeout(() => map.invalidateSize(), 0);
      }
      showMessage(errorBox, "");
    }

    function setFitBounds() {
      const bounds = [];
      loadedLayers.forEach((entry) => {
        if (entry.layer.getBounds && entry.layer.getBounds().isValid()) bounds.push(entry.layer.getBounds());
      });
      if (bounds.length) mapFitBounds = bounds.reduce((combined, next) => combined.extend(next), bounds[0]);
      else mapFitBounds = null;
    }

    function fitActiveMap() {
      if (!mapFitBounds) return;
      if (usingGoogle && googleMap) {
        googleMap.fitBounds([
          { lat: mapFitBounds.getSouth(), lng: mapFitBounds.getWest() },
          { lat: mapFitBounds.getNorth(), lng: mapFitBounds.getEast() }
        ]);
      } else {
        map.fitBounds(mapFitBounds, { padding: [24, 24], maxZoom: 16 });
      }
    }

    async function loadLayer(id) {
      const row = byId.get(id);
      if (!row) throw new Error("Layer tidak ditemukan.");
      const parts = row.components;
      const fetchPart = async (part) => {
        const response = await fetch(`/maps/file?id=${encodeURIComponent(id)}&part=${part}`, {
          credentials: "same-origin",
          headers: { "X-Requested-With": "XMLHttpRequest" },
          cache: "no-store"
        });
        if (!response.ok) throw new Error(`Gagal memuat komponen .${part} untuk layer "${row.nama_layer}".`);
        const buffer = await response.arrayBuffer();
        if (buffer.byteLength > MAX_FILE_BYTES) throw new Error(`File .${part} melebihi batas 32 MB.`);
        return buffer;
      };
      const buffers = await Promise.all(["shp", "dbf", "prj"].filter((part) => parts.includes(part)).map(async (part) => [part, await fetchPart(part)]));
      const fileData = Object.fromEntries(buffers);
      const projectionText = fileData.prj ? new TextDecoder().decode(fileData.prj) : "";
      const features = parseShapefile(fileData.shp, projectionText);
      const records = fileData.dbf ? parseDbf(fileData.dbf, features.length) : [];
      const geoJson = toGeoJson(features, records);
      if (!geoJson.features.length) throw new Error(`Layer "${row.nama_layer}" tidak memiliki geometri yang dapat ditampilkan.`);
      const color = COLORS[(id - 1) % COLORS.length];
      const layer = L.geoJSON(geoJson, {
        style: () => ({ color, weight: 2, fillColor: color, fillOpacity: 0.26 }),
        pointToLayer: (_feature, latlng) => L.circleMarker(latlng, {
          radius: 5, color, weight: 1.5, fillColor: color, fillOpacity: .85
        }),
        onEachFeature: (feature, featureLayer) => featureLayer.bindPopup(featurePopup(feature), { maxWidth: 380 })
      });
      const entry = { layer, geoJson, count: geoJson.features.length, name: row.nama_layer };
      if (usingGoogle) addGoogleLayer(id, entry);
      return entry;
    }

    function renderRows(rows, canManage) {
      byId.clear();
      list.replaceChildren();
      if (!rows.length) {
        const empty = document.createElement("div");
        empty.className = "ui message";
        empty.textContent = "Belum ada layer shapefile untuk wilayah/OPD ini.";
        list.append(empty);
        return;
      }
      rows.forEach((row) => {
        row.id = Number(row.id);
        byId.set(row.id, row);
        const container = document.createElement("div");
        container.className = "maps-layer-row";
        const toggle = document.createElement("input");
        toggle.type = "checkbox";
        toggle.checked = loadedLayers.has(row.id);
        toggle.setAttribute("aria-label", `Tampilkan layer ${row.nama_layer}`);
        const details = document.createElement("div");
        details.className = "maps-layer-label";
        const title = document.createElement("strong");
        title.textContent = row.nama_layer;
        const meta = document.createElement("small");
        meta.textContent = `${row.kd_wilayah} / ${row.kd_opd} · ${row.username_insert} · ${(row.ukuran / 1048576).toFixed(1)} MB`;
        details.append(title, meta);
        container.append(toggle, details);
        if (canManage) {
          const remove = document.createElement("button");
          remove.className = "ui mini basic icon button";
          remove.type = "button";
          remove.title = "Hapus layer";
          remove.setAttribute("aria-label", `Hapus layer ${row.nama_layer}`);
          remove.innerHTML = '<i class="trash alternate outline icon"></i>';
          remove.addEventListener("click", async () => {
            if (!window.confirm(`Hapus layer "${row.nama_layer}" dari daftar OPD?`)) return;
            try {
              const body = new URLSearchParams({ id: String(row.id), _csrf: window.CSRF_TOKEN || "" });
              await jsonRequest("/maps/delete", { method: "POST", body });
              loadedLayers.get(row.id)?.layer.remove();
              loadedLayers.get(row.id)?.googleLayer?.setMap(null);
              loadedLayers.delete(row.id);
              setFitBounds();
              await refreshLayers();
              showMessage(statusBox, "Layer berhasil dihapus.");
            } catch (error) {
              showMessage(errorBox, error.message);
            }
          });
          container.append(remove);
        }
        toggle.addEventListener("change", async () => {
          try {
            if (toggle.checked) {
              if (!loadedLayers.has(row.id)) loadedLayers.set(row.id, await loadLayer(row.id));
              const entry = loadedLayers.get(row.id);
              if (usingGoogle) addGoogleLayer(row.id, entry);
              else entry.layer.addTo(map);
              setFitBounds();
              fitActiveMap();
              coords.textContent = `${row.nama_layer} · ${loadedLayers.get(row.id).count.toLocaleString("id-ID")} objek`;
            } else if (loadedLayers.has(row.id)) {
              const entry = loadedLayers.get(row.id);
              entry.layer.remove();
              entry.googleLayer?.setMap(null);
              entry.googleLayer = null;
              loadedLayers.delete(row.id);
              setFitBounds();
              coords.textContent = mapFitBounds ? `${loadedLayers.size} layer aktif` : "Pilih peta dasar atau layer untuk mulai.";
            }
          } catch (error) {
            toggle.checked = false;
            showMessage(errorBox, error.message);
          }
        });
        list.append(container);
      });
    }

    async function refreshLayers() {
      const data = await jsonRequest("/maps/layers");
      renderRows(data.rows || [], data.can_manage === true);
    }

    setBase("osm");
    L.control.scale({ metric: true, imperial: false }).addTo(map);
    page.querySelector("#mapZoomIn").addEventListener("click", () => map.zoomIn());
    page.querySelector("#mapZoomOut").addEventListener("click", () => map.zoomOut());
    page.querySelector("#mapFit").addEventListener("click", () => {
      fitActiveMap();
    });
    baseSelect.addEventListener("change", async () => {
      try {
        await selectBase(baseSelect.value);
      } catch (error) {
        showMessage(errorBox, error.message);
        baseSelect.value = "osm";
        setBase("osm");
      }
    });
    map.whenReady(() => setTimeout(() => map.invalidateSize(), 0));
    window.addEventListener("resize", () => {
      if (usingGoogle && google.maps?.event && googleMap) google.maps.event.trigger(googleMap, "resize");
      else map.invalidateSize();
    });

    const addButton = page.querySelector("#showAddLayer");
    if (addButton && form) {
      addButton.addEventListener("click", () => { form.hidden = !form.hidden; });
      page.querySelector("#cancelAddLayer").addEventListener("click", () => {
        form.reset();
        form.hidden = true;
        showMessage(errorBox, "");
      });
      form.addEventListener("submit", async (event) => {
        event.preventDefault();
        showMessage(errorBox, "");
        const files = Array.from(page.querySelector("#shapefileInput").files || []);
        if (!files.some((file) => file.name.toLowerCase().endsWith(".shp"))) {
          showMessage(errorBox, "Pilih file .shp. Anda juga dapat memilih file .dbf, .shx, dan .prj dengan nama yang sama.");
          return;
        }
        if (files.some((file) => file.size > MAX_FILE_BYTES)) {
          showMessage(errorBox, "Setiap komponen shapefile maksimal 32 MB.");
          return;
        }
        const body = new FormData(form);
        body.append("_csrf", window.CSRF_TOKEN || "");
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
          await jsonRequest("/maps/upload", { method: "POST", body });
          form.reset();
          form.hidden = true;
          await refreshLayers();
          showMessage(statusBox, "Shapefile berhasil diunggah dan tersimpan untuk wilayah/OPD Anda.");
        } catch (error) {
          showMessage(errorBox, error.message);
        } finally {
          button.disabled = false;
        }
      });
    }

    refreshLayers().catch((error) => {
      list.textContent = error.message;
      showMessage(errorBox, error.message);
    });
  }

  window.initMapsPage = initMapsPage;
})();
