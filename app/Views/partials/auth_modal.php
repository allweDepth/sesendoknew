<!-- ================= LOGIN MODAL ================= -->
<div class="ui small modal" id="loginModal">

  <!-- HEADER -->
  <div class="header">
    <i class="sign in alternate icon"></i>
    Login
  </div>

  <!-- CONTENT -->
  <div class="content">
    <form class="ui form" id="formLogin" method="POST" action="/login/proses">

      <div class="field">
        <label>Username</label>
        <div class="ui left icon input">
          <i class="user icon"></i>
          <input type="text" name="username" placeholder="Username">
        </div>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="ui left icon input">
          <i class="lock icon"></i>
          <input type="password" name="password" placeholder="Password">
        </div>
      </div>

    </form>
  </div>

  <!-- ACTIONS -->
  <div class="actions">
    <div class="ui fluid buttons">
      <button class="ui basic red cancel button">
        Batal
      </button>
      <button type="submit" form="formLogin" class="ui primary button">
        Login
      </button>
    </div>
  </div>

</div>
<!-- ================= REGISTER MODAL ================= -->
<div class="ui small modal" id="registerModal">

  <!-- HEADER (TETAP FIXED) -->
  <div class="header">
    <i class="user plus icon"></i>
    Register seSendok
    <div class="sub header">Manage Project</div>
  </div>

  <!-- CONTENT (BISA SCROLL) -->
  <div class="scrolling content">

    <form class="ui form" id="formRegister" method="POST" action="/register/proses">

      <!-- USERNAME -->
      <div class="field">
        <label>Username</label>
        <div class="ui left icon input">
          <i class="user icon"></i>
          <input type="text" name="username" placeholder="Username">
        </div>
      </div>

      <!-- EMAIL -->
      <div class="field">
        <label>Email</label>
        <div class="ui left icon input">
          <i class="envelope icon"></i>
          <input type="text" name="email" placeholder="Email address">
        </div>
      </div>

      <!-- NAMA -->
      <div class="field">
        <label>Nama Lengkap</label>
        <div class="ui left icon input">
          <i class="id badge icon"></i>
          <input type="text" name="nama" placeholder="Nama lengkap">
        </div>
      </div>

      <!-- NIP -->
      <div class="field">
        <label>NIP</label>
        <div class="ui left icon input">
          <i class="list icon"></i>
          <input type="text" name="nip" placeholder="NIP">
        </div>
      </div>

      <!-- KONTAK -->
      <div class="field">
        <label>Kontak Person</label>
        <div class="ui left icon input">
          <i class="phone icon"></i>
          <input type="text" name="kontak_person" placeholder="No. HP / WA">
        </div>
      </div>

      <!-- ALAMAT -->
      <div class="field">
        <label>Alamat</label>
        <div class="ui left icon input">
          <i class="home icon"></i>
          <input type="text" name="alamat" placeholder="Alamat lengkap">
        </div>
      </div>

      <!-- PASSWORD -->
      <div class="field">
        <label>Password</label>
        <div class="ui right labeled left icon input">
          <i class="lock icon"></i>
          <input type="password" name="password" placeholder="Password">
          <a onclick="changePassView()" class="ui basic label">
            <i class="eye icon"></i>
          </a>
        </div>
      </div>

      <!-- WILAYAH -->
      <div class="field">
        <label>Wilayah</label>
        <div class="ui fluid search selection dropdown" id="dropdownWilayah">
          <input type="hidden" name="kd_wilayah">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Wilayah</div>
          <div class="menu"></div>
        </div>
      </div>

      <!-- ORGANISASI -->
      <div class="field">
        <label>Organisasi</label>
        <div class="ui fluid search selection dropdown disabled" id="dropdownOrganisasi">
          <input type="hidden" name="kd_opd">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih Organisasi</div>
          <div class="menu"></div>
        </div>
      </div>

      <!-- CHECKBOX -->
      <div class="field">
        <div class="ui checkbox">
          <input type="checkbox" name="setuju">
          <label>
            Saya setuju dengan
            <a href="dok/syarat_dan_ketentuan_AHSP.pdf" target="_blank">
              ketentuan penggunaan
            </a>
          </label>
        </div>
      </div>

      <!-- ERROR MESSAGE -->
      <div class="ui error message"></div>

    </form>

  </div>

  <!-- ACTIONS (TETAP FIXED DI BAWAH) -->
  <div class="actions">

    <div class="ui fluid buttons">
      <button class="ui basic red cancel button">
        <i class="remove icon"></i>
        Batal
      </button>

      <button type="submit" form="formRegister" class="ui green button">
        <i class="check icon"></i>
        Register
      </button>
    </div>

  </div>

</div>