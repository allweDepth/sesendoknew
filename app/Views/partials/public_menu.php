<nav class="ui container public-nav"><a href="<?= app_url('/') ?>" class="brand"><img src="<?= app_url('/assets/img/umum/logo.png') ?>" alt="Logo"><span><b>seSendok</b><small>Pasangkayu</small></span></a><div class="public-links">
    <a href="<?= app_url('/') ?>" class="item <?= ($active ?? '') == 'home' ? 'active' : '' ?>">Home</a>

    <a href="<?= app_url('/berita') ?>" class="item <?= ($active ?? '') == 'berita' ? 'active' : '' ?>">
      Berita
    </a>

    <a href="<?= app_url('/datateknis') ?>" class="item <?= ($active ?? '') == 'datateknis' ? 'active' : '' ?>">
      Data
    </a>

    <a href="<?= app_url('/organisasi') ?>" class="item <?= ($active ?? '') == 'organisasi' ? 'active' : '' ?>">
      Struktur Organisasi
    </a>

    <a href="<?= app_url('/pelayanan') ?>" class="item <?= ($active ?? '') == 'pelayanan' ? 'active' : '' ?>">
      Pelayanan
    </a>

    </div><div class="public-account"><?php if(!empty($_SESSION['user'])):?><a class="ui primary button" href="<?= app_url('/dashboard') ?>">Dashboard</a><a class="ui basic button" href="<?= app_url('/logout') ?>">Logout</a><?php else:?><button class="ui basic button" id="btnLogin"><i class="sign in icon"></i>Login</button><button class="ui primary button" id="btnRegister">Daftar</button><?php endif;?></div><button class="nav-toggle" aria-label="Buka menu"><i class="bars icon"></i></button></nav>
