<!-- SIDEBAR WRAPPER -->
<div class="ui bottom attached segment pushable" id="mainContext">

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

    <!-- DASHBOARD (server) -->
    <a class="item" href="/dashboard" data-spa="server">
      <i class="home icon"></i> Dashboard
    </a>

    <!-- ANGGARAN -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Anggaran
      </div>
      <div class="content">
        <a class="item" href="/?page=renstra" data-spa="client">
          <i class="purple sitemap icon"></i> RENSTRA
        </a>
        <a class="item" href="/?page=renja" data-spa="client">
          <i class="violet tag icon"></i> RENJA
        </a>
        <a class="item" href="/?page=dpa" data-spa="client">
          <i class="yellow tags icon"></i> DPA
        </a>
        <a class="item" href="/?page=renja_perubahan" data-spa="client">
          <i class="orange edit icon"></i> RENJA Perubahan
        </a>
        <a class="item" href="/?page=dppa" data-spa="client">
          <i class="brown folder open icon"></i> DPPA
        </a>
      </div>
    </div>

    <!-- KONTRAK (server) -->
    <a class="item" href="/kontrak" data-spa="server">
      <i class="file contract icon"></i> Kontrak
    </a>

    <!-- REFERENSI -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Referensi
      </div>
      <div class="content">
        <a class="item" href="/?page=referensi&tbl=urusan" data-spa="client">Urusan</a>
        <a class="item" href="/?page=referensi&tbl=bidang" data-spa="client">Bidang</a>
        <a class="item" href="/?page=referensi&tbl=program" data-spa="client">Program</a>
        <a class="item" href="/?page=referensi&tbl=kegiatan" data-spa="client">Kegiatan</a>
        <a class="item" href="/?page=referensi&tbl=sub_kegiatan" data-spa="client">Sub Kegiatan</a>
        <a class="item" href="/?page=referensi&tbl=rekanan" data-spa="client">Rekanan</a>
        <a class="item" href="/?page=referensi&tbl=satuan" data-spa="client">Satuan</a>
        <a class="item" href="/?page=referensi&tbl=mapping" data-spa="client">Mapping</a>
        <a class="item" href="/?page=referensi&tbl=aset" data-spa="client">Neraca</a>
        <a class="item" href="/?page=referensi&tbl=akun" data-spa="client">Akun</a>
        <a class="item" href="/?page=referensi&tbl=sumber_dana" data-spa="client">Sumber Dana</a>
        <a class="item" href="/?page=referensi&tbl=organisasi" data-spa="client">Organisasi</a>
        <a class="item" href="/?page=referensi&tbl=peraturan" data-spa="client">Peraturan</a>
        <a class="item" href="/?page=referensi&tbl=wilayah" data-spa="client">Wilayah</a>
      </div>
    </div>

    <!-- STANDAR HARGA -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Standar Harga Satuan
      </div>
      <div class="content">
        <a class="item" href="/?page=standar_harga&tbl=ssh" data-spa="client">SSH</a>
        <a class="item" href="/?page=standar_harga&tbl=hspk" data-spa="client">HSPK</a>
        <a class="item" href="/?page=standar_harga&tbl=asb" data-spa="client">ASB</a>
        <a class="item" href="/?page=standar_harga&tbl=sbu" data-spa="client">SBU</a>
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
        <a class="item" href="/?page=kepegawaian&tbl=asn" data-spa="client">Data ASN</a>
        <a class="item" href="/?page=kepegawaian&tbl=pppk" data-spa="client">PPPK</a>
        <a class="item" href="/?page=kepegawaian&tbl=riwayat_jabatan" data-spa="client">Riwayat Jabatan</a>
        <a class="item" href="/?page=kepegawaian&tbl=riwayat_pangkat" data-spa="client">Riwayat Pangkat</a>
        <a class="item" href="/?page=kepegawaian&tbl=cuti" data-spa="client">Cuti & Izin</a>
        <a class="item" href="/?page=kepegawaian&tbl=sk_pegawai" data-spa="client">SK Pegawai</a>
      </div>
    </div>

    <!-- TATA NASKAH -->
    <div class="ui accordion inverted item">
      <div class="title item">
        <i class="dropdown icon"></i> Tata Naskah
      </div>
      <div class="content">
        <a class="item" href="/tata_naskah/dashboard" data-spa="server">Dashboard</a>
        <a class="item" href="/tata_naskah/buat" data-spa="server">Buat Naskah</a>
        <a class="item" href="/?page=tata_naskah&tbl=daftar" data-spa="client">
          Daftar Naskah
        </a>
      </div>
    </div>

    <?php if (($_SESSION['user']['type_user'] ?? '') !== 'viewer'): ?>
      <a class="item" href="/halaman_berita" data-spa="server">
        <i class="newspaper icon"></i> Halaman Berita
      </a>
      <a class="item" href="/reset_tabel" data-spa="server">
        <i class="erase icon"></i> Reset Tabel
      </a>
      <a class="item" href="/pengaturan" data-spa="server">
        <i class="toolbox icon"></i> Pengaturan
      </a>
    <?php endif; ?>

    <a class="item" href="/wallchat" data-spa="server">
      <i class="comments outline icon"></i> Pesan
    </a>

    <a class="item" href="/profil" data-spa="server">
      <i class="user icon"></i> Profil
    </a>

  </div>