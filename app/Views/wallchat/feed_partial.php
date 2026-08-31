<?php foreach ($feeds as $feed): ?>

<div class="event">
  <div class="label">
    <i class="user circle icon big"></i>
  </div>

  <div class="content">

    <div class="summary">

      <strong><?= $feed['nama'] ?></strong>

      <div class="date">
        <?= date('d M Y H:i', strtotime($feed['created_at'])) ?>
      </div>

      <?php if ($feed['user_id'] == $_SESSION['user']['id']): ?>

      <!-- tombol edit -->
      <a class="ui mini icon button btnEditFeed" data-id="<?= $feed['id'] ?>">
        <i class="edit icon"></i>
      </a>

      <!-- tombol delete -->
      <a class="ui mini red icon button btnDeleteFeed" data-id="<?= $feed['id'] ?>">
        <i class="trash icon"></i>
      </a>

      <?php endif; ?>

    </div>

    <div class="extra text">
      <?= nl2br(htmlspecialchars($feed['content'])); ?>
    </div>
    <?php foreach (($feed['comments']??[]) as $comment): ?>
      <div class="ui small comments"><div class="comment"><div class="content"><b><?= htmlspecialchars($comment['nama']) ?></b><div class="text"><?= nl2br(htmlspecialchars($comment['content'])) ?></div></div></div></div>
    <?php endforeach; ?>
    <form class="ui reply form formComment" data-id="<?= $feed['id'] ?>"><div class="field"><input type="text" name="content" placeholder="Tulis komentar..."></div></form>

  </div>
</div>

<div class="ui divider"></div>

<?php endforeach; ?>
