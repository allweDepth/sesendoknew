<?php
$mode = in_array($mode ?? '', ['map', 'layers', 'upload'], true) ? $mode : 'map';
$pageTitle = ['map' => 'Peta', 'layers' => 'Atur Layer SHP', 'upload' => 'Tambah/Unggah SHP'][$mode];
?>
<section class="maps-page maps-mode-<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" data-mode="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" data-can-manage="<?= $canManageLayers ? '1' : '0' ?>">
  <nav class="ui secondary pointing menu maps-navigation" aria-label="Menu Maps">
    <?php foreach (['map' => ['/maps', 'map outline', 'Peta'], 'layers' => ['/maps/layers', 'layer group', 'Atur Layer SHP'], 'upload' => ['/maps/add', 'cloud upload', 'Tambah/Unggah SHP']] as $key => [$url, $icon, $label]): ?>
      <a class="<?= $mode === $key ? 'active ' : '' ?>item" href="<?= $url ?>" data-spa="server" data-title="Maps/<?= $label ?>" <?= $mode === $key ? 'aria-current="page"' : '' ?>><i class="<?= $icon ?> icon"></i><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
  <?php if ($mode === 'map'): ?>
    <div class="ui segment maps-full-toolbar">
      <label for="mapsBasemap">Peta dasar</label>
      <select id="mapsBasemap" class="ui search dropdown" aria-label="Pilih peta dasar">
        <option value="osm">OpenStreetMap</option>
        <option value="open-topo">OpenTopoMap</option>
        <option value="carto-positron">CARTO Positron (terang)</option>
        <option value="carto-dark">CARTO Dark Matter (gelap)</option>
        <option value="esri-street">Esri World Street Map</option>
        <option value="esri-terrain">Esri Terrain / Topographic</option>
        <option value="esri-satellite">Esri World Imagery (Satellite)</option>
        <option value="google-roadmap">Google Roadmap</option>
        <option value="google-satellite">Google Satellite</option>
        <option value="google-hybrid">Google Hybrid</option>
        <option value="google-terrain">Google Terrain</option>
      </select>
      <span id="mapCoordinates">Peta siap · pilih layer pada panel Layer</span>
      <span class="maps-toolbar-spacer"></span>
      <button class="ui icon button" id="mapZoomIn" type="button" title="Perbesar" aria-label="Perbesar"><i class="plus icon"></i></button>
      <button class="ui icon button" id="mapZoomOut" type="button" title="Perkecil" aria-label="Perkecil"><i class="minus icon"></i></button>
      <button class="ui icon button" id="mapFit" type="button" title="Sesuaikan layer" aria-label="Sesuaikan layer"><i class="expand icon"></i></button>
      <a class="ui basic button maps-layer-shortcut" href="/maps/layers" data-spa="server" data-title="Maps/Atur Layer SHP"><i class="layers icon"></i>Layer</a>
    </div>
    <div class="maps-full-shell">
      <div id="mapsViewport" class="maps-viewport" role="application" aria-label="Peta interaktif"></div>
      <section id="mapsFeatureProperties" class="ui segment maps-feature-properties" aria-live="polite" hidden>
        <div class="maps-feature-properties-heading"><strong id="mapsFeatureTitle">Properti feature</strong><button type="button" id="closeMapsFeatureProperties" class="ui mini basic icon button" aria-label="Tutup properti feature"><i class="close icon"></i></button></div>
        <div id="mapsFeatureFields" class="maps-feature-properties-fields"></div>
      </section>
      <aside class="ui raised segment maps-floating-layers">
        <h3 class="ui small header"><i class="layers icon"></i>Layer OPD</h3>
        <div id="mapLayersList" class="maps-layers-list"><div class="ui active inline loader"></div> Memuat layer…</div>
      </aside>
    </div>
    <div id="shapefileError" class="ui hidden negative message maps-toast" role="alert"></div>
    <div id="shapefileStatus" class="ui hidden positive message maps-toast" role="status"></div>
  <?php elseif ($mode === 'layers'): ?>
    <div class="maps-page-heading"><div><h1 class="ui header"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1><p>Atur visibilitas, simbologi, label, dan periksa field atribut shapefile.</p></div></div>
    <div class="maps-layout">
      <aside class="ui segment form maps-panel">
        <div class="maps-basemap-field"><label for="mapsBasemap">Peta dasar</label><select id="mapsBasemap" class="ui fluid search dropdown">
          <option value="osm">OpenStreetMap</option><option value="open-topo">OpenTopoMap</option><option value="carto-positron">CARTO Positron (terang)</option><option value="carto-dark">CARTO Dark Matter (gelap)</option><option value="esri-street">Esri World Street Map</option><option value="esri-terrain">Esri Terrain / Topographic</option><option value="esri-satellite">Esri World Imagery (Satellite)</option>
          <option value="google-roadmap">Google Roadmap</option>
          <option value="google-satellite">Google Satellite</option>
          <option value="google-hybrid">Google Hybrid</option>
          <option value="google-terrain">Google Terrain</option>
        </select></div>
        <h2 class="ui small header">Layer wilayah/OPD</h2>
        <div id="mapLayersList" class="maps-layers-list"><div class="ui active inline loader"></div> Memuat layer…</div>
        <div id="mapsLayerSettings" class="maps-layer-settings" hidden>
          <div class="ui divider"></div>
          <h3 class="ui small dividing header" id="mapsSelectedLayerName"></h3>
          <div class="field"><label for="mapsRenderer">Metode simbologi</label><select id="mapsRenderer" class="ui fluid search dropdown"><option value="simple">Simbol tunggal</option><option value="categorized">Kategori berdasarkan field</option></select></div>
          <div id="mapsCategorySettings" hidden>
            <div class="field"><label for="mapsCategoryField">Field kategori</label><select id="mapsCategoryField" class="ui fluid search dropdown"><option value="">Pilih field</option></select></div>
            <div class="maps-category-toolbar">
              <button class="ui small primary button" id="classifyMapsCategories" type="button">Klasifikasikan nilai unik</button>
              <button class="ui small basic button" id="toggleMapsCategories" type="button" aria-expanded="false">Tampilkan tabel</button>
            </div>
            <div class="field maps-category-search-field"><label for="mapsCategorySearch">Cari kategori</label><input id="mapsCategorySearch" type="search" placeholder="Ketik nilai untuk memfilter" autocomplete="off"></div>
            <div id="mapsCategoryCount" class="maps-category-count" aria-live="polite"></div>
            <div id="mapsCategoriesLegend" class="maps-categories-legend" hidden></div>
          </div>
          <div class="field maps-setting-toggle"><div class="ui toggle checkbox"><input id="mapsShowLabels" type="checkbox"><label for="mapsShowLabels">Tampilkan label</label></div></div>
          <div class="field"><label for="mapsLabelField">Field label</label><select id="mapsLabelField" class="ui fluid search dropdown"><option value="">Tidak ada label</option></select></div>
          <div id="mapsLabelStyle" class="maps-label-style">
            <div class="field"><label for="mapsLabelFont">Jenis font label</label><select id="mapsLabelFont" class="ui fluid search dropdown"><option value="Arial, sans-serif">Arial</option><option value="Verdana, sans-serif">Verdana</option><option value="Georgia, serif">Georgia</option><option value="monospace">Monospace</option></select></div>
            <div class="maps-style-grid">
              <div class="field"><label for="mapsLabelColor">Warna font</label><input id="mapsLabelColor" type="color" value="#1f2937"></div>
              <div class="field"><label for="mapsLabelSize">Ukuran font</label><input id="mapsLabelSize" type="number" min="8" max="24" step="1" value="12"></div>
            </div>
            <div class="field maps-setting-toggle"><div class="ui checkbox"><input id="mapsLabelBold" type="checkbox"><label for="mapsLabelBold">Tebalkan label</label></div></div>
          </div>
          <div class="maps-style-grid">
            <div class="field"><label for="mapsLineColor">Warna garis/titik</label><input id="mapsLineColor" type="color" value="#138a72"></div>
            <div class="field"><label for="mapsFillColor">Warna isi</label><input id="mapsFillColor" type="color" value="#138a72"></div>
            <div class="field"><label for="mapsFillOpacity">Opasitas isi</label><input id="mapsFillOpacity" type="range" min="0" max="1" step=".05" value=".3"></div>
            <div class="field"><label for="mapsLineStyle">Jenis garis</label><select id="mapsLineStyle" class="ui fluid search dropdown"><option value="solid">Utuh</option><option value="dash">Putus-putus</option><option value="dot">Titik-titik</option><option value="dash-dot">Garis-titik</option></select></div>
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
      </div>
    </div>
  <?php else: ?>
    <div class="maps-page-heading"><div><h1 class="ui header"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1><p>Unggah shapefile dan komponen pendamping ke ruang data wilayah/OPD aktif.</p></div></div>
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
      <section class="ui segment form maps-preview-panel">
        <div class="maps-preview-toolbar"><strong>Layer yang sudah ada</strong><span id="mapCoordinates">Pilih layer untuk pratinjau.</span></div>
        <div id="mapsViewport" class="maps-viewport" role="application" aria-label="Pratinjau peta"></div>
        <label class="maps-upload-basemap" for="mapsBasemap">Peta dasar</label>
        <select id="mapsBasemap" class="ui fluid search dropdown"><option value="osm">OpenStreetMap</option><option value="open-topo">OpenTopoMap</option><option value="carto-positron">CARTO Positron (terang)</option><option value="carto-dark">CARTO Dark Matter (gelap)</option><option value="esri-street">Esri World Street Map</option><option value="esri-terrain">Esri Terrain</option><option value="esri-satellite">Esri World Imagery (Satellite)</option>
          <option value="google-roadmap">Google Roadmap</option>
          <option value="google-satellite">Google Satellite</option>
          <option value="google-hybrid">Google Hybrid</option>
          <option value="google-terrain">Google Terrain</option>
        </select>
        <h3 class="ui small dividing header">Layer wilayah/OPD</h3><div id="mapLayersList" class="maps-layers-list maps-upload-existing-layers"></div>
      </section>
    </div>
  <?php endif; ?>
