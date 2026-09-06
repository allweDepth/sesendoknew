<?php $active = 'berita'; ?>

<div class="ui container" style="padding:55px 0;max-width:1180px">

  <!-- HEADER -->
  <div class="ui center aligned basic segment">
    <span class="ui tiny blue label">PORTAL INFORMASI</span><h1 class="ui header" style="font-weight:800;font-size:2.6rem">Berita seSendok</h1>
    <div class="ui grey text">
      Informasi pembangunan dan perencanaan daerah
    </div>
  </div>

  <div class="ui hidden divider"></div>
  <div class="ui fluid large icon input" style="margin-bottom:24px"><input id="newsSearch" placeholder="Cari judul, kategori, atau isi berita..."><i class="search icon"></i></div>

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
          $gambar = $item['gambar'] ?: ($matches[1] ?? '/assets/img/umum/bg.jpeg');
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
              <div class="berita-full-content" style="display:none"><?= $item['uraian_html'] ?? '' ?></div>

            </div>

            <div class="extra content">
              <span class="ui tiny teal label">
                <?= $tahun ?>
              </span>

              <button type="button" class="ui right floated tiny primary basic button berita-more">
                Selengkapnya
              </button>
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
.berita-full-content{clear:both;line-height:1.6;margin-top:16px;overflow:auto}.berita-full-content img{max-width:100%;height:auto}.berita-full-content table{width:100%;border-collapse:collapse;margin:16px 0}.berita-full-content td,.berita-full-content th{border:1px solid #d5dde5;padding:8px}.berita-full-content .rde-chart{height:210px;display:flex;align-items:flex-end;gap:12px;padding:24px 20px 30px;border:1px solid #dfe8f0;border-radius:8px}.berita-full-content .rde-chart span{flex:1;min-width:24px;background:linear-gradient(#54c8ff,#2185d0);text-align:center;color:#fff;position:relative}.berita-full-content .rde-chart small{position:absolute;bottom:-22px;left:0;right:0;color:#555}.berita-full-content .rde-shape{display:flex;align-items:center;justify-content:center;padding:12px}.berita-full-content .circle{border-radius:50%}.berita-full-content .pill{border-radius:999px}
</style>
<script>
document.addEventListener('click',function(e){const button=e.target.closest('.berita-more');if(!button)return;const card=button.closest('.berita-card');const content=card.querySelector('.berita-full-content');content.style.display=content.style.display==='none'?'block':'none';button.textContent=content.style.display==='none'?'Selengkapnya':'Tutup';});document.querySelector('#newsSearch')?.addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('.berita-card').forEach(c=>c.closest('.column').style.display=c.innerText.toLowerCase().includes(q)?'':'none')});
</script>
