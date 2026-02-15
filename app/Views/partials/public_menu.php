<div class="ui secondary pointing menu">

    <a href="/berita" class="item <?= ($active ?? '') == 'berita' ? 'active' : '' ?>">
      Berita
    </a>

    <a href="/datateknis" class="item <?= ($active ?? '') == 'datateknis' ? 'active' : '' ?>">
      Data Teknis
    </a>

    <a href="/organisasi" class="item <?= ($active ?? '') == 'organisasi' ? 'active' : '' ?>">
      Organisasi
    </a>

    <a href="/pelayanan" class="item <?= ($active ?? '') == 'pelayanan' ? 'active' : '' ?>">
      Pelayanan
    </a>

    <div class="right menu">
      <div class="ui dropdown item">
        Account
        <i class="dropdown icon"></i>
        <div class="menu">
          <div class="item" id="btnLogin">Login</div>
          <div class="item" id="btnRegister">Register</div>
        </div>
      </div>
    </div>

  </div>