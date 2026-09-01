<style>.naskah-create-hero{background:linear-gradient(135deg,#17324d,#2185d0);color:#fff;border-radius:16px;padding:22px;margin-bottom:18px}.naskah-create-hero .header,.naskah-create-hero .sub.header{color:#fff!important}.naskah-steps{margin:18px 0!important}.kelompok-card{border-top:4px solid #2185d0!important;transition:.2s transform,.2s box-shadow!important}.kelompok-card:hover{transform:translateY(-3px)}#jenis-container{border-radius:14px!important}.jenis-search{margin-bottom:14px}</style>
<div class="ui container" style="margin-top:20px">

  <div class="naskah-create-hero"><h2 class="ui header">
    <i class="folder open icon"></i>
    <div class="content">
      Buat Naskah Dinas
      <div class="sub header">
        Pilih kelompok naskah sesuai klasifikasi ANRI
      </div>
    </div>
  </h2><p>Pilih kelompok, tentukan jenis dokumen, lengkapi field wajib, lalu simpan. Data dapat diedit kembali dari daftar naskah.</p></div>

  <div class="ui three tiny ordered steps naskah-steps"><div class="active step" data-naskah-step="1"><div class="content"><div class="title">Kelompok</div></div></div><div class="disabled step" data-naskah-step="2"><div class="content"><div class="title">Jenis Naskah</div></div></div><div class="disabled step" data-naskah-step="3"><div class="content"><div class="title">Isi & Simpan</div></div></div></div>

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
    <div class="ui fluid icon input jenis-search"><input id="jenisNaskahSearch" placeholder="Cari nama atau kategori naskah..."><i class="search icon"></i></div>
    <div class="ui relaxed divided list" id="jenis-list"></div>
  </div>
  <div id="form-container"></div> <!-- FIX container form -->

</div>
