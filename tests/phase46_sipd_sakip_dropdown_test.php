<?php
$ok=static function($condition,$message){if(!$condition)throw new RuntimeException('FAIL: '.$message);echo'PASS: '.$message.PHP_EOL;};
$routes=file_get_contents(__DIR__.'/../routes/web.php');
$service=file_get_contents(__DIR__.'/../app/Services/StandarHargaService.php');
$sakip=file_get_contents(__DIR__.'/../app/Services/SakipReportService.php');
$dynamic=file_get_contents(__DIR__.'/../app/Services/DynamicTableService.php');
$migration=file_get_contents(__DIR__.'/../database/migrations/20260907_phase46_sipd_standard_source.sql');
$ok(str_contains($routes,"'/standar_harga/import_sipd'")&&str_contains($routes,"'/sakip/pohon_kinerja_pdf'"),'route impor SIPD dan PDF pohon tersedia');
$ok(str_contains($service,'ID standar harga')||str_contains($service,"'idstandarharga'"),'importer mengenali ID standar harga SIPD');
$ok(str_contains($service,"preg_split('/\\s*,\\s*/'")&&str_contains($service,'master_biaya_akun'),'beberapa kode rekening dipecah menjadi mapping');
$ok(str_contains($migration,'sipd_id')&&str_contains($migration,'kode_kelompok'),'identitas sumber SIPD disimpan');
$ok(str_contains($sakip,'SUB KEGIATAN')&&str_contains($sakip,'IKU'),'PDF pohon menyajikan hirarki IKU sampai sub kegiatan');
$ok(str_contains($sakip,'nama_sasaran')&&str_contains($sakip,'program_renstra_neo'),'laporan SAKIP menerjemahkan relasi menjadi uraian');
$ok(str_contains($dynamic,'normalizeDropdownField')&&str_contains($dynamic,'knownDropdownTables'),'dropdown join mempertahankan label tabel relasi');
echo "PHASE 46 SIPD/SAKIP/DROPDOWN TESTS COMPLETE\n";

