<?php $active = 'organisasi'; ?>
<?php
function randomAvatar() {
    $path = __DIR__ . '/../../../public/assets/img/avatar/large/';
    $images = glob($path . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

    if (!$images) {
        return '/assets/img/avatar/default.png';
    }

    return '/assets/img/avatar/large/' . basename($images[array_rand($images)]);
}
?>
<div class="ui container" style="margin-top:60px; max-width:1200px">

  <h2 class="ui center aligned header">
    Struktur Organisasi  
    <div class="sub header">Dinas PUPR Kabupaten Pasangkayu</div>
  </h2>

  <div class="ui divider"></div>

  <!-- ===================== -->
  <!-- KEPALA DINAS -->
  <!-- ===================== -->
  <div class="ui center aligned segment basic">
    <div class="ui centered card profil-card utama">
      <div class="image">
        <img src="/assets/img/avatar/default.jpeg">
      </div>
      <div class="content">
        <div class="header">SYAMSUNAR, SP., M.M</div>
        <div class="meta">Kepala Dinas</div>
        <div class="description">
          NIP. 197503102009031001<br>
          Pembina, IV/a
        </div>
      </div>
    </div>
  </div>

  <!-- ===================== -->
  <!-- SEKRETARIS -->
  <!-- ===================== -->
  <div class="ui center aligned segment basic">
    <div class="ui centered card profil-card">
      <div class="image">
        <img src="/assets/img/avatar/large/nan.jpg">
      </div>
      <div class="content">
        <div class="header">SRI IRDA AYU, SP., M.Si</div>
        <div class="meta">Sekretaris Dinas</div>
        <div class="description">
          NIP. 198306252005022003<br>
          Pembina Tk.I, IV/b
        </div>
      </div>
    </div>
  </div>

  <div class="ui divider"></div>

  <!-- ===================== -->
  <!-- PARA KABID -->
  <!-- ===================== -->
  <h3 class="ui header">Kepala Bidang</h3>

  <div class="ui five stackable cards">

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">KAMARUDDIN, ST., M.P.W.P</div>
        <div class="meta">Kabid Pembinaan Jasa Konstruksi</div>
        <div class="description">
          NIP. 197308142005021003<br>
          Pembina IV/a
        </div>
      </div>
    </div>

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">BACHTIAR B SUMAY, SH</div>
        <div class="meta">Kabid Bina Marga</div>
        <div class="description">
          NIP. 197409192006041007<br>
          Pembina IV/a
        </div>
      </div>
    </div>

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">I NYOMAN SUMERTA, ST., M.P.W.P</div>
        <div class="meta">Kabid Sumber Daya Air</div>
        <div class="description">
          NIP. 198301012010011020<br>
          Penata Tk.I, III/d
        </div>
      </div>
    </div>

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">NURMADINA, S.Sos., M.SI</div>
        <div class="meta">Kabid Tata Ruang</div>
        <div class="description">
          NIP. 198208022003122003<br>
          Pembina IV/a
        </div>
      </div>
    </div>

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">NARDIN, ST</div>
        <div class="meta">Kabid Cipta Karya</div>
        <div class="description">
          NIP. 197910012011011004<br>
          Penata Tk.I, III/d
        </div>
      </div>
    </div>

  </div>

  <div class="ui divider"></div>

  <!-- ===================== -->
  <!-- KASUBAG & UPTD -->
  <!-- ===================== -->
  <h3 class="ui header">Kasubag & UPTD</h3>

  <div class="ui six stackable cards">

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">FELIX DATUAN, ST</div>
        <div class="meta">Kasubag Keuangan</div>
        <div class="description">
          NIP. 198407252009031001<br>
          Penata Tk.I, III/d
        </div>
      </div>
    </div>

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">IRMAWATI, SE., M.P.W.P</div>
        <div class="meta">Kasubag Umum & Kepegawaian</div>
        <div class="description">
          NIP. 197601032006042007<br>
          Penata Tk.I, III/d
        </div>
      </div>
    </div>

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">I MADE SULAWA, SE., M.P.W.P</div>
        <div class="meta">Kepala UPTD Pengelolaan Limbah Domestik</div>
        <div class="description">
          NIP. 197307132009031001<br>
          Penata Tk.I, III/d
        </div>
      </div>
    </div>

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">AS'AT, ST</div>
        <div class="meta">Kasubag TU UPTD Pengelolaan Air Limbah Domestik</div>
        <div class="description">
          NIP. 197109072014091001<br>
          Penata, III/c
        </div>
      </div>
    </div>

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">MUNAWIR Z, SH</div>
        <div class="meta">Kepala UPTD Pengelolaan Air Bersih</div>
        <div class="description">
          NIP. 198305022007011008<br>
          Penata, III/c
        </div>
      </div>
    </div>

    <div class="card profil-card">
      <div class="image">
        <img src="<?= randomAvatar(); ?>">
      </div>
      <div class="content">
        <div class="header">FUADY KAHAR, SH</div>
        <div class="meta">Kasubag TU UPTD Pengelolaan Air Bersih</div>
        <div class="description">
          NIP. 198308082012121002<br>
          Penata, III/c
        </div>
      </div>
    </div>

  </div>

</div>

<style>
.profil-card {
  border-radius: 14px !important;
  transition: all 0.35s ease;
  box-shadow: 0 4px 14px rgba(0,0,0,0.05);
}

.profil-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 14px 30px rgba(0,0,0,0.18);
}

.profil-card img {
  height: 220px;
  object-fit: cover;
}

.profil-card.utama {
  width: 300px !important;
}
</style>