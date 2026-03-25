<!-- SIDEBAR WRAPPER -->
<div class="ui bottom attached segment pushable" id="mainContext">

  <div class="ui inverted vertical sidebar menu left sidebarutama ui accordion">

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

    <!-- DASHBOARD -->
    <a class="item" href="/dashboard" data-spa="server" data-title="Dasboard">
      <i class="home icon"></i> Dashboard
    </a>

    <!-- ANGGARAN -->
    <div class="item">
      <div class="title item">
        <i class="dropdown icon"></i> Anggaran
      </div>
      <div class="content">
        <a class="item" href="/renstra" data-spa="client" data-title="Anggaran/Renstra">
          <i class="purple sitemap icon"></i> RENSTRA
        </a>

        <a class="item" href="/renja" data-spa="server" data-title="Anggaran/Renja">
          <i class="violet tasks icon"></i> RENJA
        </a>

        <a class="item" href="/renja_perubahan" data-spa="server" data-title="Anggaran/Renja Perubahan">
          <i class="orange sync alternate icon"></i> RENJA Perubahan
        </a>

        <a class="item" href="/rka" data-spa="server" data-title="Anggaran/RKA">
          <i class="violet tasks icon"></i> RKA
        </a>

        <a class="item" href="/rka_perubahan" data-spa="server" data-title="Anggaran/RKA Perubahan">
          <i class="orange sync alternate icon"></i> RKA Perubahan
        </a>

        <a class="item" href="/dpa" data-spa="server" data-title="Anggaran/DPA">
          <i class="yellow file alternate icon"></i> DPA
        </a>

        <a class="item" href="/dppa" data-spa="server" data-title="Anggaran/DPPA">
          <i class="brown folder open icon"></i> DPPA
        </a>
      </div>
    </div>

    <!-- KONTRAK -->
    <a class="item" href="/kontrak" data-spa="client" data-title="Kontrak">
      <i class="file contract icon"></i> Kontrak
    </a>

    <!-- REFERENSI -->
    <div class="item">
      <div class="title item">
        <i class="dropdown icon"></i> Referensi
      </div>
      <div class="content">
        <a class="item" href="/referensi?tbl=rekening_kegiatan" data-req="urusan" data-spa="client"
          data-title="Referensi/Urusan">
          <i class="blue briefcase icon"></i> Urusan
        </a>

        <a class="item" href="/referensi?tbl=rekening_kegiatan" data-req="bidang" data-spa="client"
          data-title="Referensi/Bidang">
          <i class="teal layer group icon"></i> Bidang
        </a>

        <a class="item" href="/referensi?tbl=rekening_kegiatan" data-req="program" data-spa="client"
          data-title="Referensi/Program">
          <i class="green project diagram icon"></i> Program
        </a>

        <a class="item" href="/referensi?tbl=rekening_kegiatan" data-req="kegiatan" data-spa="client"
          data-title="Referensi/Kegiatan">
          <i class="olive clipboard list icon"></i> Kegiatan
        </a>

        <a class="item" href="/referensi?tbl=rekening_kegiatan" data-req="sub_kegiatan" data-spa="client"
          data-title="Referensi/Sub Kegiatan">
          <i class="grey list alternate icon"></i> Sub Kegiatan
        </a>

        <a class="item" href="/referensi?tbl=rekanan" data-spa="client" data-title="Referensi/Rekanan">
          <i class="orange handshake icon"></i> Rekanan
        </a>

        <a class="item" href="/referensi?tbl=satuan" data-spa="client" data-title="Referensi/Satuan">
          <i class="brown balance scale icon"></i> Satuan
        </a>

        <a class="item" href="/mapping" data-spa="client" data-title="Referensi/Mapping">
          <i class="purple map icon"></i> Mapping Biaya Akun
        </a>

        <a class="item" href="/referensi?tbl=aset" data-spa="client" data-title="Referensi/Aset">
          <i class="yellow warehouse icon"></i> Neraca
        </a>

        <a class="item" href="/referensi?tbl=akun" data-spa="client" data-title="Referensi/Akun">
          <i class="blue wallet icon"></i> Akun
        </a>

        <a class="item" href="/referensi?tbl=sumber_dana" data-spa="client" data-title="Referensi/Sumber Dana">
          <i class="green money bill alternate icon"></i> Sumber Dana
        </a>

        <a class="item" href="/referensi?tbl=organisasi" data-spa="client" data-title="Referensi/Organisasi OPD">
          <i class="teal building icon"></i> Organisasi
        </a>

        <a class="item" href="/referensi?tbl=peraturan" data-spa="client" data-title="Referensi/Peraturan">
          <i class="red gavel icon"></i> Peraturan
        </a>

        <a class="item" href="/referensi?tbl=wilayah" data-spa="client" data-title="Referensi/Wilayah">
          <i class="violet globe asia icon"></i> Wilayah
        </a>
      </div>
    </div>

    <!-- STANDAR HARGA -->
    <div class="item">
      <div class="title item">
        <i class="dropdown icon"></i> Standar Harga Satuan
      </div>
      <div class="content">
        <a class="item" href="/standar_harga?tbl=ssh" data-spa="client" data-title="Standar Harga/SSH">
          <i class="green calculator icon"></i> SSH
        </a>

        <a class="item" href="/standar_harga?tbl=hspk" data-spa="client" data-title="Standar Harga/HSPK">
          <i class="blue chart line icon"></i> HSPK
        </a>

        <a class="item" href="/standar_harga?tbl=asb" data-spa="client" data-title="Standar Harga/ASB">
          <i class="orange balance scale icon"></i> ASB
        </a>

        <a class="item" href="/standar_harga?tbl=sbu" data-spa="client" data-title="Standar Harga/SBU">
          <i class="teal file invoice dollar icon"></i> SBU
        </a>
      </div>
    </div>

    <!-- KEPEGAWAIAN -->
    <div class="item">
      <div class="title item">
        <i class="dropdown icon"></i>
        <i class="users icon"></i>
        Kepegawaian
      </div>
      <div class="content">
        <a class="item" href="/kepegawaian?tbl=asn" data-spa="client" data-title="Kepegawaian/ASN">
          <i class="blue id badge icon"></i> Data ASN
        </a>

        <a class="item" href="/kepegawaian?tbl=pppk" data-spa="client" data-title="Kepegawaian/PPPK">
          <i class="teal user tie icon"></i> PPPK
        </a>

        <a class="item" href="/kepegawaian?tbl=riwayat_jabatan" data-spa="client"
          data-title="Kepegawaian/Riwayat Jabatan">
          <i class="orange briefcase icon"></i> Riwayat Jabatan
        </a>

        <a class="item" href="/kepegawaian?tbl=riwayat_pangkat" data-spa="client"
          data-title="Kepegawaian/Riwayat Pangkat">
          <i class="purple medal icon"></i> Riwayat Pangkat
        </a>

        <a class="item" href="/kepegawaian?tbl=cuti" data-spa="client" data-title="Kepegawaian/Cuti">
          <i class="yellow calendar alternate icon"></i> Cuti & Izin
        </a>

        <a class="item" href="/kepegawaian?tbl=sk_pegawai" data-spa="client" data-title="Kepegawaian/SK Pegawai">
          <i class="green file signature icon"></i> SK Pegawai
        </a>
      </div>
    </div>

    <!-- TATA NASKAH -->
    <div class="item">
      <div class="title item">
        <i class="dropdown icon"></i> Tata Naskah
      </div>
      <div class="content">
        <a class="item" href="/tata_naskah/dashboard" data-spa="client" data-title="Tata Naskah/Dasboard">
          <i class="blue chart pie icon"></i> Dashboard
        </a>

        <a class="item" href="/tata_naskah/buat" data-spa="server" data-title="Tata Naskah/Buat Naskah">
          <i class="green pen square icon"></i> Buat Naskah
        </a>

        <a class="item" href="/tata_naskah/daftar" data-spa="client" data-title="Tata Naskah/Daftar Naskah">
          <i class="violet folder icon"></i> Daftar Naskah
        </a>
      </div>
    </div>

    <?php if (($_SESSION['user']['type_user'] ?? '') !== 'viewer'): ?>
    <a class="item" href="/halaman_berita" data-spa="client" data-title="Halaman Berita">
      <i class="newspaper icon"></i> Halaman Berita
    </a>

    <a class="item" href="/pengaturan" data-spa="client" data-title="Pengaturan">
      <i class="toolbox icon"></i> Pengaturan
    </a>
    <?php endif; ?>
    <?php if (($_SESSION['user']['type_user'] ?? '') === 'super_admin'): ?>
    <a class="item" href="/reset_tabel" data-spa="client" data-title="Reset Tabel Database">
      <i class="erase icon"></i> Reset Tabel
    </a>
    <a class="item" href="/user_pemda" data-spa="client" data-title="Pengaturan User Pemda">
      <i class="toolbox icon"></i> User Pemda
    </a>
    <?php endif; ?>
    <a class="item" href="/wallchat" data-spa="server" data-title="Wallchat">
      <i class="comments outline icon"></i> Pesan
    </a>

    <a class="item" href="/profil" data-spa="server" data-title="Profil">
      <i class="user icon"></i> Profil
    </a>

  </div>