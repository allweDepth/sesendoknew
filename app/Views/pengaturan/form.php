<?php
/**
 * =========================================================
 * FORM PENGATURAN SISTEM
 * =========================================================
 * Role Aware:
 *
 * super_admin      → bisa kelola periode RPJMD
 * admin_wilayah    → bisa kelola periode RPJMD wilayahnya
 * admin_opd        → tidak bisa kelola periode (hanya lihat)
 * viewer           → tidak bisa akses pengaturan (idealnya dibatasi di controller)
 *
 * =========================================================
 */

$type = $_SESSION['user']['type_user'] ?? '';
?>

<form class="ui form" method="post" action="/pengaturan/simpan">

  <!-- ======================================================
       INFORMASI DASAR
  ======================================================= -->
  <div class="ui raised segment">
    <h4 class="ui dividing header">Informasi Dasar</h4>

    <div class="two fields">

      <!-- Kode Wilayah (Readonly untuk OPD) -->
      <div class="field">
        <label>Kode Wilayah</label>
        <input type="text"
               name="kd_wilayah"
               value="<?= $_SESSION['user']['kd_wilayah'] ?? '' ?>"
               readonly>
      </div>

      <!-- Tahun Sistem -->
      <div class="field">
        <label>Tahun Sistem</label>
        <input type="number"
               name="tahun"
               value="<?= $_SESSION['user']['tahun'] ?? date('Y'); ?>">
      </div>

    </div>
  </div>


  <!-- ======================================================
       PENGATURAN REFERENSI
  ======================================================= -->
  <div class="ui raised segment">
    <h4 class="ui dividing header">Pengaturan Referensi</h4>

    <div class="four fields">

      <!-- Tahun Renstra -->
      <div class="field">
        <label>Tahun Renstra</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="tahun_renstra">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Tahun Renstra</div>
          <div class="menu">
            <div class="item" data-value="2025-2029">2025-2029</div>
            <div class="item" data-value="2020-2024">2020-2024</div>
          </div>
        </div>
      </div>

      <!-- Aturan Anggaran -->
      <div class="field">
        <label>Aturan Anggaran</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_anggaran">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan Anggaran</div>
          <div class="menu">
            <div class="item" data-value="permendagri_77">Permendagri 77/2020</div>
          </div>
        </div>
      </div>

      <!-- Aturan Organisasi -->
      <div class="field">
        <label>Aturan Organisasi</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_organisasi">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan Organisasi</div>
          <div class="menu">
            <div class="item" data-value="perda">Perda Organisasi</div>
          </div>
        </div>
      </div>

      <!-- Aturan Pengadaan -->
      <div class="field">
        <label>Aturan Pengadaan</label>
        <div class="ui fluid selection dropdown">
          <input type="hidden" name="aturan_pengadaan">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Aturan Pengadaan</div>
          <div class="menu">
            <div class="item" data-value="perpres_12">Perpres 12/2021</div>
          </div>
        </div>
      </div>

    </div>
  </div>


  <!-- ======================================================
       PERIODE RPJMD
       HANYA UNTUK admin_wilayah & super_admin
  ======================================================= -->
  <?php if (in_array($type, ['admin_wilayah','super_admin'])): ?>

  <div class="ui raised segment">
    <h4 class="ui dividing header">Kelola Periode RPJMD</h4>

    <div class="three fields">

      <!-- Periode Mulai -->
      <div class="field">
        <label>Periode Mulai</label>
        <input type="number"
               name="periode_mulai"
               placeholder="2025"
               required>
      </div>

      <!-- Periode Selesai -->
      <div class="field">
        <label>Periode Selesai</label>
        <input type="number"
               name="periode_selesai"
               placeholder="2029"
               required>
      </div>

      <!-- Status Aktif -->
      <div class="field">
        <label>Status Aktif</label>
        <div class="ui toggle checkbox">
          <input type="checkbox" name="status_aktif" value="1">
          <label>Aktifkan periode ini</label>
        </div>
      </div>

    </div>

    <!-- Keterangan -->
    <div class="field">
      <label>Keterangan</label>
      <input type="text"
             name="keterangan"
             placeholder="Contoh: RPJMD 2025-2029">
    </div>

    <!-- Info Sistem -->
    <div class="ui small info message">
      <i class="info circle icon"></i>
      Jika diaktifkan, periode lain di wilayah ini harus dinonaktifkan di backend.
    </div>

  </div>

  <?php endif; ?>


  <!-- ======================================================
       INFORMASI PERIODE (ADMIN OPD)
       HANYA TAMPIL INFO
  ======================================================= -->
  <?php if ($type === 'admin_opd'): ?>

  <div class="ui info message">
    <i class="info circle icon"></i>
    Periode RPJMD ditentukan oleh Admin Wilayah.
    Anda mengikuti periode aktif sistem.
  </div>

  <?php endif; ?>


  <!-- ======================================================
       KONTROL GLOBAL
  ======================================================= -->
  <div class="ui raised segment">
    <h4 class="ui dividing header">Kontrol Global</h4>

    <div class="ui three column grid">

      <div class="column">
        <div class="ui toggle checkbox">
          <input type="checkbox" name="disable">
          <label>Disable Sistem</label>
        </div>
      </div>

      <div class="column">
        <div class="ui toggle checkbox">
          <input type="checkbox" name="kunci">
          <label>Kunci Global</label>
        </div>
      </div>

      <div class="column">
        <div class="ui toggle checkbox">
          <input type="checkbox" name="setujui">
          <label>Setujui Global</label>
        </div>
      </div>

    </div>
  </div>


  <!-- ======================================================
       TOMBOL SIMPAN
  ======================================================= -->
  <div class="ui right aligned segment">

    <button type="submit" class="ui primary button">
      <i class="save icon"></i>
      Simpan
    </button>

    <button type="reset" class="ui button">
      Reset
    </button>

  </div>

</form>