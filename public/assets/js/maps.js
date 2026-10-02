(function () {
  "use strict";

  const MAX_FILE_BYTES = 32 * 1024 * 1024;
  const MAX_ARCHIVE_BYTES = 96 * 1024 * 1024;
  const MAX_FEATURES = 50000;
  const MAX_POINTS = 500000;
  const COLORS = ["#138a72", "#2563eb", "#e87924", "#9333ea", "#0891b2", "#dc2626"];
  let destroyActiveMapPage = null;
  const bases = {
    osm: () => L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap contributors</a>'
    }),
    "open-topo": () => L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", {
      maxZoom: 17,
      attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, SRTM | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a>'
    }),
    "carto-positron": () => L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png", {
      subdomains: "abcd",
      maxZoom: 20,
      attribution: '&copy; OpenStreetMap contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
    }),
    "carto-dark": () => L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
      subdomains: "abcd",
      maxZoom: 20,
      attribution: '&copy; OpenStreetMap contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
    }),
    "esri-street": () => L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}", {
      maxZoom: 19,
      attribution: "Tiles &copy; Esri and its contributors"
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
      if (name && width) fields.push({
        name,
        type: String.fromCharCode(view.getUint8(offset + 11)),
        width,
        decimals: view.getUint8(offset + 17)
      });
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
    return { fields, records };
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

  function collectCoordinates(value, points = []) {
    if (Array.isArray(value) && value.length >= 2 && Number.isFinite(value[0]) && Number.isFinite(value[1])) {
      points.push(value);
    } else if (Array.isArray(value)) {
      value.forEach((child) => collectCoordinates(child, points));
    }
    return points;
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
    if (destroyActiveMapPage) destroyActiveMapPage();
    page.dataset.initialized = "true";
    if (!window.L) throw new Error("Leaflet tidak berhasil dimuat.");

    const list = page.querySelector("#mapLayersList");
    const baseSelect = page.querySelector("#mapsBasemap");
    const form = page.querySelector("#shapefileForm");
    const errorBox = page.querySelector("#shapefileError");
    const statusBox = page.querySelector("#shapefileStatus");
    const coords = page.querySelector("#mapCoordinates");
    const googleViewport = page.querySelector("#googleMapsViewport");
    const featurePropertiesPanel = page.querySelector("#mapsFeatureProperties");
    const mode = page.dataset.mode || "map";
    const settingsPanel = page.querySelector("#mapsLayerSettings");
    const byId = new Map();
    const loadedLayers = new Map();
    const map = L.map(page.querySelector("#mapsViewport"), { preferCanvas: true, zoomControl: false }).setView([-1.25, 119.35], 8);
    let activeBase = null;
    let googleLoader = null;
    let googleMap = null;
    let googleInfoWindow = null;
    let usingGoogle = false;
    let mapFitBounds = null;
    let currentSettingsEntry = null;
    let selectedFeatureLayerId = null;
    let categoriesExpanded = false;
    const showMessage = (target, message) => {
      errorBox.classList.add("hidden");
      statusBox.classList.add("hidden");
      if (message) {
        target.textContent = message;
        target.classList.remove("hidden");
      }
    };
    const showFeatureProperties = (feature, layerId, layerName) => {
      if (!featurePropertiesPanel) return;
      selectedFeatureLayerId = layerId;
      const fields = page.querySelector("#mapsFeatureFields");
      const title = page.querySelector("#mapsFeatureTitle");
      const properties = feature?.properties || {};
      title.textContent = `${layerName} · Properti feature`;
      fields.replaceChildren();
      const entries = Object.entries(properties);
      if (!entries.length) {
        fields.textContent = "Feature ini tidak memiliki atribut.";
      } else {
        const table = document.createElement("table");
        const body = document.createElement("tbody");
        entries.forEach(([key, rawValue]) => {
          const row = document.createElement("tr");
          const name = document.createElement("th");
          name.scope = "row";
          name.textContent = key;
          const value = document.createElement("td");
          value.textContent = rawValue == null || rawValue === "" ? "—" : typeof rawValue === "object" ? JSON.stringify(rawValue) : String(rawValue);
          row.append(name, value);
          body.append(row);
        });
        table.append(body);
        fields.append(table);
      }
      featurePropertiesPanel.hidden = false;
    };
    page.querySelector("#closeMapsFeatureProperties")?.addEventListener("click", () => {
      featurePropertiesPanel.hidden = true;
      selectedFeatureLayerId = null;
    });

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
      const style = entry.style;
      const dataLayer = new google.maps.Data({ map: googleMap });
      dataLayer.addGeoJson(entry.geoJson);
      dataLayer.setStyle((feature) => {
        const props = {};
        feature.forEachProperty((value, key) => { props[key] = value; });
        const featureStyle = getFeatureStyle(props, style);
        return {
          strokeColor: featureStyle.color,
          strokeWeight: featureStyle.weight,
          fillColor: featureStyle.fillColor,
          fillOpacity: style.fill_opacity,
          clickable: true
        };
      });
      dataLayer.addListener("click", (event) => {
        const properties = {};
        event.feature.forEachProperty((value, key) => { properties[key] = value; });
        if (featurePropertiesPanel) {
          showFeatureProperties({ properties }, id, entry.name);
        } else {
          googleInfoWindow.setContent(featurePopup({ properties }));
          googleInfoWindow.setPosition(event.latLng);
          googleInfoWindow.open({ map: googleMap });
        }
      });
      entry.googleLayer = dataLayer;
      renderGoogleLabels(entry);
    }

    function renderGoogleLabels(entry) {
      entry.googleLabelMarkers = [];
      if (entry.style.show_labels && entry.style.label_field) {
        entry.geoJson.features.forEach((feature) => {
          const value = feature.properties?.[entry.style.label_field];
          if (value == null || value === "") return;
          const points = collectCoordinates(feature.geometry.coordinates);
          if (!points.length) return;
          const center = points.reduce((sum, point) => [sum[0] + point[0] / points.length, sum[1] + point[1] / points.length], [0, 0]);
          entry.googleLabelMarkers.push(new google.maps.Marker({
            map: googleMap,
            position: { lat: center[1], lng: center[0] },
            title: String(value),
            label: {
              text: String(value),
              color: entry.style.label_color || "#1f2937",
              fontSize: `${entry.style.label_size || 12}px`,
              fontWeight: entry.style.label_bold ? "700" : "400",
              fontFamily: entry.style.label_font || "Arial, sans-serif"
            },
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 0, fillOpacity: 0, strokeOpacity: 0 }
          }));
        });
      }
    }

    function clearGoogleLayer(entry) {
      entry.googleLayer?.setMap(null);
      entry.googleLayer = null;
      (entry.googleLabelMarkers || []).forEach((marker) => marker.setMap(null));
      entry.googleLabelMarkers = [];
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
            clearGoogleLayer(entry);
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
      const dbf = fileData.dbf ? parseDbf(fileData.dbf, features.length) : { fields: [], records: [] };
      const geoJson = toGeoJson(features, dbf.records);
      if (!geoJson.features.length) throw new Error(`Layer "${row.nama_layer}" tidak memiliki geometri yang dapat ditampilkan.`);
      const defaultColor = COLORS[(id - 1) % COLORS.length];
      const style = {
        renderer: row.style?.renderer === "categorized" ? "categorized" : "simple",
        category_field: row.style?.category_field || "",
        categories: Array.isArray(row.style?.categories) ? row.style.categories : [],
        color: row.style?.color || defaultColor,
        fill_color: row.style?.fill_color || row.style?.color || defaultColor,
        fill_opacity: Number(row.style?.fill_opacity ?? .3),
        weight: Number(row.style?.weight ?? 2),
        line_style: row.style?.line_style || "solid",
        point_radius: Number(row.style?.point_radius ?? 5),
        label_field: row.style?.label_field || "",
        show_labels: Boolean(row.style?.show_labels),
        label_font: row.style?.label_font || "Arial, sans-serif",
        label_size: Number(row.style?.label_size ?? 12),
        label_color: row.style?.label_color || "#1f2937",
        label_bold: Boolean(row.style?.label_bold)
      };
      updateLabelFontStyle(style);
      const layer = L.geoJSON(geoJson, {
        style: (feature) => {
          const featureStyle = getFeatureStyle(feature.properties, style);
          return {
            color: featureStyle.color, weight: featureStyle.weight,
            dashArray: lineDashArray(featureStyle.line_style),
            fillColor: featureStyle.fillColor, fillOpacity: style.fill_opacity
          };
        },
        pointToLayer: (feature, latlng) => {
          const featureStyle = getFeatureStyle(feature.properties, style);
          return L.circleMarker(latlng, {
            radius: style.point_radius, color: featureStyle.color, weight: featureStyle.weight,
            dashArray: lineDashArray(featureStyle.line_style),
            fillColor: featureStyle.fillColor, fillOpacity: style.fill_opacity
          });
        },
        onEachFeature: (feature, featureLayer) => {
          if (featurePropertiesPanel) {
            featureLayer.on("click", () => showFeatureProperties(feature, id, row.nama_layer));
          } else {
            featureLayer.bindPopup(featurePopup(feature), { maxWidth: 380 });
          }
          if (style.show_labels && style.label_field && feature.properties[style.label_field] != null) {
            featureLayer.bindTooltip(String(feature.properties[style.label_field]), {
              permanent: true, direction: "center", className: "maps-feature-label",
              ...labelTooltipOptions(style)
            });
          }
        }
      });
      const entry = { layer, geoJson, count: geoJson.features.length, name: row.nama_layer, style, fields: dbf.fields };
      if (usingGoogle) addGoogleLayer(id, entry);
      return entry;
    }

    function getFeatureStyle(properties, style) {
      if (style.renderer === "categorized" && style.category_field) {
        const value = properties?.[style.category_field];
        const category = style.categories.find((item) => item.value === String(value ?? ""));
        if (category) {
          return {
            color: category.color,
            fillColor: category.color,
            weight: Number(category.weight ?? style.weight),
            line_style: category.line_style || "solid"
          };
        }
      }
      return { color: style.color, fillColor: style.fill_color, weight: style.weight, line_style: style.line_style || "solid" };
    }

    function lineDashArray(lineStyle) {
      return ({ dash: "8 5", dot: "2 5", "dash-dot": "8 4 2 4" })[lineStyle] || null;
    }

    function labelTooltipOptions(style) {
      return {
        direction: "center",
        className: "maps-feature-label"
      };
    }

    function updateLabelFontStyle(style) {
      let styleElement = document.getElementById("mapsDynamicLabelStyle");
      if (!styleElement) {
        styleElement = document.createElement("style");
        styleElement.id = "mapsDynamicLabelStyle";
        document.head.append(styleElement);
      }
      const font = style.label_font || "Arial, sans-serif";
      const fontSize = Math.min(24, Math.max(8, Number(style.label_size) || 12));
      const color = /^#[0-9a-f]{6}$/i.test(style.label_color) ? style.label_color : "#1f2937";
      styleElement.textContent = `.maps-feature-label{font-family:${font};font-size:${fontSize}px;color:${color};font-weight:${style.label_bold ? "700" : "400"}}`;
    }

    function categoryColor(index) {
      const hue = (index * 137.508) % 360;
      const saturation = 0.68;
      const lightness = index % 2 ? 0.43 : 0.52;
      const chroma = (1 - Math.abs(2 * lightness - 1)) * saturation;
      const x = chroma * (1 - Math.abs((hue / 60) % 2 - 1));
      const sectors = [[chroma, x, 0], [x, chroma, 0], [0, chroma, x], [0, x, chroma], [x, 0, chroma], [chroma, 0, x]];
      const match = sectors[Math.floor(hue / 60)];
      const offset = lightness - chroma / 2;
      return `#${match.map((value) => Math.round((value + offset) * 255).toString(16).padStart(2, "0")).join("")}`;
    }

    function renderCategoryLegend(categories) {
      const target = page.querySelector("#mapsCategoriesLegend");
      const search = page.querySelector("#mapsCategorySearch");
      const count = page.querySelector("#mapsCategoryCount");
      const toggle = page.querySelector("#toggleMapsCategories");
      if (!target || !search || !count || !toggle) return;
      target.hidden = !categoriesExpanded;
      toggle.textContent = categoriesExpanded ? "Ciutkan tabel" : "Tampilkan tabel";
      toggle.setAttribute("aria-expanded", categoriesExpanded ? "true" : "false");
      target.replaceChildren();
      if (!categories.length) {
        count.textContent = "Belum ada kategori. Klasifikasikan field untuk membuat legenda.";
        return;
      }
      const table = document.createElement("table");
      const head = document.createElement("thead");
      head.innerHTML = "<tr><th>Nilai</th><th>Warna</th><th>Jenis garis</th><th>Tebal</th></tr>";
      const body = document.createElement("tbody");
      const query = search.value.trim().toLocaleLowerCase("id");
      let visibleCount = 0;
      categories.forEach((category) => {
        const row = document.createElement("tr");
        const categoryValue = category.value === "" ? "(kosong)" : category.value;
        row.hidden = query !== "" && !categoryValue.toLocaleLowerCase("id").includes(query);
        if (!row.hidden) visibleCount++;
        const label = document.createElement("td");
        label.className = "maps-category-value";
        label.textContent = categoryValue;
        const colorCell = document.createElement("td");
        const color = document.createElement("input");
        color.type = "color";
        color.value = category.color;
        color.setAttribute("aria-label", `Warna kategori ${categoryValue}`);
        color.addEventListener("input", () => {
          category.color = color.value;
          if (currentSettingsEntry) applyEntryStyle(currentSettingsEntry, currentSettingsEntry.style);
        });
        colorCell.append(color);
        const lineCell = document.createElement("td");
        const lineStyle = document.createElement("select");
        lineStyle.setAttribute("aria-label", `Jenis garis kategori ${categoryValue}`);
        [["solid", "Utuh"], ["dash", "Putus-putus"], ["dot", "Titik-titik"], ["dash-dot", "Garis-titik"]]
          .forEach(([value, text]) => lineStyle.add(new Option(text, value)));
        lineStyle.value = category.line_style || "solid";
        lineStyle.addEventListener("change", () => {
          category.line_style = lineStyle.value;
          if (currentSettingsEntry) applyEntryStyle(currentSettingsEntry, currentSettingsEntry.style);
        });
        lineCell.append(lineStyle);
        const weightCell = document.createElement("td");
        const weight = document.createElement("input");
        weight.type = "number";
        weight.min = "0.5";
        weight.max = "10";
        weight.step = "0.5";
        weight.value = String(category.weight ?? 2);
        weight.setAttribute("aria-label", `Ketebalan garis kategori ${categoryValue}`);
        weight.addEventListener("input", () => {
          const value = Number(weight.value);
          if (!Number.isFinite(value) || value < 0.5 || value > 10) return;
          category.weight = value;
          if (currentSettingsEntry) applyEntryStyle(currentSettingsEntry, currentSettingsEntry.style);
        });
        weightCell.append(weight);
        row.append(label, colorCell, lineCell, weightCell);
        body.append(row);
      });
      table.append(head, body);
      target.append(table);
      count.textContent = `${visibleCount.toLocaleString("id-ID")} dari ${categories.length.toLocaleString("id-ID")} kategori`;
    }

    function showFieldDatabase(entry) {
      const target = page.querySelector("#mapsDbfFields");
      if (!target) return;
      target.replaceChildren();
      if (!entry.fields.length) {
        target.textContent = "File .dbf tidak disertakan atau tidak memiliki field.";
        return;
      }
      const table = document.createElement("table");
      const head = document.createElement("thead");
      head.innerHTML = "<tr><th>Field</th><th>Tipe</th><th>Panjang</th><th>Contoh</th></tr>";
      const body = document.createElement("tbody");
      entry.fields.forEach((field) => {
        const row = document.createElement("tr");
        [field.name, field.type, String(field.width), String(entry.geoJson.features.find((feature) => feature.properties[field.name] != null)?.properties[field.name] ?? "—")].forEach((value) => {
          const cell = document.createElement("td");
          cell.textContent = value;
          row.append(cell);
        });
        body.append(row);
      });
      table.append(head, body);
      target.append(table);
    }

    function applyEntryStyle(entry, style) {
      entry.style = style;
      updateLabelFontStyle(style);
      entry.layer.eachLayer((featureLayer) => {
        if (featureLayer.setStyle) {
          const featureStyle = getFeatureStyle(featureLayer.feature?.properties, style);
          featureLayer.setStyle({
            radius: style.point_radius, color: featureStyle.color, weight: featureStyle.weight,
            dashArray: lineDashArray(featureStyle.line_style),
            fillColor: featureStyle.fillColor, fillOpacity: style.fill_opacity
          });
        }
        if (featureLayer.unbindTooltip) featureLayer.unbindTooltip();
        const value = featureLayer.feature?.properties?.[style.label_field];
        if (style.show_labels && style.label_field && value != null && featureLayer.bindTooltip) {
          featureLayer.bindTooltip(String(value), {
            permanent: true, direction: "center", className: "maps-feature-label",
            ...labelTooltipOptions(style)
          });
        }
      });
      if (entry.googleLayer) {
        entry.googleLayer.setStyle((feature) => {
          const props = {};
          feature.forEachProperty((value, key) => { props[key] = value; });
          const featureStyle = getFeatureStyle(props, style);
          return {
            strokeColor: featureStyle.color, strokeWeight: featureStyle.weight,
            fillColor: featureStyle.fillColor, fillOpacity: style.fill_opacity, clickable: true
          };
        });
        (entry.googleLabelMarkers || []).forEach((marker) => marker.setMap(null));
        renderGoogleLabels(entry);
      }
    }

    async function selectLayerForSettings(row) {
      if (!loadedLayers.has(row.id)) loadedLayers.set(row.id, await loadLayer(row.id));
      const entry = loadedLayers.get(row.id);
      currentSettingsEntry = entry;
      if (!map.hasLayer(entry.layer) && !usingGoogle) entry.layer.addTo(map);
      const toggle = [...list.querySelectorAll('input[type="checkbox"]')].find((input) => input.getAttribute("aria-label") === `Tampilkan layer ${row.nama_layer}`);
      if (toggle) toggle.checked = true;
      setFitBounds();
      fitActiveMap();
      settingsPanel.hidden = false;
      page.querySelector("#mapsSelectedLayerName").textContent = row.nama_layer;
      page.querySelector("#mapsShowLabels").checked = entry.style.show_labels;
      page.querySelector("#mapsLineColor").value = entry.style.color;
      page.querySelector("#mapsFillColor").value = entry.style.fill_color;
      page.querySelector("#mapsFillOpacity").value = String(entry.style.fill_opacity);
      page.querySelector("#mapsLineWeight").value = String(entry.style.weight);
      page.querySelector("#mapsPointRadius").value = String(entry.style.point_radius);
      page.querySelector("#mapsLineStyle").value = entry.style.line_style || "solid";
      page.querySelector("#mapsLabelFont").value = entry.style.label_font || "Arial, sans-serif";
      page.querySelector("#mapsLabelSize").value = String(entry.style.label_size || 12);
      page.querySelector("#mapsLabelColor").value = entry.style.label_color || "#1f2937";
      page.querySelector("#mapsLabelBold").checked = Boolean(entry.style.label_bold);
      page.querySelector("#mapsLabelStyle").hidden = !entry.style.show_labels;
      const fieldSelect = page.querySelector("#mapsLabelField");
      fieldSelect.replaceChildren(new Option("Tidak ada label", ""));
      entry.fields.forEach((field) => fieldSelect.add(new Option(field.name, field.name)));
      fieldSelect.value = entry.style.label_field;
      const categoryField = page.querySelector("#mapsCategoryField");
      categoryField.replaceChildren(new Option("Pilih field", ""));
      entry.fields.forEach((field) => categoryField.add(new Option(field.name, field.name)));
      categoryField.value = entry.style.category_field;
      page.querySelector("#mapsRenderer").value = entry.style.renderer;
      page.querySelector("#mapsCategorySettings").hidden = entry.style.renderer !== "categorized";
      page.querySelector("#mapsCategorySearch").value = "";
      renderCategoryLegend(entry.style.categories);
      showFieldDatabase(entry);
      coords.textContent = `${row.nama_layer} · ${entry.count.toLocaleString("id-ID")} objek`;
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
        if (canManage && mode === "layers") {
          const actions = document.createElement("div");
          actions.className = "maps-layer-actions";
          const configure = document.createElement("button");
          configure.className = "ui mini basic icon button";
          configure.type = "button";
          configure.title = "Atur simbologi dan field";
          configure.setAttribute("aria-label", `Atur layer ${row.nama_layer}`);
          configure.innerHTML = '<i class="sliders horizontal icon"></i>';
          configure.addEventListener("click", async () => {
            try {
              await selectLayerForSettings(row);
            } catch (error) {
              showMessage(errorBox, error.message);
            }
          });
          actions.append(configure);
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
              const removedEntry = loadedLayers.get(row.id);
              if (removedEntry) clearGoogleLayer(removedEntry);
              loadedLayers.delete(row.id);
              setFitBounds();
              await refreshLayers();
              showMessage(statusBox, "Layer berhasil dihapus.");
            } catch (error) {
              showMessage(errorBox, error.message);
            }
          });
          actions.append(remove);
          container.append(actions);
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
              if (selectedFeatureLayerId === row.id && featurePropertiesPanel) {
                featurePropertiesPanel.hidden = true;
                selectedFeatureLayerId = null;
              }
              entry.layer.remove();
              clearGoogleLayer(entry);
              loadedLayers.delete(row.id);
              setFitBounds();
              coords.textContent = mapFitBounds ? `${loadedLayers.size} layer aktif` : "Pilih layer untuk menampilkan peta.";
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
      const data = await jsonRequest("/maps/api/layers");
      renderRows(data.rows || [], data.can_manage === true);
    }

    setBase("osm");
    L.control.scale({ metric: true, imperial: false }).addTo(map);
    page.querySelector("#mapZoomIn")?.addEventListener("click", () => {
      if (usingGoogle && googleMap) googleMap.setZoom(googleMap.getZoom() + 1);
      else map.zoomIn();
    });
    page.querySelector("#mapZoomOut")?.addEventListener("click", () => {
      if (usingGoogle && googleMap) googleMap.setZoom(googleMap.getZoom() - 1);
      else map.zoomOut();
    });
    page.querySelector("#mapFit")?.addEventListener("click", fitActiveMap);
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
    const onResize = () => {
      if (usingGoogle && google.maps?.event && googleMap) google.maps.event.trigger(googleMap, "resize");
      else map.invalidateSize();
    };
    window.addEventListener("resize", onResize);

    const saveStyleButton = page.querySelector("#saveMapsStyle");
    if (saveStyleButton) {
      saveStyleButton.addEventListener("click", async () => {
        const selectedName = page.querySelector("#mapsSelectedLayerName").textContent;
        const row = [...byId.values()].find((candidate) => candidate.nama_layer === selectedName);
        if (!row || !loadedLayers.has(row.id)) return;
        const entry = loadedLayers.get(row.id);
        const style = readStyleControls(entry);
        try {
          const body = new URLSearchParams({ id: String(row.id), style: JSON.stringify(style), _csrf: window.CSRF_TOKEN || "" });
          await jsonRequest("/maps/style", { method: "POST", body });
          applyEntryStyle(entry, style);
          row.style = style;
          showMessage(statusBox, "Simbologi dan pengaturan label layer berhasil disimpan.");
        } catch (error) {
          showMessage(errorBox, error.message);
        }
      });
    }

    function readStyleControls(entry) {
      return {
        ...entry.style,
        color: page.querySelector("#mapsLineColor").value,
        fill_color: page.querySelector("#mapsFillColor").value,
        fill_opacity: Number(page.querySelector("#mapsFillOpacity").value),
        weight: Number(page.querySelector("#mapsLineWeight").value),
        line_style: page.querySelector("#mapsLineStyle").value,
        point_radius: Number(page.querySelector("#mapsPointRadius").value),
        label_field: page.querySelector("#mapsLabelField").value,
        show_labels: page.querySelector("#mapsShowLabels").checked,
        label_font: page.querySelector("#mapsLabelFont").value,
        label_size: Number(page.querySelector("#mapsLabelSize").value),
        label_color: page.querySelector("#mapsLabelColor").value,
        label_bold: page.querySelector("#mapsLabelBold").checked,
        renderer: page.querySelector("#mapsRenderer").value,
        category_field: page.querySelector("#mapsCategoryField").value,
        categories: entry.style.categories || []
      };
    }

    const updateSelectedStyle = () => {
      if (!currentSettingsEntry) return;
      currentSettingsEntry.style = readStyleControls(currentSettingsEntry);
      page.querySelector("#mapsLabelStyle").hidden = !currentSettingsEntry.style.show_labels;
      applyEntryStyle(currentSettingsEntry, currentSettingsEntry.style);
    };

    const rendererSelect = page.querySelector("#mapsRenderer");
    const categorySettings = page.querySelector("#mapsCategorySettings");
    rendererSelect?.addEventListener("change", () => {
      categorySettings.hidden = rendererSelect.value !== "categorized";
      updateSelectedStyle();
    });
    page.querySelector("#mapsCategoryField")?.addEventListener("change", (event) => {
      if (!currentSettingsEntry) return;
      currentSettingsEntry.style.category_field = event.currentTarget.value;
      currentSettingsEntry.style.categories = [];
      renderCategoryLegend([]);
      applyEntryStyle(currentSettingsEntry, currentSettingsEntry.style);
    });
    page.querySelector("#toggleMapsCategories")?.addEventListener("click", () => {
      categoriesExpanded = !categoriesExpanded;
      renderCategoryLegend(currentSettingsEntry?.style.categories || []);
    });
    page.querySelector("#mapsCategorySearch")?.addEventListener("input", () => {
      renderCategoryLegend(currentSettingsEntry?.style.categories || []);
    });
    ["mapsShowLabels", "mapsLabelField", "mapsLabelFont", "mapsLabelSize", "mapsLabelColor", "mapsLabelBold",
      "mapsLineColor", "mapsFillColor", "mapsFillOpacity", "mapsLineStyle", "mapsLineWeight", "mapsPointRadius"]
      .forEach((id) => {
        const control = page.querySelector(`#${id}`);
        control?.addEventListener(control.type === "range" || control.type === "color" || control.type === "number" ? "input" : "change", updateSelectedStyle);
      });
    page.querySelector("#classifyMapsCategories")?.addEventListener("click", () => {
      if (!currentSettingsEntry) return;
      const field = page.querySelector("#mapsCategoryField").value;
      if (!field) {
        showMessage(errorBox, "Pilih field kategori terlebih dahulu.");
        return;
      }
      const values = new Set(currentSettingsEntry.geoJson.features
        .map((feature) => feature.properties?.[field])
        .filter((value) => value !== null && value !== undefined)
        .map((value) => String(value)));
      if (values.size > 1000) {
        showMessage(errorBox, "Field memiliki lebih dari 1.000 nilai unik. Pilih field dengan kategori lebih sedikit.");
        return;
      }
      currentSettingsEntry.style.renderer = "categorized";
      currentSettingsEntry.style.category_field = field;
      currentSettingsEntry.style.categories = [...values].sort((a, b) => a.localeCompare(b, "id"))
        .map((value, index) => ({
          value,
          color: categoryColor(index),
          line_style: "solid",
          weight: currentSettingsEntry.style.weight
        }));
      rendererSelect.value = "categorized";
      categorySettings.hidden = false;
      categoriesExpanded = true;
      renderCategoryLegend(currentSettingsEntry.style.categories);
      applyEntryStyle(currentSettingsEntry, currentSettingsEntry.style);
      showMessage(statusBox, `${values.size} kategori unik berhasil dibuat.`);
    });

    if (form) {
      form.addEventListener("submit", async (event) => {
        event.preventDefault();
        showMessage(errorBox, "");
        const files = Array.from(page.querySelector("#shapefileInput").files || []);
        if (files.length !== 1 || !files[0].name.toLowerCase().endsWith(".zip")) {
          showMessage(errorBox, "Pilih satu paket ZIP yang berisi file .shp dan seluruh berkas pendampingnya.");
          return;
        }
        if (files[0].size > MAX_ARCHIVE_BYTES) {
          showMessage(errorBox, "Paket ZIP shapefile maksimal 96 MB.");
          return;
        }
        const body = new FormData(form);
        body.append("_csrf", window.CSRF_TOKEN || "");
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
          await jsonRequest("/maps/upload", { method: "POST", body });
          form.reset();
          if (mode !== "upload") form.hidden = true;
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
    destroyActiveMapPage = () => {
      window.removeEventListener("resize", onResize);
      map.remove();
      if (googleMap && google.maps?.event) google.maps.event.clearInstanceListeners(googleMap);
    };
  }

  window.initMapsPage = initMapsPage;
})();
