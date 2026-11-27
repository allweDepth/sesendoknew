<?php
[, $label, $icon, $description] = $pages[$active];
$isHome = $active === 'home';
$feature = $isHome ? ($items[0] ?? null) : null;
$heading = $feature['judul'] ?? ($isHome ? 'Infrastruktur Pasangkayu' : $label);
$intro = $feature['summary'] ?? ($isHome ? 'Informasi Dinas Pekerjaan Umum dan Penataan Ruang Kabupaten Pasangkayu.' : $description);
$heroImage = $feature['cover'] ?? $sectionCovers[$active];
$filters = [];
foreach ($items as $item) {
    $filter = $active === 'berita' ? $pages[$item['jenis_halaman']][1] : $item['kelompok'];
    $filters[$filter] = true;
}
?>

<?php if ($articleKey !== null): ?>
  <div class="ui container portal-reading">
    <a class="ui basic button portal-back" href="<?= $escape($pageUrl($active)) ?>"><i class="left arrow icon" aria-hidden="true"></i>Kembali ke <?= $escape($label) ?></a>
    <?php if (!$article): ?>
      <div class="ui message"><h1>Artikel tidak tersedia</h1><p>Temukan artikel yang tersedia di <?= $escape($label) ?>.</p></div>
    <?php else: ?>
      <?php $section = $pages[$article['jenis_halaman']]; ?>
      <article class="reading-article" data-reading data-section="<?= $escape($article['jenis_halaman']) ?>">
        <header>
          <div class="article-tags">
            <a class="ui label portal-tag" href="<?= $escape($pageUrl($article['jenis_halaman'])) ?>"><?= $escape($section[1]) ?></a>
            <span class="ui basic label portal-topic"><?= $escape($article['kelompok']) ?></span>
          </div>
          <h1><?= $escape($article['judul']) ?></h1>
          <p class="reading-byline">Portal Dinas PUPR Kabupaten Pasangkayu</p>
        </header>
        <?php if ($article['cover']): ?>
          <figure class="reading-cover<?= $article['portrait'] ? ' reading-cover-portrait' : ($article['regional_profile'] ? ' reading-cover-regional' : '') ?>">
            <img src="<?= $escape($article['cover']) ?>" alt="<?= $article['portrait'] || $article['regional_profile'] ? $escape($article['judul']) : '' ?>">
            <?php if (isset($article['cover_caption'])): ?><figcaption><?= $escape($article['cover_caption']) ?></figcaption><?php endif; ?>
          </figure>
        <?php endif; ?>
        <div class="reading-body"><?= $article['body'] ?></div>
        <?php if ($article['portrait'] && $regionalProfile): ?>
          <p class="reading-byline"><a href="<?= $escape($regionalProfile['url']) ?>">Profil Bupati dan Wakil Bupati serta visi-misi Pasangkayu <i class="right arrow icon" aria-hidden="true"></i></a></p>
        <?php endif; ?>
      </article>
    <?php endif; ?>
  </div>
  <?php return; ?>
<?php endif; ?>

<section class="portal-hero <?= $isHome ? 'portal-hero-home' : 'portal-hero-section' ?>" data-parallax>
  <img class="hero-image" src="<?= $escape($heroImage) ?>" alt="" fetchpriority="high">
  <div class="ui container hero-content">
    <div class="hero-kicker"><span></span><?= $isHome ? 'DINAS PUPR KABUPATEN PASANGKAYU' : 'PORTAL PUBLIK PUPR PASANGKAYU' ?></div>
    <h1><?= $escape($heading) ?></h1>
    <p><?= $escape($intro) ?></p>
    <?php if ($isHome): ?>
      <div class="hero-actions">
        <a class="ui large button portal-primary" href="<?= $escape($pageUrl('data_teknis')) ?>">Jelajahi data <i class="right arrow icon" aria-hidden="true"></i></a>
        <?php if ($feature): ?>
          <a class="ui large inverted basic button" href="<?= $escape($feature['url']) ?>">Baca sorotan</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="hero-symbol" aria-hidden="true"><i class="<?= $escape($icon) ?> icon"></i></div>
    <?php endif; ?>
  </div>
  <?php if (str_starts_with($heroImage, '/assets/img/portal/')): ?><span class="hero-caption"><?= str_ends_with($heroImage, '-konsep.webp') ? 'Visualisasi lingkungan' : 'Ilustrasi editorial' ?></span><?php endif; ?>
