<?php
$role = (string)($_SESSION['user']['type_user'] ?? 'viewer');
$regional = in_array($role, ['super_admin', 'admin_wilayah', 'tapd'], true);
$manager = in_array($role, ['super_admin', 'admin_wilayah', 'tapd', 'admin_opd', 'kepala_opd', 'pa_kpa'], true);
$financialReader = in_array($role, ['super_admin', 'admin_wilayah', 'tapd', 'admin_opd', 'kepala_opd', 'pa_kpa', 'bendahara'], true);
$group = static function (string $title, string $icon, array $items): void {
  $mods = ['Referensi' => 'referensi', 'Standar Harga' => 'standar_harga', 'Usulan' => 'usulan', 'Perencanaan' => 'perencanaan', 'Penganggaran' => 'penganggaran', 'Kontrak' => 'kontrak', 'Realisasi' => 'realisasi', 'Laporan Keuangan' => 'laporan_keuangan', 'SAKIP & Kinerja' => 'sakip', 'Kepegawaian' => 'kepegawaian', 'Tata Naskah' => 'tata_naskah', 'User & Role OPD' => 'user_role', 'Berita' => 'berita', 'Pesan' => 'pesan', 'Profil' => 'profil'];
  $items = array_values(array_filter($items, fn($x) => strtolower($x[0]) !== 'dashboard'));
  if (isset($mods[$title])) array_unshift($items, ['Dashboard', '/dashboard?mod=' . $mods[$title], $title . '/Dashboard']);
  $id = 'nav-group-' . preg_replace('/[^a-z]+/', '-', strtolower($title));
  ?><div class="item workspace-group">
    <button type="button" class="title" aria-expanded="false" aria-controls="<?= $id ?>">
      <span class="workspace-nav-icon"><i class="<?= $icon ?> icon" aria-hidden="true"></i></span>
      <span class="workspace-nav-text"><?= htmlspecialchars($title) ?></span>
      <i class="angle right icon workspace-chevron" aria-hidden="true"></i>
    </button>
    <div class="content" id="<?= $id ?>"><?php foreach ($items as [$label, $url, $page]): ?><a class="item" href="<?= $url ?>" data-spa="<?= str_starts_with($url, '/dashboard') || str_starts_with($url, '/maps') || $url === '/kepegawaian/struktur' ? 'server' : 'client' ?>" data-title="<?= $page ?>"><?= $label ?></a><?php endforeach; ?></div>
  </div><?php };
        ?>
