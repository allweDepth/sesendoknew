<!DOCTYPE html>
<html>

<head>
  <link rel="stylesheet" href="/assets/css/fomantic.min.css">
  <script src="/assets/js/jquery.min.js"></script>
  <script src="/assets/js/fomantic.min.js"></script>
</head>

<body>

  <div class="ui secondary pointing menu">
    <a href="/berita" class="item">Berita</a>
    <a href="/organisasi" class="item">Organisasi</a>
    <a href="/pelayanan" class="item">Pelayanan</a>

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

  <div class="ui container">
    <h2 class="ui header">Berita</h2>
    <p>Isi berita...</p>

    <h2 class="ui header">Organisasi</h2>
    <p>Isi organisasi...</p>

    <h2 class="ui header">Pelayanan</h2>
    <p>Isi pelayanan...</p>
  </div>

  <!-- LOGIN MODAL -->
  <div class="ui modal" id="loginModal">
    <div class="header">Login</div>
    <div class="content">
      <form class="ui form" method="POST" action="/login/proses">
        <div class="field">
          <label>Username</label>
          <input type="text" name="username">
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="password">
        </div>
        <button class="ui primary button">Login</button>
      </form>
    </div>
  </div>
  <!-- REGISTER MODAL -->
  <div class="ui modal" id="registerModal">
    <form class="ui form" method="POST" action="/register/proses">
      <div class="ui stacked segment">
        <h2 class="ui teal image header"><i class="home icon"></i>
          <div class="content"> Register seSendok
            <div class="sub header"> Manage project </div>
          </div>
        </h2>
        <div class="field">
          <div class="ui left icon input"> <i class="user icon"></i>
            <input type="text" name="username" placeholder="username">
          </div>
        </div>
        <div class="field">
          <div class="ui left icon input"> <i class="envelope open icon"></i>
            <input type="text" name="email" placeholder="E-mail address">
          </div>
        </div>
        <div class="field">
          <div class="ui left icon input"> <i class="user icon"></i>
            <input type="text" name="nama" placeholder="Nama Lengkap">
          </div>
        </div>
        <div class="field">
          <div class="ui left icon input"> <i class="list icon"></i>
            <input type="text" name="nip" placeholder="NIP">
          </div>
        </div>
        <div class="field">
          <div class="ui left icon input"> <i class="fax icon"></i>
            <input type="text" name="kontak_person" placeholder="kontak person">
          </div>
        </div>
        <div class="field">
          <div class="ui left icon input"> <i class="home icon"></i>
            <input type="text" name="alamat" placeholder="alamat">
          </div>
        </div>
        <div class="field">
          <div class="ui right labeled left icon input">
            <i class="lock icon"></i>
            <input type="password" name="password" placeholder="Password">
            <a onclick="changePassView()" class="ui tag label">
              <i class="eye icon"></i>
            </a>
          </div>
        </div>
        <div class="field">
          <div class="ui fluid search selection wilayah dropdown ajx">
            <input type="hidden" name="kd_wilayah" class="noselection">
            <i class="dropdown icon"></i>
            <input class="search" autocomplete="off" tabindex="0">
            <div class="default text"><i class="fax icon"></i>Wilayah</div>
            <div class="menu hidden transition" tabindex="-1">

            </div>
          </div>
        </div>
        <div class="field">
          <div class="ui fluid search selection organisasi dropdown ajx">
            <input type="hidden" name="organisasi" class="noselection">
            <i class="dropdown icon"></i>
            <input class="search" autocomplete="off" tabindex="0">
            <div class="default text"><i class="fax icon"></i>Organisasi</div>
            <div class="menu" tabindex="-1">

            </div>
          </div>
        </div>

        <div class="inline field">
          <div class="ui checkbox">
            <input type="checkbox" name="setuju" checked="">
            <label href="dok/Basic Price 1 Header.xlsx" target="_blank">Saya setuju dengan <a href="dok/syarat_dan_ketentuan_AHSP.pdf" target="_blank">ketentuan</a> penggunaan aplikasi ini</label>
          </div>
        </div>

      </div>
      <div class="ui error message"></div>
      <div class="actions">
        <div class="ui red basic cancel inverted button">
          <i class="remove icon"></i>
          Batal
        </div>

        <button type="submit" class="ui green inverted button">
          <i class="checkmark icon"></i>
          Register
        </button>
      </div>
    </form>
  </div>
  <script>
    $('.ui.dropdown').dropdown();

    $('#btnLogin').click(function() {
      $('#loginModal').modal('show');
    });
    $('#btnRegister').click(function() {
      $('#registerModal').modal('show');
    });
    $('#registerModal').modal({
      closable: false
    });
  </script>

</body>

</html>