<?php
$ok=static function(bool $v,string $m):void{if(!$v)throw new RuntimeException('FAIL: '.$m);echo "PASS: $m\n";};
$view=file_get_contents(__DIR__.'/../app/Views/partials/auth_navbar.php');$js=file_get_contents(__DIR__.'/../public/assets/js/app-init.js');$routes=file_get_contents(__DIR__.'/../routes/web.php');
foreach(['userMenu','userMenuMessages','darkToggle','userMenuProfile','btnLogout'] as $id)$ok(str_contains($view,'id="'.$id.'"'),"menu pengguna memuat $id");
$ok(str_contains($view,'href="/wallchat"')&&str_contains($routes,"'/wallchat'"),'Pesan terhubung ke route Wallchat');
$ok(str_contains($view,'href="/profil"')&&str_contains($routes,"'/profil'"),'Profil terhubung ke route profil');
$ok(str_contains($view,'href="/logout"')&&str_contains($routes,"'/logout'"),'Keluar terhubung ke route logout');
foreach(['applyTheme','sesendok-theme','dark-mode','aria-pressed','click.userTheme','keydown.userTheme'] as $feature)$ok(str_contains($js,$feature),"handler tema memuat $feature");
$ok(str_contains($js,'DialogEngine.show')&&str_contains($js,'#btnLogout'),'logout memakai dialog konfirmasi');
echo "PHASE 24 USER MENU TESTS COMPLETE\n";
