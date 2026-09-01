<?php
$ok=static function($v,$m){if(!$v)throw new RuntimeException('FAIL: '.$m);echo "PASS: $m\n";};
$model=file_get_contents(__DIR__.'/../app/Models/WallchatModel.php');$index=file_get_contents(__DIR__.'/../database/migrations/20260901_phase27_performance_indexes.sql');$front=file_get_contents(__DIR__.'/../public/index.php');$ht=file_get_contents(__DIR__.'/../public/.htaccess');$doc=file_get_contents(__DIR__.'/../docs/production-performance-security.md');
$ok(str_contains($model,'LIMIT {$limit}')&&str_contains($model,'LIMIT 50'),'feed dan inbox dibatasi');
$ok(substr_count($index,'ADD INDEX IF NOT EXISTS')>=5,'indeks query produksi idempoten tersedia');
foreach(['session.use_strict_mode','session.cookie_httponly','session.cookie_samesite','CONTENT_LENGTH','Cache-Control'] as $f)$ok(str_contains($front,$f),"hardening request memuat $f");
$ok(str_contains($ht,'Options -Indexes')&&str_contains($ht,'Require all denied'),'web root menolak listing dan file sensitif');
$ok(str_contains($doc,'slow query')&&str_contains($doc,'OPcache')&&str_contains($doc,'Backup terenkripsi'),'runbook produksi lengkap');
echo "PHASE 27 PERFORMANCE/SECURITY TESTS COMPLETE\n";
