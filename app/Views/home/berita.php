<?php $active = 'berita'; ?>

<div class="ui container" style="margin-top:50px; max-width:1100px">

  <!-- HEADER -->
  <div class="ui center aligned basic segment">
    <h1 class="ui header" style="font-weight:600;">
      Berita seSendok
    </h1>
    <div class="ui grey text">
      Informasi pembangunan dan perencanaan daerah
    </div>
  </div>

  <div class="ui hidden divider"></div>

  <?php if (!empty($berita)): ?>

    <div class="ui stackable two column grid">

      <?php foreach ($berita as $item): ?>

        <?php
          $judul = htmlspecialchars($item['judul'] ?? '');
          $kelompok = htmlspecialchars($item['kelompok'] ?? '');
          $ringkas = $item['uraian_singkat']
                      ? htmlspecialchars($item['uraian_singkat'])
                      : substr(strip_tags($item['uraian_html'] ?? ''), 0, 150) . '...';

          $tanggalRaw = $item['tanggal'] ?? null;
          $tanggal = $tanggalRaw ? date('d F Y', strtotime($tanggalRaw)) : '-';
          $tahun = $tanggalRaw ? date('Y', strtotime($tanggalRaw)) : '-';

          // Ambil gambar dari uraian_html jika ada
          preg_match('/<img.*?src=["\'](.*?)["\']/', $item['uraian_html'] ?? '', $matches);
          $gambar = $matches[1] ?? "https://source.unsplash.com/600x400/?" . urlencode($kelompok);
        ?>

        <div class="column">
          <div class="ui fluid card berita-card">

            <!-- IMAGE -->
            <div class="image berita-image">
              <img src="<?= $gambar ?>" alt="<?= $judul ?>">
            </div>

            <div class="content">

              <!-- Badge -->
              <div class="right floated meta">
                <span class="ui tiny basic blue label">
                  <?= $kelompok ?>
                </span>
              </div>

              <!-- Judul -->
              <div class="header berita-title">
                <?= $judul ?>
              </div>

              <!-- Meta -->
              <div class="meta berita-meta">
                <i class="calendar alternate outline icon"></i>
                <?= $tanggal ?>
              </div>

              <div class="description berita-desc">
                <?= $ringkas ?>
              </div>

            </div>

            <div class="extra content">
              <span class="ui tiny teal label">
                <?= $tahun ?>
              </span>

              <div class="ui right floated tiny primary basic button">
                Selengkapnya
              </div>
            </div>

          </div>
        </div>

      <?php endforeach; ?>

    </div>

  <?php else: ?>

    <div class="ui placeholder segment">
      <div class="ui icon header">
        <i class="newspaper outline icon"></i>
        Belum ada berita tersedia
      </div>
    </div>

  <?php endif; ?>

</div>

<style>
.berita-card {
  border-radius: 14px !important;
  overflow: hidden;
  transition: all 0.35s ease;
  box-shadow: 0 3px 12px rgba(0,0,0,0.05);
}

.berita-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 14px 30px rgba(0,0,0,0.15);
}

.berita-image img {
  height: 220px;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.berita-card:hover .berita-image img {
  transform: scale(1.05);
}

.berita-title {
  font-weight: 600 !important;
  margin-top: 5px !important;
}

.berita-meta {
  font-size: 13px;
  color: #777;
  margin-bottom: 10px;
}

.berita-desc {
  color: #555;
  line-height: 1.6;
}
</style>