</section>

<?php if ($regionalProfile): ?>
  <section class="ui container portal-block home-headline regional-profile" aria-labelledby="regional-title">
    <article class="ui fluid card headline-card">
      <figure>
        <img src="<?= $escape($regionalProfile['cover']) ?>" alt="<?= $escape($regionalProfile['judul']) ?>" loading="lazy" decoding="async">
        <figcaption><?= $escape($regionalProfile['cover_caption']) ?></figcaption>
      </figure>
      <div class="content">
        <h2 id="regional-title"><?= $escape($regionalProfile['judul']) ?></h2>
        <p><?= $escape($regionalProfile['summary']) ?></p>
        <a class="ui button portal-primary" href="<?= $escape($regionalProfile['url']) ?>">Lihat profil dan visi-misi <i class="right arrow icon" aria-hidden="true"></i></a>
      </div>
    </article>
  </section>
<?php endif; ?>

<?php if ($isHome): ?>
  <section class="ui container portal-block home-topics" aria-labelledby="portal-topics">
    <h2 id="portal-topics">Jelajahi portal</h2>
    <div class="topic-grid">
      <?php foreach (array_slice($pages, 1) as $type => [, $name, $symbol]): ?>
        <a class="topic-link" href="<?= $escape($pageUrl($type)) ?>" data-section="<?= $escape($type) ?>">
          <span class="topic-icon"><i class="<?= $escape($symbol) ?> icon" aria-hidden="true"></i></span>
          <strong><?= $escape($name) ?></strong>
          <i class="right arrow icon topic-arrow" aria-hidden="true"></i>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php if ($headline): ?>
    <section class="ui container portal-block home-headline" aria-labelledby="home-headline-title">
      <article class="ui fluid card headline-card">
        <img class="headline-cover" src="<?= $escape($headline['cover']) ?>" alt="" loading="lazy" decoding="async">
        <div class="content">
          <span class="ui label portal-tag">Berita utama</span>
          <h2 id="home-headline-title"><?= $escape($headline['judul']) ?></h2>
          <p><?= $escape($headline['summary']) ?></p>
          <a class="ui button portal-primary" href="<?= $escape($headline['url']) ?>">Baca berita utama <i class="right arrow icon" aria-hidden="true"></i></a>
        </div>
      </article>
    </section>
  <?php endif; ?>

  <?php if ($leadership): ?>
    <section class="ui container portal-block home-leadership" aria-labelledby="portal-leadership">
      <h2 id="portal-leadership">Pimpinan daerah dan Dinas PUPR</h2>
      <div class="leadership-grid">
        <?php foreach ($leadership as $profile): ?>
          <article class="ui fluid card leadership-card">
            <div class="leadership-image">
              <img src="<?= $escape($profile['cover']) ?>" alt="<?= $escape($profile['judul']) ?>" loading="lazy" decoding="async">
            </div>
            <div class="content leadership-nameplate">
              <p><?= $escape($profile['kelompok']) ?></p>
              <h3><a href="<?= $escape($profile['url']) ?>"><?= $escape($profile['judul']) ?></a></h3>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
  <?php if (count($items) < 2) return; ?>
  <?php $items = array_slice($items, 1); ?>
<?php endif; ?>

