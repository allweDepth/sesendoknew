<?php
require_once __DIR__ . '/../app/Core/AuthRateLimiter.php';
$db = DB::getInstance();
$limiter = new AuthRateLimiter($db);
$key = 'audit:' . bin2hex(random_bytes(16));
$assert = static function (bool $ok, string $message): void {
  if (!$ok) throw new RuntimeException('FAIL: ' . $message);
  echo "PASS: $message\n";
};
$db->begin();
try {
  $now = time();
  $assert($limiter->consume($key, 2, 900, $now) === 0, 'percobaan pertama diizinkan');
  $assert($limiter->consume($key, 2, 900, $now) === 0, 'percobaan sampai batas diizinkan');
  $_SESSION = [];
  $assert((new AuthRateLimiter($db))->consume($key, 2, 900, $now) === 900, 'ganti sesi tidak melewati pembatas');
  $assert($limiter->consume($key, 2, 900, $now + 899) === 1, 'percobaan tertolak tidak memperpanjang blokir');
  $assert($limiter->consume($key, 2, 900, $now + 900) === 0, 'blokir kedaluwarsa otomatis');
  $limiter->clear($key);
  $assert($limiter->consume($key, 2, 900, $now + 900) === 0, 'reset akun setelah login dapat dilakukan');
  $assert($limiter->consume($key . ':other', 2, 900, $now) === 0, 'akun lain memiliki pembatas terpisah');
} finally {
  $db->rollback();
}