<div class="ui bottom attached segment pushable" id="mainContext">
  <nav class="ui inverted vertical sidebar menu left sidebarutama accordion" id="workspaceNavigation" aria-label="Navigasi ruang kerja">
    <div class="item workspace-identity">
      <div class="workspace-brand">
        <img src="<?= app_url('/assets/img/umum/logo.png') ?>" width="34" height="37" alt="">
        <div><strong>seSendok</strong><small>Kabupaten Pasangkayu</small></div>
        <button type="button" class="ui icon button workspace-nav-close" aria-label="Tutup navigasi"><i class="times icon" aria-hidden="true"></i></button>
      </div>
      <button type="button" class="ui button workspace-nav-search" data-menu-search><i class="search icon" aria-hidden="true"></i>Cari menu <kbd data-menu-shortcut>Ctrl K</kbd></button>
    </div>
    <a class="item" href="/dashboard" data-spa="server" data-title="Dashboard"><i class="home icon"></i> Dashboard</a>
    <?php $group('Maps', 'map outline', [
      ['Peta', '/maps', 'Maps/Peta'],
      ['Atur Layer SHP', '/maps/layers', 'Maps/Atur Layer SHP'],
      ['Tambah/Unggah SHP', '/maps/add', 'Maps/Tambah SHP']
    ]); ?>
    <?php $group('Referensi', 'database', [
      ['Urusan', '/referensi?tbl=rekening_kegiatan&req=urusan', 'Referensi/Urusan'],
      ['Bidang', '/referensi?tbl=rekening_kegiatan&req=bidang', 'Referensi/Bidang'],
      ['Program', '/referensi?tbl=rekening_kegiatan&req=program', 'Referensi/Program'],
      ['Kegiatan', '/referensi?tbl=rekening_kegiatan&req=kegiatan', 'Referensi/Kegiatan'],
      ['Sub Kegiatan', '/referensi?tbl=rekening_kegiatan&req=sub_kegiatan', 'Referensi/Sub Kegiatan'],
      ['Rekanan', '/referensi?tbl=rekanan', 'Referensi/Rekanan'],
      ['Satuan', '/referensi?tbl=satuan', 'Referensi/Satuan'],
      ['Mapping Biaya Akun', '/mapping', 'Referensi/Mapping'],
      ['Neraca', '/referensi?tbl=aset', 'Referensi/Neraca'],
      ['Akun', '/referensi?tbl=akun', 'Referensi/Akun'],
      ['Sumber Dana', '/referensi?tbl=sumber_dana', 'Referensi/Sumber Dana'],
      ['Organisasi OPD', '/referensi?tbl=organisasi', 'Referensi/OPD'],
      ['Peraturan', '/referensi?tbl=peraturan', 'Referensi/Peraturan'],
      ['Wilayah', '/referensi?tbl=wilayah', 'Referensi/Wilayah']
    ]);
    $group('Standar Harga', 'calculator', array_map(fn($x) => [strtoupper($x), '/standar_harga?tbl=' . $x, 'Standar Harga/' . strtoupper($x)], ['ssh', 'hspk', 'asb', 'sbu']));
    $group('Usulan', 'lightbulb outline', [['Daftar Usulan', '/referensi?tbl=usulan_pembangunan', 'Usulan/Daftar Usulan']]);
    $group('Perencanaan', 'project diagram', [['RPJMD Kabupaten', '/referensi?tbl=rpjmd_kabupaten', 'Perencanaan/RPJMD'], ['RENSTRA', '/renstra', 'Perencanaan/Renstra'], ['RKPD', '/rkpd', 'Perencanaan/RKPD'], ['RENJA', '/renja', 'Perencanaan/Renja']]);
    $group('Penganggaran', 'shopping cart', [['RKA', '/rka', 'Penganggaran/RKA'], ['DPA', '/dpa', 'Penganggaran/DPA'], ['RKPD Perubahan', '/rkpd_perubahan', 'Penganggaran/RKPD Perubahan'], ['RENJA Perubahan', '/renja_perubahan', 'Penganggaran/Renja Perubahan'], ['RKA Perubahan', '/rka_perubahan', 'Penganggaran/RKA Perubahan'], ['DPPA', '/dppa', 'Penganggaran/DPPA']]);
    $group('Kontrak', 'file signature', [['Daftar Kontrak', '/kontrak?tbl=kontrak', 'Kontrak/Daftar Kontrak']]);
    $group('Realisasi', 'chart bar', [['Dashboard Realisasi', '/kontrak?tbl=__laporan', 'Realisasi/Dashboard'], ['Penginputan', '/kontrak?tbl=realisasi', 'Realisasi/Penginputan'], ['Laporan Realisasi', '/kontrak?tbl=__laporan&report=realisasi', 'Realisasi/Laporan Realisasi'], ['Laporan Fisik & Keuangan', '/kontrak?tbl=__laporan&report=fisik_keuangan', 'Realisasi/Laporan Fisik & Keuangan']]);
    if ($financialReader) $group('Laporan Keuangan', 'balance scale', [['LRA', '/laporan-keuangan?laporan=lra', 'Laporan Keuangan/LRA'], ['Neraca', '/laporan-keuangan?laporan=neraca', 'Laporan Keuangan/Neraca'], ['CaLK', '/laporan-keuangan?laporan=calk', 'Laporan Keuangan/CaLK']]);
    $group('SAKIP & Kinerja', 'bullseye', [['IKU OPD', '/referensi?tbl=iku_opd', 'SAKIP/IKU OPD'], ['Pohon Kinerja', '/referensi?tbl=pohon_kinerja', 'SAKIP/Pohon Kinerja'], ['Target Renja', '/referensi?tbl=renja_kinerja', 'SAKIP/Target Renja'], ['Perjanjian Kinerja', '/referensi?tbl=perjanjian_kinerja', 'SAKIP/Perjanjian Kinerja'], ['Indikator PK', '/referensi?tbl=perjanjian_kinerja_detail', 'SAKIP/Indikator PK'], ['Pengukuran Kinerja', '/referensi?tbl=pengukuran_kinerja', 'SAKIP/Pengukuran'], ['Evaluasi Renja', '/referensi?tbl=evaluasi_renja', 'SAKIP/Evaluasi Renja'], ['Evaluasi Renstra', '/referensi?tbl=evaluasi_renstra', 'SAKIP/Evaluasi Renstra']]);
    if ($manager) {
      $pegawaiItems = [['Struktur Organisasi', '/kepegawaian/struktur', 'Kepegawaian/Struktur Organisasi']];
      $pegawaiItems = array_merge($pegawaiItems, array_map(fn($x) => [$x[1], '/kepegawaian?tbl=' . $x[0], 'Kepegawaian/' . $x[1]], [['asn', 'Data ASN'], ['pppk', 'PPPK'], ['riwayat_jabatan', 'Riwayat Jabatan'], ['riwayat_pangkat', 'Riwayat Pangkat'], ['cuti', 'Cuti & Izin'], ['sk_pegawai', 'SK Pegawai'], ['pejabat_tahunan', 'Pejabat Tahunan'], ['absensi', 'Absensi'], ['penugasan_subkegiatan', 'Role & Sub Kegiatan']]));
      $group('Kepegawaian', 'users', $pegawaiItems);
    }
    $group('Tata Naskah', 'file alternate', [['Dashboard', '/tata_naskah/dokumen', 'Tata Naskah/Dashboard'], ['Buat Naskah', '/tata_naskah/buat', 'Tata Naskah/Buat'], ['Daftar Naskah', '/tata_naskah/daftar', 'Tata Naskah/Daftar']]); ?>
    <?php if ($manager) $group('User & Role OPD', 'users cog', [['Kelola User & Role', '/user_opd', 'User & Role OPD/Kelola']]); ?>
    <a class="item" href="/pengaturan" data-spa="client" data-title="Pengaturan"><i class="toolbox icon"></i> Pengaturan</a>
    <?php if ($manager) $group('Berita', 'newspaper', [['Kelola Konten', '/halaman_berita', 'Berita/Kelola Konten']]); ?>
    <?php if ($role === 'super_admin'): ?><a class="item" href="/reset_tabel" data-spa="client" data-title="Reset Tabel"><i class="erase icon"></i> Reset Tabel</a><a class="item" href="/user_pemda" data-spa="server" data-title="User Pemda"><i class="users icon"></i> User Pemda</a><?php endif; ?>
    <?php if ($role === 'admin_wilayah'): ?><a class="item" href="/reset_tabel" data-spa="server" data-title="Backup SQL Wilayah"><i class="download icon"></i> Backup SQL Wilayah</a><?php endif; ?>
    <?php $group('Pesan', 'comments outline', [['Buka Messenger', '/wallchat', 'Pesan/Messenger']]);
    $group('Profil', 'user', [['Pengaturan Profil', '/profil', 'Profil/Pengaturan']]); ?>
  </nav>
