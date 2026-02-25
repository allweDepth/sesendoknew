<!-- SIDEBAR WRAPPER -->
<div class="ui bottom attached segment pushable" id="mainContext">
  <!-- SIDEBAR -->
  <div class="ui inverted vertical sidebar menu left sidebarutama">
    <!-- HEADER -->
    <div class="item">
      <h2 class="ui inverted center aligned icon header dash_header">
        <i class="circular blue building icon"></i>
        <div class="content">
          seSendok
          <div class="sub header">Pemerintahan</div>
          <div class="ui blue basic inverted label">
            <?= $_SESSION["user"]["tahun"] ?? date('Y'); ?>
          </div>
        </div>
      </h2>
    </div>
    <!-- SEARCH -->
    <div class="item">
      <div class="ui inverted transparent icon input">
        <input type="text" placeholder="Menu...">
        <i class="search icon"></i>
      </div>
    </div>
    <!-- HOME -->
    <a class="item" href="/dashboard">
      <i class="home icon"></i> Dashboard
    </a>
    <!-- ANGGARAN -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i><span></span>Anggaran
      </div>
      <div class="content">
        <a class="item" href="/renstra"><i class="purple sitemap icon"></i> RENSTRA</a>
        <a class="item" href="/renja"><i class="violet tag icon"></i> RENJA</a>
        <a class="item" href="/dpa"><i class="yellow tags icon"></i> DPA</a>
        <a class="item" href="/renja_perubahan"><i class="orange edit icon"></i> RENJA Perubahan</a>
        <a class="item" href="/dppa"><i class="brown folder open icon"></i> DPPA</a>
      </div>
    </div>

    <!-- KONTRAK -->
    <a class="item" href="/kontrak">
      <i class="file contract icon"></i> Kontrak
    </a>

    <!-- REALISASI -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Realisasi
      </div>
      <div class="content">
        <a class="item" href="/input_realisasi"><i class="purple chart pie icon"></i> Input Realisasi</a>
        <a class="item" href="/spj"><i class="violet chart line icon"></i> SPJ</a>
        <a class="item" href="/laporan"><i class="yellow chart bar icon"></i> Laporan</a>
      </div>
    </div>

    <!-- REFERENSI -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Referensi
      </div>
      <div class="content">
        <a class="item" href="/referensi?tbl=urusan"><i class="user plus icon"></i>Urusan</a>
        <a class="item" href="/referensi?tbl=bidang"><i class="user plus icon"></i> Bidang </a>
        <a class="item" href="/referensi?tbl=program"><i class="users icon"></i> Program</a>
        <a class="item" href="/referensi?tbl=kegiatan"><i class="outdent icon"></i> Kegiatan</a>
        <a class="item" href="/referensi?tbl=sub_kegiatan"><i class="layer group icon"></i> Sub Kegiatan</a>
        <a class="item" href="/referensi?tbl=rekanan"><i class="book reader icon"></i> Rekanan</a>
        <a class="item" href="/referensi?tbl=satuan"><i class="calculator icon"></i> Satuan</a>
        <a class="item" href="/referensi?tbl=mapping"><i class="stream icon"></i> Mapping</a>
        <a class="item" href="/referensi?tbl=aset"><i class="calendar alternate icon"></i> Neraca</a>
        <a class="item" href="/referensi?tbl=akun"><i class="calendar alternate outline icon"></i> Akun</a>
        <a class="item" href="/referensi?tbl=sumber_dana"><i class="money check alternate icon"></i> Sumber Dana</a>
        <a class="item" href="/referensi?tbl=organisasi"><i class="id card icon"></i> Organisasi</a>
        <a class="item" href="/referensi?tbl=peraturan"><i class="balance scale icon"></i> Peraturan</a>
        <a class="item" href="/referensi?tbl=wilayah"><i class="globe icon"></i> Wilayah</a>
      </div>
    </div>

    <!-- STANDAR HARGA -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Standar Harga Satuan
      </div>
      <div class="content">
        <a class="item" href="/standar_harga?tbl=ssh"><i class="file icon"></i> SSH</a>
        <a class="item" href="/standar_harga?tbl=hspk"><i class="file alternate icon"></i> HSPK</a>
        <a class="item" href="/standar_harga?tbl=asb"><i class="file alternate outline icon"></i> ASB</a>
        <a class="item" href="/standar_harga?tbl=sbu"><i class="file outline icon"></i> SBU</a>
      </div>
    </div>

    <!-- KEPEGAWAIAN -->
    <div class="ui accordion inverted item">

      <div class="title item">
        <i class="dropdown icon"></i>
        <i class="users icon"></i>
        Kepegawaian
      </div>

      <div class="content">

        <a class="item"
          href="/kepegawaian/dashboard"
          data-spa>
          <i class="chart pie icon"></i>
          Dashboard
        </a>

        <a class="item"
          href="/kepegawaian?tbl=asn"
          data-spa>
          <i class="users icon"></i>
          Data ASN
        </a>

        <a class="item"
          href="/kepegawaian?tbl=pppk"
          data-spa>
          <i class="user outline icon"></i>
          PPPK
        </a>

        <a class="item"
          href="/kepegawaian?tbl=riwayat_jabatan"
          data-spa>
          <i class="briefcase icon"></i>
          Riwayat Jabatan
        </a>

        <a class="item"
          href="/kepegawaian?tbl=riwayat_pangkat"
          data-spa>
          <i class="angle up icon"></i>
          Riwayat Pangkat
        </a>

        <a class="item"
          href="/kepegawaian?tbl=cuti"
          data-spa>
          <i class="calendar icon"></i>
          Cuti & Izin
        </a>

        <a class="item"
          href="/kepegawaian?tbl=sk_pegawai"
          data-spa>
          <i class="file signature icon"></i>
          SK Pegawai
        </a>

      </div>

    </div>
    <!-- TATA NASKAH -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Tata Naskah
      </div>
      <div class="content">
        <a class="item" href="/tata_naskah/dashboard">
          <i class="chart bar icon"></i> Dashboard
        </a>

        <a class="item" href="/tata_naskah/buat">
          <i class="plus icon"></i> Buat Naskah
        </a>

        <a class="item" href="/tata_naskah/daftar">
          <i class="file alternate icon"></i> Daftar Naskah
        </a>

        <a class="item" href="/dynamic?tbl=ref_jenis_naskah">
          <i class="list icon"></i> Master Jenis
        </a>

        <a class="item" href="/dynamic?tbl=ref_template_naskah">
          <i class="clone icon"></i> Template
        </a>

        <a class="item" href="/dynamic?tbl=ref_klasifikasi_keamanan">
          <i class="shield alternate icon"></i> Klasifikasi
        </a>
      </div>
    </div>
    <!-- ADMIN MENU super_admin,admin_wilayah,admin_opd,1=viewer 1=viewer  2=admin_opd, 3.admin_wilayah 4=super_admin-->
    <?php if (($_SESSION['user']['type_user'] ?? '') !== 'viewer'): ?>


      <a class="item" href="/halaman_berita">
        <i class="newspaper icon"></i> Halaman Berita
      </a>
      <a class="item" href="/reset_tabel">
        <i class="erase icon"></i> Reset Tabel
      </a>
      <a class="item" href="/pengaturan">
        <i class="toolbox icon"></i> Pengaturan
      </a>

    <?php endif; ?>

    <a class="item" href="/wallchat">
      <i class="comments outline icon"></i> Pesan
    </a>

    <a class="item" href="/profil">
      <i class="user icon"></i> Profil
    </a>
  </div>