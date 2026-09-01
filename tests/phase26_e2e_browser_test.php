<?php
$ok=static function($v,$m){if(!$v)throw new RuntimeException('FAIL: '.$m);echo "PASS: $m\n";};
$migration=file_get_contents(__DIR__.'/../database/migrations/20260901_phase26_e2e_browser_messages.sql');$controller=file_get_contents(__DIR__.'/../app/Controllers/WallchatController.php');$model=file_get_contents(__DIR__.'/../app/Models/WallchatModel.php');$js=file_get_contents(__DIR__.'/../public/assets/js/services/e2e-message.js');$module=file_get_contents(__DIR__.'/../public/assets/js/modules/wallchat.js');
foreach(['message_public_key','e2e_payload','encryption_version'] as $column)$ok(str_contains($migration,$column),"schema memuat $column");
$ok(str_contains($js,"'RSA-OAEP'")&&str_contains($js,"'AES-GCM'")&&str_contains($js,'indexedDB'),'browser memiliki identitas RSA dan enkripsi AES-GCM');
$ok(str_contains($controller,"content='Pesan terenkripsi end-to-end'")&&str_contains($model,"'content_ciphertext' =>"),'server menerima payload tanpa plaintext pesan');
$ok(str_contains($module,"formData.set('content','')"),'plaintext dihapus sebelum request');
echo "PHASE 26 E2E TESTS COMPLETE\n";
