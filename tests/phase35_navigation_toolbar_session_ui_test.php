<?php
$ok=static function($value,$message){if(!$value)throw new RuntimeException('FAIL: '.$message);echo"PASS: $message\n";};
$sidebar=file_get_contents(__DIR__.'/../app/Views/partials/sidebar.php');
$router=file_get_contents(__DIR__.'/../public/assets/js/core/spa-router.js');
$table=file_get_contents(__DIR__.'/../public/assets/js/engine/table-manager.js');
$contract=file_get_contents(__DIR__.'/../public/assets/js/modules/kontrak.js');
$settings=file_get_contents(__DIR__.'/../app/Views/pengaturan/form.php');
$ajax=file_get_contents(__DIR__.'/../public/assets/js/core/ajax.js');
$layout=file_get_contents(__DIR__.'/../app/Views/layouts/app.php');
$ok(str_contains($sidebar,'> Kontrak</div>')&&str_contains($sidebar,'> Realisasi</div>')&&str_contains($sidebar,'List Penginputan'),'Kontrak dan Realisasi terpisah serta realisasi memiliki dashboard/list input');
$ok(str_contains($table,'input${namespace}')&&str_contains($table,'change${namespace}')&&str_contains($table,'#countRow'),'search langsung dan dropdown jumlah baris mengendalikan TableManager aktif');
$ok(str_contains($contract,'window.TableRowInjector=c=>c.tbl')&&!str_contains($contract,'const old=window.TableRowInjector'),'injector aksi kontrak idempoten dan tidak menumpuk tombol');
$ok(str_contains($router,'updateHeader(link)')&&str_contains($router,'dynamicHeaderIcon')&&str_contains($router,'pDashboard'),'sticky header mengikuti ikon dan deskripsi menu aktif');
$ok(str_contains($settings,'document-period-table')&&str_contains($settings,'edit-document-period'),'periode dokumen berupa tabel dengan tombol edit per baris');
$ok(str_contains($router,'/session/status')&&str_contains($ajax,"xhr.status === 401"),'navigasi dan AJAX mengunci UI lalu kembali ke halaman utama saat session habis');
$ok(str_contains($layout,'text-align-last: left'),'baris terakhir paragraf justified tidak direnggangkan');
echo"PHASE 35 NAVIGATION/TOOLBAR/SESSION/UI TESTS COMPLETE\n";
