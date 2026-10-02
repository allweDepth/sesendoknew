<?php
$mode = in_array($mode ?? '', ['map', 'layers', 'upload'], true) ? $mode : 'map';
$pageTitle = ['map' => 'Peta', 'layers' => 'Atur Layer SHP', 'upload' => 'Tambah/Unggah SHP'][$mode];
?>
<section class="maps-page maps-mode-<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" data-mode="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" data-can-manage="<?= $canManageLayers ? '1' : '0' ?>">
  <?php if ($mode === 'map'): ?>
    <div class="maps-full-toolbar">
      <label for="mapsBasemap">Peta dasar</label>
      <select id="mapsBasemap" aria-label="Pilih peta dasar">
        <option value="osm">OpenStreetMap</option>
        <option value="esri-terrain">Esri Terrain / Topographic</option>
        <option value="esri-satellite">Esri Satellite</option>
        <option value="google-roadmap" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Roadmap<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
        <option value="google-satellite" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Satellite<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
        <option value="google-hybrid" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Hybrid<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
        <option value="google-terrain" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Terrain<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
      </select>
      <?php if (empty($googleMapsApiKey)): ?><span class="maps-google-note" title="Memerlukan Google Maps Platform API key">Google memerlukan API key</span><?php endif; ?>
      <span id="mapCoordinates">Peta siap · pilih layer pada panel Layer</span>
      <span class="maps-toolbar-spacer"></span>
      <button class="ui icon button" id="mapZoomIn" type="button" title="Perbesar" aria-label="Perbesar"><i class="plus icon"></i></button>
      <button class="ui icon button" id="mapZoomOut" type="button" title="Perkecil" aria-label="Perkecil"><i class="minus icon"></i></button>
      <button class="ui icon button" id="mapFit" type="button" title="Sesuaikan layer" aria-label="Sesuaikan layer"><i class="expand icon"></i></button>
      <a class="ui basic button maps-layer-shortcut" href="/maps/layers" data-spa="server" data-title="Maps/Atur Layer SHP"><i class="layers icon"></i>Layer</a>
    </div>
    <div class="maps-full-shell">
      <div id="mapsViewport" class="maps-viewport" role="application" aria-label="Peta interaktif"></div>
      <div id="googleMapsViewport" class="maps-viewport" role="application" aria-label="Google Maps" hidden></div>
      <aside class="maps-floating-layers">
        <h3><i class="layers icon"></i> Layer OPD</h3>
        <div id="mapLayersList" class="maps-layers-list"><div class="ui active inline loader"></div> Memuat layer…</div>
      </aside>
    </div>
    <div id="shapefileError" class="ui hidden negative message maps-toast" role="alert"></div>
    <div id="shapefileStatus" class="ui hidden positive message maps-toast" role="status"></div>
  <?php elseif ($mode === 'layers'): ?>
    <div class="maps-page-heading"><div><h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1><p>Atur visibilitas, simbologi, label, dan periksa field atribut shapefile.</p></div></div>
    <div class="maps-layout">
      <aside class="ui segment maps-panel">
        <div class="maps-basemap-field"><label for="mapsBasemap">Peta dasar</label><select id="mapsBasemap" class="ui fluid dropdown">
          <option value="osm">OpenStreetMap</option><option value="esri-terrain">Esri Terrain / Topographic</option><option value="esri-satellite">Esri Satellite</option>
          <option value="google-roadmap" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Roadmap<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
          <option value="google-satellite" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Satellite<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
          <option value="google-hybrid" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Hybrid<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
          <option value="google-terrain" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Terrain<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
        </select></div>
        <h2 class="ui small header">Layer wilayah/OPD</h2>
        <div id="mapLayersList" class="maps-layers-list"><div class="ui active inline loader"></div> Memuat layer…</div>
        <div id="mapsLayerSettings" class="maps-layer-settings" hidden>
          <div class="ui divider"></div>
          <h3 id="mapsSelectedLayerName"></h3>
          <div class="field"><label for="mapsRenderer">Metode simbologi</label><select id="mapsRenderer" class="ui fluid dropdown"><option value="simple">Simbol tunggal</option><option value="categorized">Kategori berdasarkan field</option></select></div>
          <div id="mapsCategorySettings" hidden>
            <div class="field"><label for="mapsCategoryField">Field kategori</label><select id="mapsCategoryField" class="ui fluid dropdown"><option value="">Pilih field</option></select></div>
            <button class="ui small basic button" id="classifyMapsCategories" type="button">Klasifikasikan nilai unik</button>
            <div id="mapsCategoriesLegend" class="maps-categories-legend"></div>
          </div>
          <label class="maps-setting-toggle"><input id="mapsShowLabels" type="checkbox"> Tampilkan label</label>
          <div class="field"><label for="mapsLabelField">Field label</label><select id="mapsLabelField" class="ui fluid dropdown"><option value="">Tidak ada label</option></select></div>
          <div class="maps-style-grid">
            <div class="field"><label for="mapsLineColor">Warna garis/titik</label><input id="mapsLineColor" type="color" value="#138a72"></div>
            <div class="field"><label for="mapsFillColor">Warna isi</label><input id="mapsFillColor" type="color" value="#138a72"></div>
            <div class="field"><label for="mapsFillOpacity">Opasitas isi</label><input id="mapsFillOpacity" type="range" min="0" max="1" step=".05" value=".3"></div>
            <div class="field"><label for="mapsLineWeight">Tebal garis</label><input id="mapsLineWeight" type="range" min=".5" max="10" step=".5" value="2"></div>
            <div class="field"><label for="mapsPointRadius">Ukuran titik</label><input id="mapsPointRadius" type="range" min="2" max="16" step="1" value="5"></div>
          </div>
          <button class="ui primary fluid button" id="saveMapsStyle" type="button"><i class="save icon"></i>Simpan simbologi</button>
          <h4 class="ui tiny header">Database field (.dbf)</h4>
          <div id="mapsDbfFields" class="maps-dbf-fields">Aktifkan layer untuk membaca field.</div>
        </div>
        <div id="shapefileError" class="ui hidden negative message" role="alert"></div>
        <div id="shapefileStatus" class="ui hidden positive message" role="status"></div>
      </aside>
      <div class="ui segment maps-preview-panel">
        <div class="maps-preview-toolbar"><strong>Pratinjau</strong><span id="mapCoordinates">Pilih layer untuk menampilkan peta.</span>
          <div class="ui buttons"><button class="ui icon button" id="mapZoomIn" type="button" title="Perbesar" aria-label="Perbesar"><i class="plus icon"></i></button><button class="ui icon button" id="mapZoomOut" type="button" title="Perkecil" aria-label="Perkecil"><i class="minus icon"></i></button><button class="ui icon button" id="mapFit" type="button" title="Sesuaikan layer" aria-label="Sesuaikan layer"><i class="expand icon"></i></button></div>
        </div>
        <div id="mapsViewport" class="maps-viewport" role="application" aria-label="Pratinjau peta"></div>
        <div id="googleMapsViewport" class="maps-viewport" role="application" aria-label="Pratinjau Google Maps" hidden></div>
      </div>
    </div>
  <?php else: ?>
    <div class="maps-page-heading"><div><h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1><p>Unggah shapefile dan komponen pendamping ke ruang data wilayah/OPD aktif.</p></div></div>
    <div class="maps-layout maps-upload-layout">
      <section class="ui segment maps-panel">
        <?php if ($canManageLayers): ?>
          <form class="ui form maps-upload-form" id="shapefileForm">
            <div class="field"><label for="layerName">Nama layer (opsional)</label><input id="layerName" name="nama_layer" type="text" maxlength="160" placeholder="Nama layer pada peta"></div>
            <div class="field"><label for="shapefileInput">Paket shapefile lengkap (.zip)</label><input id="shapefileInput" type="file" name="files[]" accept=".zip,application/zip" required><small>ZIP harus berisi tepat satu .shp dan berkas pendamping dengan nama dasar yang sama (.shx, .dbf, .prj, .cpg, dan sidecar shapefile yang dikenal). Maksimal 32 MB per komponen dan 96 MB total.</small></div>
            <div class="ui info message"><i class="lock icon"></i>File disimpan privat dan hanya dibagikan dalam wilayah/OPD sesuai hak akses.</div>
            <button class="ui primary button" type="submit"><i class="cloud upload icon"></i>Unggah ke OPD</button>
            <a class="ui button" href="/maps" data-spa="server" data-title="Maps/Peta">Kembali ke Peta</a>
          </form>
        <?php else: ?>
          <div class="ui warning message"><div class="header">Akses unggah tidak tersedia</div><p>Unggah layer hanya dapat dilakukan oleh admin OPD, kepala OPD, atau PA/KPA.</p></div>
        <?php endif; ?>
        <div id="shapefileError" class="ui hidden negative message" role="alert"></div>
        <div id="shapefileStatus" class="ui hidden positive message" role="status"></div>
      </section>
      <section class="ui segment maps-preview-panel">
        <div class="maps-preview-toolbar"><strong>Layer yang sudah ada</strong><span id="mapCoordinates">Pilih layer untuk pratinjau.</span></div>
        <div id="mapsViewport" class="maps-viewport" role="application" aria-label="Pratinjau peta"></div>
        <div id="googleMapsViewport" class="maps-viewport" role="application" aria-label="Pratinjau Google Maps" hidden></div>
        <label class="maps-upload-basemap" for="mapsBasemap">Peta dasar</label>
        <select id="mapsBasemap"><option value="osm">OpenStreetMap</option><option value="esri-terrain">Esri Terrain</option><option value="esri-satellite">Esri Satellite</option>
          <option value="google-roadmap" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Roadmap<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
          <option value="google-satellite" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Satellite<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
          <option value="google-hybrid" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Hybrid<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
          <option value="google-terrain" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Terrain<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
        </select>
        <div id="mapLayersList" class="maps-upload-existing-layers"></div>
      </section>
    </div>
  <?php endif; ?>
  <script>window.MAPS_GOOGLE_API_KEY = <?= json_encode((string)($googleMapsApiKey ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
</section>
<style>
  .maps-page { width: 100%; max-width: none !important; height: 100%; min-height: 0; padding: 0 !important; }
  .maps-mode-map { display: flex; flex-direction: column; gap: 8px; height: 100%; min-height: 520px; }
  .maps-full-toolbar { display: flex; align-items: center; gap: 8px; min-height: 48px; padding: 6px 10px; background: var(--surface, #fff); border: 1px solid #d9e2ec; border-radius: 8px; }
  .maps-full-toolbar label { white-space: nowrap; font-weight: 700; }
  .maps-full-toolbar select { max-width: 230px; min-height: 34px; border: 1px solid #cbd5e1; border-radius: 5px; padding: 5px 8px; }
  .maps-toolbar-spacer { flex: 1; }
  .maps-full-toolbar .button { margin: 0 !important; }
  .maps-google-note { color: #697586; font-size: .85em; }
  .maps-full-shell { position: relative; flex: 1; min-height: 0; overflow: hidden; border: 1px solid #d9e2ec; border-radius: 9px; }
  .maps-viewport { width: 100%; height: 100%; min-height: 330px; overflow: hidden; background: #eef3f6; }
  .maps-viewport .leaflet-container { width: 100%; height: 100%; font: inherit; }
  #googleMapsViewport[hidden] { display: none; }
  .maps-floating-layers { position: absolute; z-index: 500; top: 12px; right: 12px; width: min(300px, 38vw); max-height: calc(100% - 24px); padding: 13px; overflow: auto; background: rgba(255,255,255,.96); border: 1px solid #d9e2ec; border-radius: 9px; box-shadow: 0 3px 18px rgba(0,0,0,.18); }
  .maps-floating-layers h3 { margin: 0 0 10px; font-size: 1rem; }
  .maps-layers-list { display: grid; gap: 7px; max-height: 38vh; overflow-y: auto; }
  .maps-layer-row { display: flex; align-items: flex-start; gap: 8px; padding: 8px; border: 1px solid #d9e2ec; border-radius: 7px; }
  .maps-layer-row input { margin-top: 3px; }
  .maps-layer-label { min-width: 0; flex: 1; overflow-wrap: anywhere; }
  .maps-layer-label small { display: block; margin-top: 3px; color: #697586; }
  .maps-layer-actions { display: flex; gap: 4px; }
  .maps-layer-actions .button { margin: 0 !important; }
  .maps-page-heading { padding: 4px 4px 14px; }
  .maps-page-heading h1 { margin: 0 0 4px; }
  .maps-page-heading p { margin: 0; color: #697586; }
  .maps-mode-layers, .maps-mode-upload { overflow: auto; }
  .maps-layout { display: grid; grid-template-columns: minmax(320px, 390px) minmax(0, 1fr); gap: 14px; min-height: 640px; }
  .maps-panel, .maps-preview-panel { margin: 0 !important; border-radius: 10px !important; }
  .maps-panel { min-width: 0; overflow: auto; }
  .maps-basemap-field { display: grid; gap: 6px; margin-bottom: 14px; }
  .maps-basemap-field label, .maps-panel > h2 { font-weight: 700; }
  .maps-layer-heading, .maps-preview-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
  .maps-preview-toolbar .ui.buttons { margin-left: auto; }
  .maps-mode-layers .maps-viewport { height: min(72vh, 820px); min-height: 480px; border-radius: 7px; }
  .maps-layer-settings { margin-top: 12px; }
  .maps-layer-settings h3 { overflow-wrap: anywhere; }
  .maps-setting-toggle { display: flex; align-items: center; gap: 7px; margin: 12px 0; }
  .maps-style-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
  .maps-style-grid .field { margin: 0 !important; }
  .maps-style-grid input[type=color] { width: 100%; height: 34px; padding: 2px; }
  .maps-dbf-fields { max-height: 250px; overflow: auto; font-size: .88em; }
  .maps-dbf-fields table { width: 100%; border-collapse: collapse; }
  .maps-dbf-fields th, .maps-dbf-fields td { padding: 5px; border-bottom: 1px solid #d9e2ec; text-align: left; overflow-wrap: anywhere; }
  .maps-upload-layout { grid-template-columns: minmax(320px, 520px) minmax(0, 1fr); }
  .maps-upload-layout .maps-viewport { height: min(60vh, 650px); min-height: 400px; border-radius: 7px; }
  .maps-upload-form .field small { display: block; margin-top: 7px; color: #697586; line-height: 1.45; }
  .maps-upload-basemap { display: block; margin: 12px 0 5px; font-weight: 700; }
  .maps-upload-existing-layers { display: none; }
  .maps-toast { position: fixed !important; z-index: 1100; top: 70px; right: 25px; width: min(450px, 90vw); }
  .maps-feature-popup { max-height: 260px; overflow: auto; }
  .maps-feature-popup table { border-collapse: collapse; }
  .maps-feature-popup th, .maps-feature-popup td { padding: 3px 6px; border-bottom: 1px solid #ddd; text-align: left; overflow-wrap: anywhere; }
  body.dark-mode .maps-floating-layers { background: rgba(20,30,40,.96); color: #e2e8f0; }
  body.dark-mode .maps-layer-label small, body.dark-mode .maps-page-heading p, body.dark-mode .maps-google-note, body.dark-mode .maps-upload-form .field small { color: #cbd5e1; }
  @media (max-width: 900px) { .maps-layout, .maps-upload-layout { grid-template-columns: 1fr; } .maps-mode-layers .maps-viewport, .maps-upload-layout .maps-viewport { min-height: 380px; height: 55vh; } }
  @media (max-width: 600px) { .maps-full-toolbar { flex-wrap: wrap; } .maps-full-toolbar select { max-width: 100%; flex: 1; } .maps-floating-layers { width: min(260px, 60vw); } .maps-layer-shortcut { display: none !important; } }
</style>
