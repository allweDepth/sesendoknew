<?php

// ==============================
// WHITELIST TABEL YANG DITAMPILKAN
// ==============================
$tables = [

  'peraturan' => [
    'header' => 'Peraturan',
    'meta' => 'Peraturan terkait aplikasi',
    'description' => 'Ketentuan normatif dalam sistem.',
    'icon' => 'teal road'
  ],

  'pengaturan' => [
    'header' => 'Pengaturan',
    'meta' => 'Pengaturan APBD',
    'description' => 'Konfigurasi dasar aplikasi.',
    'icon' => 'teal road'
  ],

  'sumber_dana' => [
    'header' => 'Sumber Dana',
    'meta' => 'Sumber dana kegiatan',
    'description' => 'Klasifikasi, Kodefikasi dan Nomenklatur sumber pendanaan.',
    'icon' => 'purple money'
  ],

  'akun_belanja' => [
    'header' => 'Akun Belanja',
    'meta' => 'Aplikasi Standar Satuan Harga (SSH)',
    'description' => 'Perhitungan kebutuhan biaya tenaga kerja, bahan, dan peralatan.',
    'icon' => 'teal money bill alternate outline'
  ],

  'sub_keg' => [
    'header' => 'Sub Kegiatan',
    'meta' => 'SUB KEGIATAN',
    'description' => 'Data sub kegiatan.',
    'icon' => 'violet users cog'
  ],

];

?>

<div class="ui grid stackable container">
  <div class="column">

    <!-- ========================= -->
    <!-- BACKUP & RESTORE         -->
    <!-- ========================= -->
    <div class="ui placeholder segment">
      <div class="ui two column stackable center aligned grid">
        <div class="ui vertical divider">Or</div>
        <div class="middle aligned row">

          <div class="column">
            <div class="ui icon header">
              <i class="world icon"></i>
              Backup Tabel
            </div>
            <div class="inline">
              <div class="ui buttons">
                <button class="ui blue button"
                        data-action="backup_all">
                        ALL
                </button>
                <div class="or"></div>
                <button class="ui positive button"
                        data-action="backup_proyek">
                        Proyek
                </button>
              </div>
            </div>
          </div>

          <div class="column">
            <div class="ui icon header">
              <i class="world icon"></i>
              Restore Tabel
            </div>
            <div class="inline">
              <div class="ui buttons">
                <button class="ui blue button"
                        data-action="restore_all">
                        All
                </button>
                <div class="or"></div>
                <button class="ui positive button"
                        data-action="restore_proyek">
                        Proyek
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ========================= -->
    <!-- CARD TABEL DINAMIS       -->
    <!-- ========================= -->
    <div class="ui three stackable cards">

      <?php foreach ($tables as $tbl => $meta): ?>

        <div class="card">
          <div class="content">
            <i class="right floated large ui bordered colored <?= $meta['icon'] ?> icon"></i>

            <div class="header"><?= $meta['header'] ?></div>
            <div class="meta"><?= $meta['meta'] ?></div>
            <div class="description"><?= $meta['description'] ?></div>
          </div>

          <div class="extra content">
            <div class="ui three buttons">

              <div class="ui teal button"
                   data-action="delete_all"
                   data-table="<?= $tbl ?>">
                   All
              </div>

              <button class="ui blue button"
                      data-action="delete_proyek"
                      data-table="<?= $tbl ?>">
                      Dokumen
              </button>

              <div class="ui violet button"
                   data-action="reset"
                   data-table="<?= $tbl ?>">
                   Reset
              </div>

            </div>
          </div>
        </div>

      <?php endforeach; ?>

    </div>

  </div>
</div>