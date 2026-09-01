<?php
$ok=static function(bool $v,string $m):void{if(!$v)throw new RuntimeException('FAIL: '.$m);echo "PASS: $m\n";};
$create=file_get_contents(__DIR__.'/../app/Views/tata_naskah/buat.php');$list=file_get_contents(__DIR__.'/../app/Views/tata_naskah/daftar.php');$module=file_get_contents(__DIR__.'/../public/assets/js/modules/tata_naskah.js');
foreach(['data-naskah-step="1"','data-naskah-step="2"','data-naskah-step="3"','jenisNaskahSearch'] as $feature)$ok(str_contains($create,$feature),"halaman buat memuat $feature");
$ok(str_contains($list,'searchTataNaskah'),'daftar naskah memiliki pencarian langsung');
foreach(['enhanceForm','data-naskah-progress','input.naskahProgress','Toast.success','Toast.error'] as $feature)$ok(str_contains($module,$feature),"modul Tata Naskah memuat $feature");
$ok(!str_contains($module,'Toast.show("success"')&&!str_contains($module,'Toast.show("error"'),'pemanggilan toast Tata Naskah memakai API yang benar');
require_once __DIR__.'/phase6_tata_naskah_test.php';
echo "PHASE 23 TATA NASKAH UX TESTS COMPLETE\n";
