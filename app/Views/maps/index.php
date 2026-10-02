<section class="ui container maps-page" data-can-manage="<?= $canManageLayers ? '1' : '0' ?>">
  <div class="ui stackable grid">
    <div class="five wide column">
      <div class="ui raised segment maps-panel">
        <h2 class="ui header"><i class="map marked alternate icon"></i><span class="content">Maps<div class="sub header">Peta dasar dan layer shapefile per OPD</div></span></h2>
        <div class="field maps-basemap-field">
          <label for="mapsBasemap">Peta dasar</label>
          <select class="ui fluid dropdown" id="mapsBasemap">
            <optgroup label="OpenStreetMap">
              <option value="osm">OpenStreetMap</option>
            </optgroup>
            <optgroup label="Esri">
              <option value="esri-terrain">Esri Terrain / Topographic</option>
              <option value="esri-satellite">Esri Satellite</option>
            </optgroup>
            <optgroup label="Google Maps Platform">
              <option value="google-roadmap" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Roadmap<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
              <option value="google-satellite" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Satellite<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
              <option value="google-hybrid" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Hybrid<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
              <option value="google-terrain" <?= empty($googleMapsApiKey) ? 'disabled' : '' ?>>Google Terrain<?= empty($googleMapsApiKey) ? ' (perlu API key)' : '' ?></option>
            </optgroup>
          </select>
        </div>
        <?php if (empty($googleMapsApiKey)): ?>
          <div class="ui tiny info message">Basemap Google memakai Google Maps Platform dan memerlukan API key di konfigurasi server (<code>GOOGLE_MAPS_API_KEY</code>).</div>
        <?php endif; ?>
        <div class="ui divider"></div>
        <div class="maps-layer-heading"><h3 class="ui small header">Layer OPD</h3><?php if ($canManageLayers): ?><button class="ui mini primary button" id="showAddLayer" type="button"><i class="plus icon"></i>Tambah SHP</button><?php endif; ?></div>
        <?php if ($canManageLayers): ?>
          <form class="ui form maps-upload-form" id="shapefileForm" hidden>
            <div class="field">
              <label for="shapefileInput">File shapefile</label>
              <input id="shapefileInput" type="file" name="files[]" accept=".shp,.shx,.dbf,.prj" multiple required>
              <small>Wajib pilih .shp; sertakan .shx, .dbf, dan .prj dengan nama dasar sama bila ada. Maks. 32 MB per file.</small>
            </div>
            <div class="field"><label for="layerName">Nama layer (opsional)</label><input id="layerName" name="nama_layer" type="text" maxlength="160" placeholder="Nama layer pada peta"></div>
            <button class="ui primary button" type="submit"><i class="cloud upload icon"></i>Unggah ke OPD</button>
            <button class="ui button" id="cancelAddLayer" type="button">Batal</button>
          </form>
        <?php endif; ?>
        <div id="shapefileError" class="ui hidden negative message" role="alert"></div>
        <div id="shapefileStatus" class="ui hidden positive message" role="status"></div>
        <div id="mapLayersList" class="maps-layers-list" aria-live="polite"><div class="ui active inline loader"></div> Memuat layer…</div>
        <div class="maps-attribution-note">Layer SHP tersimpan privat di server dan dikelompokkan berdasarkan wilayah serta OPD.</div>
      </div>
    </div>
    <div class="eleven wide column">
      <div class="ui raised segment maps-canvas-panel">
        <div class="maps-toolbar">
          <div><h3 class="ui header">Peta</h3><span id="mapCoordinates">Pilih peta dasar atau layer untuk mulai.</span></div>
          <div class="ui buttons">
            <button class="ui icon button" id="mapZoomIn" type="button" title="Perbesar" aria-label="Perbesar"><i class="plus icon"></i></button>
            <button class="ui icon button" id="mapZoomOut" type="button" title="Perkecil" aria-label="Perkecil"><i class="minus icon"></i></button>
            <button class="ui icon button" id="mapFit" type="button" title="Sesuaikan layer" aria-label="Sesuaikan layer"><i class="expand icon"></i></button>
          </div>
        </div>
        <div id="mapsViewport" class="maps-viewport" role="application" aria-label="Peta interaktif"></div>
        <div id="googleMapsViewport" class="maps-viewport" role="application" aria-label="Google Maps" hidden></div>
        <div class="maps-hint">Gunakan roda mouse untuk zoom; klik atau sentuh peta untuk memilih fitur.</div>
      </div>
    </div>
  </div>
  <script>window.MAPS_GOOGLE_API_KEY = <?= json_encode((string)($googleMapsApiKey ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
</section>
<style>
  .maps-page { max-width: 1500px !important; }
  .maps-page .maps-panel, .maps-canvas-panel { border-radius: 12px !important; }
  .maps-page .maps-panel { min-height: 440px; }
  .maps-page .field small { display: block; margin-top: 7px; color: #697586; line-height: 1.45; }
  .maps-layer-heading, .maps-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .maps-layer-heading { margin: 12px 0; }
  .maps-layer-heading .header, .maps-toolbar .header { margin: 0 !important; }
  .maps-upload-form { padding: 12px; margin: 8px 0 14px; border: 1px solid #d9e2ec; border-radius: 8px; }
  .maps-layers-list { display: grid; gap: 8px; max-height: 42vh; overflow-y: auto; }
  .maps-layer-row { display: flex; align-items: flex-start; gap: 9px; padding: 10px; border: 1px solid #d9e2ec; border-radius: 8px; }
  .maps-layer-row input { margin-top: 3px; }
  .maps-layer-label { min-width: 0; flex: 1; overflow-wrap: anywhere; }
  .maps-layer-label small { display: block; margin-top: 4px; color: #697586; }
  .maps-attribution-note, .maps-hint, #mapCoordinates { color: #697586; font-size: .88em; }
  .maps-attribution-note { margin-top: 14px; line-height: 1.45; }
  .maps-toolbar { margin-bottom: 12px; }
  .maps-viewport { height: min(70vh, 760px); min-height: 380px; overflow: hidden; border: 1px solid #d9e2ec; border-radius: 9px; background: #eef3f6; }
  .maps-viewport .leaflet-container { width: 100%; height: 100%; font: inherit; }
  #googleMapsViewport[hidden] { display: none; }
  .maps-hint { margin-top: 10px; }
  .maps-feature-popup { max-height: 260px; overflow: auto; }
  .maps-feature-popup table { border-collapse: collapse; }
  .maps-feature-popup th, .maps-feature-popup td { padding: 3px 6px; border-bottom: 1px solid #ddd; text-align: left; overflow-wrap: anywhere; }
  body.dark-mode .maps-page .field small, body.dark-mode .maps-layer-label small, body.dark-mode .maps-attribution-note, body.dark-mode .maps-hint, body.dark-mode #mapCoordinates { color: #cbd5e1; }
  @media (max-width: 767px) { .maps-viewport { min-height: 320px; height: 55vh; } .maps-toolbar { align-items: flex-start; } }
</style>
