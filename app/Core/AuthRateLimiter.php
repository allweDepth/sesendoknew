<?php
require_once __DIR__ . '/DB.php';

final class AuthRateLimiter
{
  public function __construct(private DB $db) {}

  /** Atomic counters shared by every PHP worker and independent of cookies. */
  public function consume(string $key, int $limit, int $window, ?int $now = null): int
  {
    $now ??= time();
    $bucket = hash('sha256', $key);
    $this->db->query(
      'INSERT INTO auth_rate_limits (bucket,attempts,expires_at) VALUES (?,1,?)
       ON DUPLICATE KEY UPDATE attempts=IF(expires_at<=?,1,attempts+1),
       expires_at=IF(expires_at<=?,VALUES(expires_at),expires_at)',
      [$bucket, $now + $window, $now, $now]
    );
    $row = $this->db->query('SELECT attempts,expires_at FROM auth_rate_limits WHERE bucket=?', [$bucket])->fetch();
    if (random_int(1, 100) === 1) {
      $this->db->query('DELETE FROM auth_rate_limits WHERE expires_at<=? LIMIT 1000', [$now]);
    }
    return (int)$row['attempts'] > $limit ? max(1, (int)$row['expires_at'] - $now) : 0;
  }

  public function clear(string $key): void
  {
    $this->db->query('DELETE FROM auth_rate_limits WHERE bucket=?', [hash('sha256', $key)]);
  }
}
