<?php $active = 'pelayanan'; ?>
<div class="ui container" style="margin-top:60px; max-width:1100px">

  <h2 class="ui center aligned header">
    Layanan Dinas PUPR Kabupaten Pasangkayu
    <div class="sub header">
      Pelayanan publik bidang pekerjaan umum dan penataan ruang
    </div>
  </h2>

  <div class="ui divider"></div>

  <div class="ui four stackable cards">

    <!-- IMB -->
    <div class="card layanan-card">
      <div class="image">
        <img src="https://source.unsplash.com/600x400/?building,architecture">
      </div>
      <div class="content">
        <div class="header">IMB / Persetujuan Bangunan Gedung</div>
        <div class="meta">Perizinan</div>
        <div class="description">
          Pelayanan penerbitan izin mendirikan bangunan sesuai ketentuan tata ruang dan standar teknis bangunan.
        </div>
      </div>
      <div class="extra content">
        <div class="ui tiny primary basic button">
          Detail Layanan
        </div>
      </div>
    </div>

    <!-- AIR BERSIH -->
    <div class="card layanan-card">
      <div class="image">
        <img src="https://source.unsplash.com/600x400/?clean-water,water">
      </div>
      <div class="content">
        <div class="header">Pengelolaan Air Bersih</div>
        <div class="meta">SPAM</div>
        <div class="description">
          Pengelolaan dan distribusi air bersih untuk masyarakat guna meningkatkan akses sanitasi dan kesehatan lingkungan.
        </div>
      </div>
      <div class="extra content">
        <div class="ui tiny primary basic button">
          Detail Layanan
        </div>
      </div>
    </div>

    <!-- AIR LIMBAH -->
    <div class="card layanan-card">
      <div class="image">
        <img src="https://source.unsplash.com/600x400/?wastewater,treatment">
      </div>
      <div class="content">
        <div class="header">Pengelolaan Air Limbah Domestik</div>
        <div class="meta">Sanitasi</div>
        <div class="description">
          Pelayanan pengelolaan limbah domestik untuk menjaga kebersihan lingkungan dan kesehatan masyarakat.
        </div>
      </div>
      <div class="extra content">
        <div class="ui tiny primary basic button">
          Detail Layanan
        </div>
      </div>
    </div>

    <!-- ALAT BERAT -->
    <div class="card layanan-card">
      <div class="image">
        <img src="https://source.unsplash.com/600x400/?heavy-equipment,construction">
      </div>
      <div class="content">
        <div class="header">Peralatan Alat Berat & Laboratorium</div>
        <div class="meta">Teknis</div>
        <div class="description">
          Penyediaan layanan peralatan alat berat serta laboratorium pengujian untuk mendukung pembangunan infrastruktur.
        </div>
      </div>
      <div class="extra content">
        <div class="ui tiny primary basic button">
          Detail Layanan
        </div>
      </div>
    </div>

  </div>

</div>

<style>
.layanan-card {
  border-radius: 14px !important;
  overflow: hidden;
  transition: all 0.35s ease;
  box-shadow: 0 3px 12px rgba(0,0,0,0.06);
}

.layanan-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 30px rgba(0,0,0,0.18);
}

.layanan-card img {
  height: 220px;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.layanan-card:hover img {
  transform: scale(1.05);
}
</style>