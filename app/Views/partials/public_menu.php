<header class="portal-header">
  <nav class="ui container public-nav" aria-label="Navigasi utama">
    <a href="<?= $escape($pageUrl('home')) ?>" class="brand">
      <img src="<?= app_url('/assets/img/umum/logo.png') ?>" alt="" width="40" height="43">
      <span><b>seSendok</b><small>Kabupaten Pasangkayu</small></span>
    </a>
    <div class="public-links" id="public-navigation">
      <?php foreach ($pages as $type => [, $name]): ?>
        <a href="<?= $escape($pageUrl($type)) ?>" class="item<?= $active === $type ? ' active' : '' ?>" <?= $active === $type ? 'aria-current="page"' : '' ?>><?= $escape($name) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="public-account">
      <?php if (!empty($_SESSION['user'])): ?>
        <a class="ui button portal-primary" href="<?= app_url('/dashboard') ?>">Dashboard</a>
        <form method="POST" action="<?= app_url('/logout') ?>">
          <input type="hidden" name="_csrf" value="<?= $escape($_SESSION['csrf_token'] ?? '') ?>">
          <button class="ui basic button" type="submit">Logout</button>
        </form>
      <?php else: ?>
        <button type="button" class="ui basic button" id="btnLogin">Masuk</button>
        <button type="button" class="ui button portal-primary" id="btnRegister">Daftar</button>
      <?php endif; ?>
    </div>
    <button type="button" class="ui icon basic button nav-toggle" aria-label="Menu navigasi" aria-controls="public-navigation" aria-expanded="false"><i class="bars icon" aria-hidden="true"></i></button>
  </nav>
</header>
