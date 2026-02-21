<div class="ui container" style="margin-top:20px">

  <h2 class="ui header">
    <i class="folder open icon"></i>
    <div class="content">
      Buat Naskah Dinas
      <div class="sub header">
        Pilih kelompok naskah sesuai klasifikasi ANRI
      </div>
    </div>
  </h2>

  <div class="ui divider"></div>

  <!-- STEP 1: KELOMPOK -->
  <div class="ui three stackable cards">

    <?php if (!empty($kelompok)) : ?>
      <?php foreach ($kelompok as $k): ?>

        <div class="ui raised link card kelompok-card" data-id="<?= $k['id'] ?>">
          <div class="content">
            <div class="header"><?= htmlspecialchars($k['nama']) ?></div>
            <div class="meta">Klik untuk melihat jenis naskah</div>
          </div>
          <div class="extra content">
            <i class="arrow right icon"></i> Pilih
          </div>
        </div>

      <?php endforeach; ?>
    <?php else: ?>
      <div class="ui message">
        Data kelompok belum tersedia.
      </div>
    <?php endif; ?>

  </div>

  <!-- STEP 2: JENIS -->
  <div id="jenis-container" class="ui segment hidden" style="margin-top:30px">
    <h4 class="ui dividing header">Pilih Jenis Naskah</h4>
    <div class="ui relaxed divided list" id="jenis-list"></div>
  </div>

  <!-- STEP 3: FORM DINAMIS -->
  <div id="form-container" class="ui segment hidden" style="margin-top:30px"></div>

</div>