<div class="ui container">

  <!-- HEADER -->
  <div class="ui clearing segment">
    <h2 class="ui left floated header">
      <i class="comments icon"></i>
      Wallchat
    </h2>

    <button class="ui right floated teal button" id="btnPrivateMessage">
      <i class="envelope icon"></i>
      Kirim Pesan Pribadi
    </button>
  </div>

  <!-- FORM POST -->
  <form class="ui form" id="formPost">
    <div class="field">
      <textarea name="content" placeholder="Tulis status..."></textarea>
    </div>
    <input type="hidden" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <button class="ui primary button">
      <i class="send icon"></i> Posting
    </button>
  </form>

  <div class="ui divider"></div>

  <!-- FEED -->
  <div class="ui feed" id="feedContainer">

    <?php foreach ($feeds as $feed): ?>

    <div class="event">
      <div class="label">
        <i class="user circle icon big"></i>
      </div>

      <div class="content">

        <div class="summary">
          <strong><?= $feed['nama'] ?? 'User'; ?></strong>

          <div class="date">
            <?= date('d M Y H:i', strtotime($feed['created_at'])); ?>
          </div>
        </div>

        <div class="extra text">
          <?= nl2br(htmlspecialchars($feed['content'])); ?>
        </div>

        <!-- KOMENTAR -->
        <div class="extra">
          <form class="ui reply form formComment" data-id="<?= $feed['id']; ?>">
            <div class="field">
              <input type="text" name="content" placeholder="Tulis komentar...">
            </div>
          </form>
        </div>

      </div>
    </div>

    <div class="ui divider"></div>

    <?php endforeach; ?>

  </div>

</div>

<!-- ==============================
     MODAL PESAN PRIBADI
================================= -->

<div class="ui modal" id="modalPrivateMessage">
  <i class="close icon"></i>

  <div class="header">
    Kirim Pesan Pribadi
  </div>

  <div class="content">
    <form class="ui form" id="formPrivateMessage">

      <div class="field">
        <label>Pilih Penerima</label>
        <div class="ui fluid search selection dropdown">
          <input type="hidden" name="receiver_id">
          <i class="dropdown icon"></i>
          <div class="default text">Pilih User</div>
          <div class="menu">
            <?php if (!empty($users)): ?>
            <?php foreach ($users as $user): ?>

            <div class="item" data-value="<?= $user['id']; ?>">
              <?= htmlspecialchars($user['nama']); ?>
              <!-- FIX: kolom tabel adalah 'nama' -->
            </div>

            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="field">
        <label>Pesan</label>
        <textarea name="content" placeholder="Tulis pesan..."></textarea>
      </div>

      <button class="ui primary button">
        <i class="send icon"></i> Kirim
      </button>

    </form>
  </div>
</div>

<!-- ==============================
     JAVASCRIPT INTERAKTIF
================================= -->