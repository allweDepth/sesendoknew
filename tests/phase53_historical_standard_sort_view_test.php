<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Core/Auth.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';

$ok=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}\n";};
$db=DB::getInstance();
$user=$db->query("SELECT * FROM user_sesendok_biila WHERE disable=0 AND kd_wilayah<>'' ORDER BY id LIMIT 1")->fetch();
$ok((bool)$user,'pengguna berscope wilayah tersedia dari database');
$user['tahun']=2025;
$_SESSION['user']=$user;
$response=json_decode((new DynamicTableService())->handle(['action'=>'list','tbl'=>'hspk','halaman'=>1,'rows'=>5]),true);
$ok(($response['success']??false)===true,'listing HSPK 2025 berhasil');
$ok((int)($response['meta']['total']??0)>0,'harga satuan HSPK 2025 tampil dari master_biaya');

$view=$db->query('SELECT COUNT(*) jumlah FROM sakip_sumber_kinerja_v')->fetch();
$ok((int)$view['jumlah']>0,'view sumber kinerja terbentuk dari relasi Renstra');

$table=file_get_contents(__DIR__.'/../public/assets/js/engine/table-manager.js');
$toolbar=file_get_contents(__DIR__.'/../public/assets/js/app-init.js');
$navbar=file_get_contents(__DIR__.'/../app/Views/partials/auth_navbar.php');
$ok(str_contains($table,'clearSort()')&&str_contains($table,'this.sortBy = null'),'sortir tabel server dapat dibersihkan');
$ok(str_contains($toolbar,'data-original-sort-index')&&str_contains($toolbar,'click.clearTableSort'),'sortir tabel lokal dapat dikembalikan ke urutan awal');
$ok(str_contains($navbar,'id="clearTableSort"'),'fasilitas clear sortir tersedia pada toolbar global');
echo "PHASE 53 HISTORICAL STANDARD/SORT/VIEW TESTS COMPLETE\n";
