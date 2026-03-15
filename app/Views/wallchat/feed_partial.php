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

      <?php if ($feed['user_id'] == $_SESSION['user_id']): ?>

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

  </div>
</div>

<div class="ui divider"></div>

<?php endforeach; ?>