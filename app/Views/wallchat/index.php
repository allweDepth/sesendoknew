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
        <?php foreach (($feed['comments']??[]) as $comment): ?>
          <div class="ui small comments"><div class="comment"><div class="content"><b><?= htmlspecialchars($comment['nama']) ?></b><div class="text"><?= nl2br(htmlspecialchars($comment['content'])) ?></div></div></div></div>
        <?php endforeach; ?>

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

  <div class="ui segment">
    <h3 class="ui dividing header"><i class="lock icon"></i> Pesan Pribadi</h3>
    <div class="ui relaxed divided list">
      <?php foreach (($messages??[]) as $message): ?>
      <div class="item private-message-card" data-message-id="<?= (int)$message['id'] ?>"><i class="<?= !empty($message['read_at'])?'envelope open outline':'envelope outline' ?> icon"></i><div class="content"><div class="header"><?= htmlspecialchars($message['pengirim']) ?> → <?= htmlspecialchars($message['penerima']) ?> <?php if(!empty($message['is_ephemeral'])):?><span class="ui mini orange label">sementara</span><?php endif;?></div><div class="description"><?= nl2br(htmlspecialchars($message['content'])) ?></div><?php if(!empty($message['attachment_path'])):?><a class="ui mini basic button" href="<?= app_url('/wallchat/private/file?id='.(int)$message['id']) ?>"><i class="paperclip icon"></i><?= htmlspecialchars($message['attachment_name']) ?></a><?php endif;?><button class="ui mini basic green button btnReadPrivate" data-id="<?= (int)$message['id'] ?>"><i class="check icon"></i>Sudah dibaca</button><button class="ui mini basic red button btnDeletePrivate" data-id="<?= (int)$message['id'] ?>"><i class="trash icon"></i>Hapus dari saya</button></div></div>
      <?php endforeach; ?>
      <?php if(empty($messages)): ?><div class="ui message">Belum ada pesan pribadi.</div><?php endif; ?>
    </div>
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
    <form class="ui form" id="formPrivateMessage" enctype="multipart/form-data">

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

      <div class="two fields"><div class="field"><label>Lampiran (maks. 3 MB)</label><input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.xlsx"></div><div class="field"><label>Privasi</label><div class="ui toggle checkbox"><input type="checkbox" name="is_ephemeral" value="1"><label>Pesan sementara—hilang setelah penerima membaca</label></div></div></div>

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
