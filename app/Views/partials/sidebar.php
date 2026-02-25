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

    <!-- DASHBOARD -->
    <a class="item" href="/dashboard" data-route="/dashboard">
      <i class="home icon"></i> Dashboard
    </a>

    <!-- ANGGARAN -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Anggaran
      </div>
      <div class="content">
        <a class="item" href="/renstra" data-route="/renstra">
          <i class="purple sitemap icon"></i> RENSTRA
        </a>
        <a class="item" href="/renja" data-route="/renja">
          <i class="violet tag icon"></i> RENJA
        </a>
        <a class="item" href="/dpa" data-route="/dpa">
          <i class="yellow tags icon"></i> DPA
        </a>
        <a class="item" href="/renja-perubahan" data-route="/renja-perubahan">
          <i class="orange edit icon"></i> RENJA Perubahan
        </a>
        <a class="item" href="/dppa" data-route="/dppa">
          <i class="brown folder open icon"></i> DPPA
        </a>
      </div>
    </div>

    <!-- KONTRAK -->
    <a class="item" href="/kontrak" data-route="/kontrak">
      <i class="file contract icon"></i> Kontrak
    </a>

    <!-- REALISASI -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Realisasi
      </div>
      <div class="content">
        <a class="item" href="/realisasi/input" data-route="/realisasi/input">
          <i class="purple chart pie icon"></i> Input Realisasi
        </a>
        <a class="item" href="/realisasi/spj" data-route="/realisasi/spj">
          <i class="violet chart line icon"></i> SPJ
        </a>
        <a class="item" href="/realisasi/laporan" data-route="/realisasi/laporan">
          <i class="yellow chart bar icon"></i> Laporan
        </a>
      </div>
    </div>

    <!-- REFERENSI -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Referensi
      </div>
      <div class="content">
        <a class="item" href="/referensi/urusan" data-route="/referensi/urusan"><i class="user plus icon"></i> Urusan</a>
        <a class="item" href="/referensi/bidang" data-route="/referensi/bidang"><i class="user plus icon"></i> Bidang</a>
        <a class="item" href="/referensi/program" data-route="/referensi/program"><i class="users icon"></i> Program</a>
        <a class="item" href="/referensi/kegiatan" data-route="/referensi/kegiatan"><i class="outdent icon"></i> Kegiatan</a>
        <a class="item" href="/referensi/sub-kegiatan" data-route="/referensi/sub-kegiatan"><i class="layer group icon"></i> Sub Kegiatan</a>
        <a class="item" href="/referensi/rekanan" data-route="/referensi/rekanan"><i class="book reader icon"></i> Rekanan</a>
        <a class="item" href="/referensi/satuan" data-route="/referensi/satuan"><i class="calculator icon"></i> Satuan</a>
        <a class="item" href="/referensi/mapping" data-route="/referensi/mapping"><i class="stream icon"></i> Mapping</a>
        <a class="item" href="/referensi/aset" data-route="/referensi/aset"><i class="calendar alternate icon"></i> Neraca</a>
        <a class="item" href="/referensi/akun" data-route="/referensi/akun"><i class="calendar alternate outline icon"></i> Akun</a>
        <a class="item" href="/referensi/sumber-dana" data-route="/referensi/sumber-dana"><i class="money check alternate icon"></i> Sumber Dana</a>
        <a class="item" href="/referensi/organisasi" data-route="/referensi/organisasi"><i class="id card icon"></i> Organisasi</a>
        <a class="item" href="/referensi/peraturan" data-route="/referensi/peraturan"><i class="balance scale icon"></i> Peraturan</a>
        <a class="item" href="/referensi/wilayah" data-route="/referensi/wilayah"><i class="globe icon"></i> Wilayah</a>
      </div>
    </div>

    <!-- STANDAR HARGA -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Standar Harga Satuan
      </div>
      <div class="content">
        <a class="item" href="/standar-harga/ssh" data-route="/standar-harga/ssh"><i class="file icon"></i> SSH</a>
        <a class="item" href="/standar-harga/hspk" data-route="/standar-harga/hspk"><i class="file alternate icon"></i> HSPK</a>
        <a class="item" href="/standar-harga/asb" data-route="/standar-harga/asb"><i class="file alternate outline icon"></i> ASB</a>
        <a class="item" href="/standar-harga/sbu" data-route="/standar-harga/sbu"><i class="file outline icon"></i> SBU</a>
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
        <a class="item" href="/kepegawaian/dashboard" data-route="/kepegawaian/dashboard"><i class="chart pie icon"></i> Dashboard</a>
        <a class="item" href="/kepegawaian/asn" data-route="/kepegawaian/asn"><i class="users icon"></i> Data ASN</a>
        <a class="item" href="/kepegawaian/pppk" data-route="/kepegawaian/pppk"><i class="user outline icon"></i> PPPK</a>
        <a class="item" href="/kepegawaian/riwayat-jabatan" data-route="/kepegawaian/riwayat-jabatan"><i class="briefcase icon"></i> Riwayat Jabatan</a>
        <a class="item" href="/kepegawaian/riwayat-pangkat" data-route="/kepegawaian/riwayat-pangkat"><i class="angle up icon"></i> Riwayat Pangkat</a>
        <a class="item" href="/kepegawaian/cuti" data-route="/kepegawaian/cuti"><i class="calendar icon"></i> Cuti & Izin</a>
        <a class="item" href="/kepegawaian/sk-pegawai" data-route="/kepegawaian/sk-pegawai"><i class="file signature icon"></i> SK Pegawai</a>
      </div>
    </div>

    <!-- TATA NASKAH -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Tata Naskah
      </div>
      <div class="content">
        <a class="item" href="/tata-naskah/dashboard" data-route="/tata-naskah/dashboard"><i class="chart bar icon"></i> Dashboard</a>
        <a class="item" href="/tata-naskah/buat" data-route="/tata-naskah/buat"><i class="plus icon"></i> Buat Naskah</a>
        <a class="item" href="/tata-naskah/daftar" data-route="/tata-naskah/daftar"><i class="file alternate icon"></i> Daftar Naskah</a>
        <a class="item" href="/tata-naskah/master-jenis" data-route="/tata-naskah/master-jenis"><i class="list icon"></i> Master Jenis</a>
        <a class="item" href="/tata-naskah/template" data-route="/tata-naskah/template"><i class="clone icon"></i> Template</a>
        <a class="item" href="/tata-naskah/klasifikasi" data-route="/tata-naskah/klasifikasi"><i class="shield alternate icon"></i> Klasifikasi</a>
      </div>
    </div>

    <?php if (($_SESSION['user']['type_user'] ?? '') !== 'viewer'): ?>
      <a class="item" href="/halaman-berita" data-route="/halaman-berita">
        <i class="newspaper icon"></i> Halaman Berita
      </a>
      <a class="item" href="/reset-tabel" data-route="/reset-tabel">
        <i class="erase icon"></i> Reset Tabel
      </a>
      <a class="item" href="/pengaturan" data-route="/pengaturan">
        <i class="toolbox icon"></i> Pengaturan
      </a>
    <?php endif; ?>

    <a class="item" href="/wallchat" data-route="/wallchat">
      <i class="comments outline icon"></i> Pesan
    </a>

    <a class="item" href="/profil" data-route="/profil">
      <i class="user icon"></i> Profil
    </a>

  </div>