<section class="ui container portal-block library" data-library aria-labelledby="library-title">
  <div class="library-head">
    <h2 id="library-title"><?= $isHome ? 'Pilihan Beranda' : (match ($active) { 'berita' => 'Arsip artikel', 'pelayanan' => 'Layanan dan panduan', default => 'Jelajahi ' . $label }) ?></h2>
    <label class="ui search icon input library-search">
      <span class="portal-sr-only">Cari artikel</span>
      <input type="search" placeholder="Cari artikel…" data-search>
      <i class="search icon" aria-hidden="true"></i>
    </label>
  </div>
  <?php if (!$isHome && count($filters) > 1): ?>
    <div class="portal-filters" role="group" aria-label="Filter artikel">
      <button class="ui button portal-filter is-selected" type="button" aria-pressed="true" data-filter="">Semua</button>
      <?php foreach (array_keys($filters) as $filter): ?>
        <button class="ui button portal-filter" type="button" aria-pressed="false" data-filter="<?= $escape($filter) ?>"><?= $escape($filter) ?></button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <p class="library-count" aria-live="polite" data-count><?= count($items) ?> artikel</p>

  <?php
  $groups = ['' => $items];
  if ($active === 'pelayanan') {
      $groups = [
          'Layanan utama' => array_values(array_filter($items, static fn ($item) => $item['featured_service'])),
          'Panduan lainnya' => array_values(array_filter($items, static fn ($item) => !$item['featured_service'])),
      ];
  }
  ?>
  <?php foreach ($groups as $groupTitle => $group): ?>
    <?php if (!$group) continue; ?>
    <div class="library-group" data-card-group>
      <?php if ($groupTitle): ?><h3 class="library-group-title"><?= $escape($groupTitle) ?></h3><?php endif; ?>
      <div class="library-grid" data-library-section="<?= $escape($active) ?>">
        <?php foreach ($group as $index => $item): ?>
          <?php
          $type = $item['jenis_halaman'];
          [, $sectionName, $sectionIcon] = $pages[$type];
          $filter = $active === 'berita' ? $sectionName : $item['kelompok'];
          ?>
          <article class="ui fluid card portal-card<?= $active === 'pelayanan' && $item['featured_service'] ? ' portal-card-service' : '' ?><?= $index === 0 && ($active === 'berita' || $isHome) && $item['cover'] ? ' portal-card-lead' : '' ?>" data-card data-section="<?= $escape($type) ?>"
            data-category="<?= $escape($filter) ?>" data-keywords="<?= $escape($item['search']) ?>">
            <a class="portal-card-art<?= $item['portrait'] ? ' portal-portrait-art' : '' ?>" href="<?= $escape($item['url']) ?>" tabindex="-1" aria-hidden="true">
              <?php if ($item['cover']): ?>
                <img src="<?= $escape($item['cover']) ?>" alt="" decoding="async" loading="lazy">
              <?php else: ?>
                <i class="<?= $escape($sectionIcon) ?> icon"></i>
              <?php endif; ?>
            </a>
            <div class="content portal-card-content">
              <div class="article-tags">
                <span class="ui label portal-tag"><?= $escape($sectionName) ?></span>
                <span class="ui basic label portal-topic"><?= $escape($item['kelompok']) ?></span>
              </div>
              <h3><a href="<?= $escape($item['url']) ?>"><?= $escape($item['judul']) ?></a></h3>
              <p><?= $escape($item['summary']) ?></p>
              <a class="card-action" href="<?= $escape($item['url']) ?>" aria-label="<?= $escape('Baca ' . $item['judul']) ?>"><?= $active === 'pelayanan' ? 'Buka panduan' : 'Baca artikel' ?> <i class="right arrow icon" aria-hidden="true"></i></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="ui message portal-empty" data-empty <?= $items ? 'hidden' : '' ?>>
    <p><?= $items ? 'Tidak ada artikel yang cocok.' : 'Belum ada artikel yang diterbitkan di halaman ini.' ?></p>
    <?php if ($items): ?><button type="button" class="ui basic button" data-reset>Hapus filter</button><?php endif; ?>
  </div>
</section>
