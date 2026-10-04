<?php $mapsSearchConfig = require __DIR__ . '/../../../config/maps.php'; ?>
<div class="maps-location-search" data-endpoint="<?= htmlspecialchars($mapsSearchConfig['search_endpoint'], ENT_QUOTES, 'UTF-8') ?>">
  <div id="mapsLocationSearch" class="ui search">
    <div class="ui action input">
      <input class="prompt" type="search" maxlength="160" placeholder="Cari lokasi atau alamat…" aria-label="Cari lokasi atau alamat" autocomplete="off" aria-describedby="mapsSearchStatus">
      <button class="ui primary icon button maps-search-submit" type="button" aria-label="Cari lokasi" title="Cari lokasi"><i class="search icon"></i></button>
    </div>
    <div class="results"></div>
  </div>
  <div class="maps-search-caption"><span id="mapsSearchStatus" role="status" aria-live="polite">Enter atau tombol cari</span> · <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">© OpenStreetMap</a></div>
</div>