</section>
<style>
  .maps-page { width: 100%; max-width: none !important; height: 100%; min-height: 0; padding: 0 !important; }
  #mainContext .content-scroll:has(.maps-mode-map) { display: flex; flex-direction: column; min-height: 0; overflow: hidden; }
  #mainContext .content-scroll:has(.maps-mode-map) #main-content { display: flex; flex: 1 1 auto; width: 100%; min-height: 0; }
  .maps-mode-map { display: flex; flex: 1 1 auto; flex-direction: column; gap: 8px; height: auto; min-height: 0; }
  .maps-full-toolbar { margin: 0 !important; display: flex; align-items: center; gap: 8px; min-height: 48px; padding: 6px 10px; background: var(--surface, #fff); border: 1px solid #d9e2ec; border-radius: 8px; }
  .maps-full-toolbar label { white-space: nowrap; font-weight: 700; }
  .maps-toolbar-spacer { flex: 1; }
  .maps-full-toolbar .button { margin: 0 !important; }
  .maps-google-note { color: #697586; font-size: .85em; }
  .maps-full-shell { position: relative; flex: 1; min-height: 0; overflow: hidden; border: 1px solid #d9e2ec; border-radius: 9px; }
  .maps-viewport { width: 100%; height: 100%; min-height: 330px; overflow: hidden; background: #eef3f6; }
  .maps-mode-map .maps-full-shell > .maps-viewport { position: absolute; inset: 0; width: 100%; height: 100%; min-height: 0; }
  .maps-viewport .leaflet-container { width: 100%; height: 100%; font: inherit; }
  #googleMapsViewport[hidden] { display: none; }
  .maps-page .maps-floating-layers { margin: 0 !important; position: absolute; z-index: 500; top: 12px; right: 12px; width: min(300px, 38vw); max-height: calc(100% - 24px); padding: 13px; overflow: auto; background: rgba(255,255,255,.96); border: 1px solid #d9e2ec; border-radius: 9px; box-shadow: 0 3px 18px rgba(0,0,0,.18); }
  .maps-page .maps-feature-properties { margin: 0 !important; position: absolute; z-index: 510; left: 12px; bottom: 12px; width: min(390px, calc(100% - 340px)); max-height: min(48%, 420px); padding: 12px; overflow: auto; background: rgba(255,255,255,.97); border: 1px solid #b9c8d8; border-radius: 9px; box-shadow: 0 3px 18px rgba(0,0,0,.2); }
  .maps-feature-properties-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
  .maps-feature-properties-fields { overflow-wrap: anywhere; }
  .maps-feature-properties-fields table { width: 100%; border-collapse: collapse; }
  .maps-feature-properties-fields th, .maps-feature-properties-fields td { padding: 5px 7px; border-bottom: 1px solid #d9e2ec; text-align: left; vertical-align: top; }
  .maps-feature-properties-fields th { width: 40%; }
  .maps-floating-layers h3 { margin: 0 0 10px; font-size: 1rem; }
  .maps-layers-list { display: grid; gap: 7px; max-height: 38vh; overflow-y: auto; }
  .maps-layer-row { display: flex; align-items: flex-start; gap: 8px; padding: 8px; border: 1px solid #d9e2ec; border-radius: 7px; }
  .maps-layer-row .ui.checkbox { flex: 0 0 auto; margin-top: 3px; }
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
  .maps-category-toolbar { display: flex; flex-wrap: wrap; gap: 6px; margin: 8px 0; }
  .maps-category-toolbar .button { margin: 0 !important; }
  .maps-category-search-field { margin: 8px 0 4px !important; }
  .maps-category-count { margin: 4px 0 7px; color: #64748b; font-size: .85rem; }
  .maps-categories-legend { max-height: 310px; overflow: auto; border: 1px solid #d9e2ec; border-radius: 7px; }
  .maps-categories-legend table { width: 100%; border-collapse: collapse; font-size: .86rem; }
  .maps-categories-legend th, .maps-categories-legend td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; text-align: left; }
  .maps-categories-legend thead { position: sticky; top: 0; z-index: 1; background: #f8fafc; }
  .maps-categories-legend input[type=color] { width: 38px; height: 30px; padding: 1px; }
  .maps-categories-legend input[type=number] { width: 62px; min-width: 0; padding: 5px; }
  .maps-categories-legend select { min-width: 105px; padding: 5px; }
  .maps-category-value { overflow-wrap: anywhere; }
  body.dark-mode .maps-categories-legend { border-color: #475569; }
  body.dark-mode .maps-categories-legend thead { background: #1e293b; }
  body.dark-mode .maps-categories-legend th, body.dark-mode .maps-categories-legend td { border-color: #334155; }
  body.dark-mode .maps-category-count { color: #cbd5e1; }
  .maps-setting-toggle { display: flex; align-items: center; gap: 7px; margin: 12px 0; }
  .maps-style-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
  .maps-style-grid .field { margin: 0 !important; }
  .maps-style-grid input[type=color] { width: 100%; height: 34px; padding: 2px; }
  .maps-label-style[hidden] { display: none !important; }
  .maps-dbf-fields { max-height: 250px; overflow: auto; font-size: .88em; }
  .maps-dbf-fields table { width: 100%; border-collapse: collapse; }
  .maps-dbf-fields th, .maps-dbf-fields td { padding: 5px; border-bottom: 1px solid #d9e2ec; text-align: left; overflow-wrap: anywhere; }
  .maps-upload-layout { grid-template-columns: minmax(320px, 520px) minmax(0, 1fr); }
  .maps-upload-layout .maps-viewport { height: min(60vh, 650px); min-height: 400px; border-radius: 7px; }
  .maps-upload-form .field small { display: block; margin-top: 7px; color: #697586; line-height: 1.45; }
  .maps-upload-basemap { display: block; margin: 12px 0 5px; font-weight: 700; }
  .maps-upload-existing-layers { margin-top: 14px; }
  .maps-toast { position: fixed !important; z-index: 1100; top: 70px; right: 25px; width: min(450px, 90vw); }
  .maps-feature-popup { max-height: 260px; overflow: auto; }
  .maps-feature-popup table { border-collapse: collapse; }
  .maps-feature-popup th, .maps-feature-popup td { padding: 3px 6px; border-bottom: 1px solid #ddd; text-align: left; overflow-wrap: anywhere; }
  body.dark-mode .maps-floating-layers { background: rgba(20,30,40,.96); color: #e2e8f0; }
  body.dark-mode .maps-layer-label small, body.dark-mode .maps-page-heading p, body.dark-mode .maps-google-note, body.dark-mode .maps-upload-form .field small { color: #cbd5e1; }
  @media (max-width: 900px) { .maps-layout, .maps-upload-layout { grid-template-columns: 1fr; } .maps-mode-layers .maps-viewport, .maps-upload-layout .maps-viewport { min-height: 380px; height: 55vh; } }
  body.dark-mode .maps-feature-properties { background: rgba(20,30,40,.97); color: #e2e8f0; border-color: #475569; }
  @media (max-width: 600px) { .maps-full-toolbar { flex-wrap: wrap; } .maps-full-toolbar select { max-width: 100%; flex: 1; } .maps-page .maps-floating-layers { width: min(260px, 60vw); } .maps-page .maps-feature-properties { left: 8px; bottom: 8px; width: calc(100% - 16px); max-height: 45%; padding: 8px; font-size: .85rem; } .maps-layer-shortcut { display: none !important; } }
  .maps-page [hidden] { display: none !important; }
  .maps-navigation.ui.menu { flex: 0 0 auto; margin: 0 0 12px; flex-wrap: wrap; }
  .maps-mode-map .maps-navigation.ui.menu { margin-bottom: 0; }
  .maps-full-toolbar > .ui.dropdown { min-width: 210px; max-width: 290px; }
  #mapCoordinates { color: #64748b; font-size: .9em; overflow-wrap: anywhere; }
  .maps-preview-panel { min-width: 0; }
  .maps-preview-toolbar { flex-wrap: wrap; }
  .maps-layer-row:has(input:checked) { border-color: #2185d0; background: rgba(33,133,208,.05); }
  .maps-panel.ui.form .field > label { margin-bottom: .5em; }
  .maps-panel input[type=range] { width: 100%; accent-color: #2185d0; }
  .maps-panel input[type=color] { border: 1px solid rgba(34,36,38,.15); border-radius: .28571429rem; cursor: pointer; }
  .maps-dbf-fields .ui.table, .maps-categories-legend .ui.table, .maps-feature-properties .ui.table { margin: 0; }
  body.dark-mode #mapCoordinates { color: #cbd5e1; }
  @media (max-width: 600px) {
    .maps-navigation.ui.menu .item { padding: .75em; font-size: .85em; }
    .maps-full-toolbar > .ui.dropdown { min-width: 0; max-width: none; flex: 1 1 220px; }
    .maps-full-toolbar #mapCoordinates { flex: 1 1 100%; order: 2; }
    .maps-page .maps-page .maps-floating-layers { width: min(240px, 65vw); max-height: 38%; }
    .maps-full-shell { min-height: 380px; }
    .maps-layout { min-height: 0; }
    .maps-page .maps-feature-properties { max-height: 42%; }
  }
</style>
