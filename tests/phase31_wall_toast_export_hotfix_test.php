<?php
$ok=static function($v,$m){if(!$v)throw new RuntimeException('FAIL: '.$m);echo "PASS: $m\n";};$front=file_get_contents(__DIR__.'/../public/index.php');$toast=file_get_contents(__DIR__.'/../public/assets/js/core/toast.js');$wall=file_get_contents(__DIR__.'/../app/Controllers/WallchatController.php');$js=file_get_contents(__DIR__.'/../public/assets/js/modules/anggaran-document.js');
$ok(str_contains($front,'str_starts_with($match[3], APP_BASE_PATH')&&str_contains($front,'preg_replace_callback'),'URL yang sudah ber-base-path tidak diprefix ulang');
$ok(substr_count($wall,'while(ob_get_level()>0)ob_end_clean()')>=2,'buffer layout dibuang sebelum binary media dikirim');
$ok(str_contains($toast,'data-toast-created')&&str_contains($toast,'cleanup(true, createdAt)'),'toast lama dihapus paksa berdasarkan umur');
foreach(['recap-excel','recap-pdf','data-budget-action="excel"','export_rekap_excel'] as $f)$ok(str_contains($js,$f),"handler export $f tersedia");echo "PHASE 31 HOTFIX TESTS COMPLETE\n";
