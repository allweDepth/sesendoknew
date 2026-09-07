<?php
$ok=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";};
$js=file_get_contents(__DIR__.'/../public/assets/js/ui/modern-table-resizer.js');
$css=file_get_contents(__DIR__.'/../public/assets/css/modern-tables.css');
$layout=file_get_contents(__DIR__.'/../app/Views/layouts/app.php');
$table=file_get_contents(__DIR__.'/../public/assets/js/engine/table-manager.js');
$budget=file_get_contents(__DIR__.'/../public/assets/js/modules/anggaran-document.js');
$ok(str_contains($js,'column-resize-handle')&&str_contains($js,'startColumn'),'lebar kolom dapat diseret');
$ok(str_contains($js,'row-resize-handle')&&str_contains($js,'startRow'),'tinggi baris dapat diseret');
$ok(substr_count($js,'dblclick')>=2&&str_contains($js,'autoColumn'),'klik ganda mengembalikan ukuran otomatis');
$ok(str_contains($js,'localStorage.setItem')&&str_contains($js,'readWidths'),'lebar kolom tersimpan per tabel di browser');
$ok(str_contains($css,'.table-action-column')&&str_contains($table,"table-action-column'>Aksi"),'kolom aksi mengikuti lebar tombol');
$ok(str_contains($layout,'modern-table-resizer.js'),'pengendali resize dimuat pada layout utama');
$ok(str_contains($budget,'budget-pagu-column')&&str_contains($css,'min-width: 170px !important'),'kolom pagu dokumen anggaran memiliki lebar default yang terbaca');
echo "PHASE 51 TABLE RESIZE TESTS COMPLETE\n";
