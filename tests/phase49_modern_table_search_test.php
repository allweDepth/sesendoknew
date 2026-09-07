<?php
$assert=static function(bool $condition,string $message):void{if(!$condition)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";};
$table=file_get_contents(__DIR__.'/../public/assets/js/engine/table-manager.js');
$toolbar=file_get_contents(__DIR__.'/../public/assets/js/app-init.js');
$service=file_get_contents(__DIR__.'/../app/Services/DynamicTableService.php');
$navbar=file_get_contents(__DIR__.'/../app/Views/partials/auth_navbar.php');
$layout=file_get_contents(__DIR__.'/../app/Views/layouts/app.php');
$css=file_get_contents(__DIR__.'/../public/assets/css/modern-tables.css');
$assert(str_contains($table,'maxColumns || config.tableMaxColumns || 7'),'tabel generik membatasi kolom penting');
$assert(str_contains($table,'data-sort-key')&&str_contains($table,'sort_by: this.sortBy'),'header tabel mengirim sortir server-side');
$assert(str_contains($table,'setTimeout(execute, 450)')&&str_contains($table,'pendingRequest.abort()'),'pencarian memakai debounce dan membatalkan request lama');
$assert(str_contains($service,'min(100, max(1')&&str_contains($service,"preg_match('/^[a-zA-Z0-9_]+$/"),'limit dan nama kolom sortir divalidasi server');
$assert(str_contains($toolbar,'hasSearchableUi')&&str_contains($toolbar,"Pencarian tidak tersedia"),'search utama hanya aktif saat UI memiliki tabel');
$assert(!str_contains($navbar,'data-value="all"')&&str_contains($navbar,'maxlength="100"'),'dropdown dan input pencarian dibatasi aman');
$assert(str_contains($layout,'modern-tables.css')&&str_contains($css,'.table-col-wide')&&str_contains($css,'.table-col-money'),'lebar kolom modern bersifat proporsional');
echo "PHASE 49 MODERN TABLE/SEARCH TESTS COMPLETE\n";
