<?php
$ok=static function(bool $v,string $m):void{if(!$v)throw new RuntimeException('FAIL: '.$m);echo "PASS: $m\n";};
$controller=file_get_contents(__DIR__.'/../app/Controllers/WallchatController.php');$view=file_get_contents(__DIR__.'/../app/Views/wallchat/index.php');$js=file_get_contents(__DIR__.'/../public/assets/js/modules/wallchat.js');
$ok(substr_count($controller,'while(ob_get_level()>0)ob_end_clean()')>=2,'buffer HTML dibersihkan sebelum media dan lampiran dikirim');
foreach(['wallView','inboxView','btnOpenInbox','btnBackToWall','btnComposePrivate'] as $feature)$ok(str_contains($view,'id="'.$feature.'"'),"view Wallchat memuat $feature");
foreach(['click.wallViews','#btnOpenInbox','#btnBackToWall','#btnComposePrivate'] as $feature)$ok(str_contains($js,$feature),"navigasi Wall/Inbox memuat $feature");
$ok(!str_contains($view,'id="btnPrivateMessage"'),'list pesan tidak lagi diekspos langsung di Wall');
echo "PHASE 25 WALL MEDIA/INBOX TESTS COMPLETE\n";
