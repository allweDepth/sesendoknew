 <?php
    $tables = [
        'akun_neo' => ['header' => 'Akun Neo', 'meta' => 'Tabel akun', 'description' => 'Data akun sistem.', 'icon' => 'teal database'],
        'anggaran_program_renstra_neo' => ['header' => 'Anggaran Program Renstra', 'meta' => 'Renstra', 'description' => 'Anggaran program renstra.', 'icon' => 'blue chart bar'],
        'asb_akun_map' => ['header' => 'ASB Akun Map', 'meta' => 'ASB', 'description' => 'Mapping akun ASB.', 'icon' => 'purple linkify'],
        'asb_neo' => ['header' => 'ASB Neo', 'meta' => 'ASB', 'description' => 'Data ASB.', 'icon' => 'purple cubes'],
        'aset_neo' => ['header' => 'Aset Neo', 'meta' => 'Aset', 'description' => 'Data aset.', 'icon' => 'brown building'],
        'berita_neo' => ['header' => 'Berita Neo', 'meta' => 'Berita', 'description' => 'Data berita.', 'icon' => 'grey newspaper'],
        'bidang' => ['header' => 'Bidang', 'meta' => 'Organisasi', 'description' => 'Data bidang.', 'icon' => 'teal sitemap'],
        'cache_schema_naskah' => ['header' => 'Cache Schema Naskah', 'meta' => 'Cache', 'description' => 'Cache schema naskah.', 'icon' => 'grey database'],
        'daftar_paket_neo' => ['header' => 'Daftar Paket', 'meta' => 'Paket', 'description' => 'Data daftar paket.', 'icon' => 'orange boxes'],
        'daftar_realisasi_neo' => ['header' => 'Daftar Realisasi', 'meta' => 'Realisasi', 'description' => 'Data realisasi.', 'icon' => 'green check circle'],
        'daftar_uraian_paket' => ['header' => 'Daftar Uraian Paket', 'meta' => 'Paket', 'description' => 'Uraian paket.', 'icon' => 'orange list'],
        'db_asn_pemda_neo' => ['header' => 'ASN Pemda', 'meta' => 'ASN', 'description' => 'Database ASN Pemda.', 'icon' => 'blue users'],
        'dpa_neo' => ['header' => 'DPA Neo', 'meta' => 'DPA', 'description' => 'Data DPA.', 'icon' => 'teal file alternate'],
        'dppa_neo' => ['header' => 'DPPA Neo', 'meta' => 'DPPA', 'description' => 'Data DPPA.', 'icon' => 'teal file'],
        'hspk_akun_map' => ['header' => 'HSPK Akun Map', 'meta' => 'HSPK', 'description' => 'Mapping akun HSPK.', 'icon' => 'purple linkify'],
        'hspk_neo' => ['header' => 'HSPK Neo', 'meta' => 'HSPK', 'description' => 'Data HSPK.', 'icon' => 'purple cubes'],
        'import_logs' => ['header' => 'Import Logs', 'meta' => 'Logs', 'description' => 'Log import data.', 'icon' => 'grey history'],
        'indikator_program_renstra_neo' => ['header' => 'Indikator Program Renstra', 'meta' => 'Renstra', 'description' => 'Indikator program renstra.', 'icon' => 'blue bullseye'],
        'indikator_sasaran_renstra_neo' => ['header' => 'Indikator Sasaran Renstra', 'meta' => 'Renstra', 'description' => 'Indikator sasaran.', 'icon' => 'blue bullseye'],
        'kd_wilayah_neo' => ['header' => 'KD Wilayah', 'meta' => 'Wilayah', 'description' => 'Kode wilayah.', 'icon' => 'teal map'],
        'kegiatan' => ['header' => 'Kegiatan', 'meta' => 'Program', 'description' => 'Data kegiatan.', 'icon' => 'green tasks'],
        'kegiatan_renstra_neo' => ['header' => 'Kegiatan Renstra', 'meta' => 'Renstra', 'description' => 'Kegiatan renstra.', 'icon' => 'blue tasks'],
        'log_activity' => ['header' => 'Log Activity', 'meta' => 'Logs', 'description' => 'Log aktivitas sistem.', 'icon' => 'grey history'],
        'mapping_aset_akun' => ['header' => 'Mapping Aset Akun', 'meta' => 'Aset', 'description' => 'Mapping aset ke akun.', 'icon' => 'purple linkify'],
        'misi_renstra_neo' => ['header' => 'Misi Renstra', 'meta' => 'Renstra', 'description' => 'Data misi renstra.', 'icon' => 'blue flag'],
        'naskah_dinas_neo' => ['header' => 'Naskah Dinas', 'meta' => 'Naskah', 'description' => 'Data naskah dinas.', 'icon' => 'grey file'],
        'organisasi_neo' => ['header' => 'Organisasi', 'meta' => 'Organisasi', 'description' => 'Data organisasi.', 'icon' => 'teal sitemap'],
        'pengaturan_neo' => ['header' => 'Pengaturan Neo', 'meta' => 'Konfigurasi', 'description' => 'Pengaturan aplikasi.', 'icon' => 'teal cogs'],
        'peraturan_neo' => ['header' => 'Peraturan Neo', 'meta' => 'Regulasi', 'description' => 'Data peraturan.', 'icon' => 'teal balance scale'],
        'periode_rpjmd' => ['header' => 'Periode RPJMD', 'meta' => 'RPJMD', 'description' => 'Periode RPJMD.', 'icon' => 'blue calendar'],
        'program' => ['header' => 'Program', 'meta' => 'Program', 'description' => 'Data program.', 'icon' => 'green clipboard'],
        'program_renstra_neo' => ['header' => 'Program Renstra', 'meta' => 'Renstra', 'description' => 'Program renstra.', 'icon' => 'blue clipboard'],
        'rab_paket_neo' => ['header' => 'RAB Paket', 'meta' => 'RAB', 'description' => 'RAB paket.', 'icon' => 'orange calculator'],
        'ref_jenis_naskah' => ['header' => 'Ref Jenis Naskah', 'meta' => 'Referensi', 'description' => 'Referensi jenis naskah.', 'icon' => 'grey book'],
        'ref_jenis_naskah_dinas' => ['header' => 'Ref Jenis Naskah Dinas', 'meta' => 'Referensi', 'description' => 'Referensi naskah dinas.', 'icon' => 'grey book'],
        'ref_kelompok_naskah' => ['header' => 'Ref Kelompok Naskah', 'meta' => 'Referensi', 'description' => 'Kelompok naskah.', 'icon' => 'grey folder'],
        'ref_klasifikasi_keamanan' => ['header' => 'Ref Klasifikasi Keamanan', 'meta' => 'Referensi', 'description' => 'Klasifikasi keamanan.', 'icon' => 'grey shield'],
        'ref_template_naskah' => ['header' => 'Ref Template Naskah', 'meta' => 'Template', 'description' => 'Template naskah.', 'icon' => 'grey file'],
        'register_naskah_dinas' => ['header' => 'Register Naskah Dinas', 'meta' => 'Register', 'description' => 'Register naskah dinas.', 'icon' => 'grey clipboard'],
        'rekanan_neo' => ['header' => 'Rekanan', 'meta' => 'Mitra', 'description' => 'Data rekanan.', 'icon' => 'brown handshake'],
        'renja_neo' => ['header' => 'Renja', 'meta' => 'Renja', 'description' => 'Data renja.', 'icon' => 'blue calendar check'],
        'renja_p_neo' => ['header' => 'Renja Perubahan', 'meta' => 'Renja', 'description' => 'Renja perubahan.', 'icon' => 'blue calendar alternate'],
        'renstra_neo' => ['header' => 'Renstra', 'meta' => 'Renstra', 'description' => 'Data renstra.', 'icon' => 'blue book'],
        'sasaran_renstra_neo' => ['header' => 'Sasaran Renstra', 'meta' => 'Renstra', 'description' => 'Sasaran renstra.', 'icon' => 'blue bullseye'],
        'satuan_neo' => ['header' => 'Satuan', 'meta' => 'Satuan', 'description' => 'Data satuan.', 'icon' => 'teal ruler'],
        'sbu_akun_map' => ['header' => 'SBU Akun Map', 'meta' => 'SBU', 'description' => 'Mapping akun SBU.', 'icon' => 'purple linkify'],
        'sbu_neo' => ['header' => 'SBU Neo', 'meta' => 'SBU', 'description' => 'Data SBU.', 'icon' => 'purple cubes'],
        'sk_asn_neo' => ['header' => 'SK ASN', 'meta' => 'ASN', 'description' => 'Data SK ASN.', 'icon' => 'blue file'],
        'ssh_akun_map' => ['header' => 'SSH Akun Map', 'meta' => 'SSH', 'description' => 'Mapping akun SSH.', 'icon' => 'purple linkify'],
        'ssh_neo' => ['header' => 'SSH Neo', 'meta' => 'SSH', 'description' => 'Data SSH.', 'icon' => 'purple cubes'],
        'sub_kegiatan' => ['header' => 'Sub Kegiatan', 'meta' => 'Sub Kegiatan', 'description' => 'Data sub kegiatan.', 'icon' => 'violet tasks'],
        'sub_kegiatan_neo' => ['header' => 'Sub Kegiatan Neo', 'meta' => 'Sub Kegiatan', 'description' => 'Sub kegiatan neo.', 'icon' => 'violet tasks'],
        'sub_kegiatan_renstra_neo' => ['header' => 'Sub Kegiatan Renstra', 'meta' => 'Renstra', 'description' => 'Sub kegiatan renstra.', 'icon' => 'blue tasks'],
        'sub_keg_dpa_neo' => ['header' => 'Sub Kegiatan DPA', 'meta' => 'DPA', 'description' => 'Sub kegiatan DPA.', 'icon' => 'teal file'],
        'sub_keg_renja_neo' => ['header' => 'Sub Kegiatan Renja', 'meta' => 'Renja', 'description' => 'Sub kegiatan renja.', 'icon' => 'blue file'],
        'sumber_dana_neo' => ['header' => 'Sumber Dana', 'meta' => 'Pendanaan', 'description' => 'Sumber dana kegiatan.', 'icon' => 'green money'],
        'trx_naskah_dinas' => ['header' => 'Transaksi Naskah Dinas', 'meta' => 'Transaksi', 'description' => 'Transaksi naskah dinas.', 'icon' => 'grey exchange'],
        'trx_naskah_meta' => ['header' => 'Transaksi Naskah Meta', 'meta' => 'Transaksi', 'description' => 'Meta naskah dinas.', 'icon' => 'grey exchange'],
        'trx_naskah_struktur' => ['header' => 'Transaksi Naskah Struktur', 'meta' => 'Transaksi', 'description' => 'Struktur naskah dinas.', 'icon' => 'grey exchange'],
        'trx_nomor_counter' => ['header' => 'Nomor Counter', 'meta' => 'Counter', 'description' => 'Counter nomor otomatis.', 'icon' => 'grey hashtag'],
        'tujuan_renstra_neo' => ['header' => 'Tujuan Renstra', 'meta' => 'Renstra', 'description' => 'Tujuan renstra.', 'icon' => 'blue flag'],
        'urusan' => ['header' => 'Urusan', 'meta' => 'Urusan', 'description' => 'Data urusan pemerintahan.', 'icon' => 'teal building'],
        'users' => ['header' => 'Users', 'meta' => 'User', 'description' => 'Data pengguna sistem.', 'icon' => 'blue users'],
        'wallchat' => ['header' => 'Wallchat', 'meta' => 'Chat', 'description' => 'Wallchat internal.', 'icon' => 'grey comments'],
        'wilayah_neo' => ['header' => 'Wilayah', 'meta' => 'Wilayah', 'description' => 'Data wilayah.', 'icon' => 'teal map'],

    ];
    ?>
 <div class="ui container">
     <div class="ui grid">
         <div class="column" data-module="reset_tabel">

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
                                     <button class="ui blue button" data-action="backup_all">
                                         ALL
                                     </button>
                                     <div class="or"></div>
                                     <button class="ui positive button" data-action="backup_proyek">
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
                                     <button class="ui blue button" data-action="restore_all">
                                         All
                                     </button>
                                     <div class="or"></div>
                                     <button class="ui positive button" data-action="restore_proyek">
                                         Proyek
                                     </button>
                                 </div>
                             </div>
                         </div>

                     </div>
                 </div>
             </div>
             <h3 class="ui dividing header">Master & Referensi</h3>
             <div class="ui three stackable cards">
                 <?php
                    $group_master = array_filter($tables, function ($k) {
                        return str_starts_with($k, 'ref_')
                            || str_starts_with($k, 'ssh_')
                            || str_starts_with($k, 'sbu_')
                            || str_starts_with($k, 'asb_')
                            || str_starts_with($k, 'hspk_')
                            || $k === 'urusan'
                            || $k === 'bidang'
                            || $k === 'users';
                    }, ARRAY_FILTER_USE_KEY);

                    foreach ($group_master as $tbl => $meta):
                    ?>
                     <!-- CARD TETAP SAMA -->
                     <div class="card">
                         <div class="content">
                             <i class="right floated large ui bordered colored <?= $meta['icon'] ?> icon"></i>
                             <div class="header"><?= $meta['header'] ?></div>
                             <div class="meta"><?= $meta['meta'] ?></div>
                             <div class="description"><?= $meta['description'] ?></div>
                         </div>
                         <div class="extra content">
                             <div class="ui three buttons">
                                 <div class="ui teal button" data-action="delete_all" data-table="<?= $tbl ?>">All</div>
                                 <button class="ui blue button" data-action="delete_proyek" data-table="<?= $tbl ?>">Dokumen</button>
                                 <div class="ui violet button" data-action="reset" data-table="<?= $tbl ?>">Reset</div>
                             </div>
                         </div>
                     </div>
                 <?php endforeach; ?>
             </div>
             <h3 class="ui dividing header">Renstra & Perencanaan</h3>
             <div class="ui three stackable cards">
                 <?php
                    $group_renstra = array_filter($tables, function ($k) {
                        return str_contains($k, 'renstra')
                            || str_contains($k, 'renja')
                            || str_contains($k, 'rpjmd')
                            || str_contains($k, 'program')
                            || str_contains($k, 'kegiatan')
                            || str_contains($k, 'sasaran')
                            || str_contains($k, 'tujuan');
                    }, ARRAY_FILTER_USE_KEY);

                    foreach ($group_renstra as $tbl => $meta):
                    ?>
                     <!-- CARD SAMA -->
                     <div class="card">
                         <div class="content">
                             <i class="right floated large ui bordered colored <?= $meta['icon'] ?> icon"></i>
                             <div class="header"><?= $meta['header'] ?></div>
                             <div class="meta"><?= $meta['meta'] ?></div>
                             <div class="description"><?= $meta['description'] ?></div>
                         </div>
                         <div class="extra content">
                             <div class="ui three buttons">
                                 <div class="ui teal button" data-action="delete_all" data-table="<?= $tbl ?>">All</div>
                                 <button class="ui blue button" data-action="delete_proyek" data-table="<?= $tbl ?>">Dokumen</button>
                                 <div class="ui violet button" data-action="reset" data-table="<?= $tbl ?>">Reset</div>
                             </div>
                         </div>
                     </div>
                 <?php endforeach; ?>
             </div>
             <h3 class="ui dividing header">Transaksi</h3>
             <div class="ui three stackable cards">
                 <?php
                    $group_trx = array_filter($tables, function ($k) {
                        return str_starts_with($k, 'trx_')
                            || str_contains($k, 'naskah')
                            || str_contains($k, 'paket')
                            || str_contains($k, 'rab');
                    }, ARRAY_FILTER_USE_KEY);

                    foreach ($group_trx as $tbl => $meta):
                    ?>
                     <!-- CARD SAMA -->
                     <div class="card">
                         <div class="content">
                             <i class="right floated large ui bordered colored <?= $meta['icon'] ?> icon"></i>
                             <div class="header"><?= $meta['header'] ?></div>
                             <div class="meta"><?= $meta['meta'] ?></div>
                             <div class="description"><?= $meta['description'] ?></div>
                         </div>
                         <div class="extra content">
                             <div class="ui three buttons">
                                 <div class="ui teal button" data-action="delete_all" data-table="<?= $tbl ?>">All</div>
                                 <button class="ui blue button" data-action="delete_proyek" data-table="<?= $tbl ?>">Dokumen</button>
                                 <div class="ui violet button" data-action="reset" data-table="<?= $tbl ?>">Reset</div>
                             </div>
                         </div>
                     </div>
                 <?php endforeach; ?>
             </div>
         </div>
     </div>
 </div>