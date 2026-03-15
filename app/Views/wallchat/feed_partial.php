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

  </div>
</div>

<div class="ui divider"></div>

<?php endforeach; ?>