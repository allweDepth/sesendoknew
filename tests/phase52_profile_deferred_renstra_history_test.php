<?php
require_once __DIR__.'/../app/Core/DB.php';
$ok=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('FAIL: '.$message);echo"PASS: $message\n";};

$profile=file_get_contents(__DIR__.'/../public/assets/js/modules/profil.js');
$view=file_get_contents(__DIR__.'/../app/Views/profil/index.php');
$photoStart=strpos($profile,"\tbindPhotoWatcher() {");$periodStart=strpos($profile,"\tbindPeriodSelector() {");$destroyStart=strpos($profile,"\tdestroy() {");$submitStart=strpos($profile,"\tbindSubmit() {");
$photoWatcher=substr($profile,$photoStart,$periodStart-$photoStart);
$periodSelector=substr($profile,$periodStart,$destroyStart-$periodStart);
$submit=substr($profile,$submitStart,$photoStart-$submitStart);
$ok(!str_contains($photoWatcher,'this.ajax.request'),'pemilihan foto hanya membuat preview dan tidak langsung upload');
$ok(!str_contains($periodSelector,'/profil/select-period'),'perubahan periode tidak langsung disimpan');
$ok(str_contains($submit,'/profil/save')&&str_contains($submit,'/profil/upload-photo'),'profil dan foto disimpan melalui tombol submit');
$ok(str_contains($view,'button type="submit"'),'tombol Simpan Perubahan bertipe submit eksplisit');
$ok(substr_count($view,'class="ui fluid search selection dropdown"')>=2&&substr_count($profile,'action: "activate"')>=2,'dropdown periode dan tahun memakai struktur Fomantic native yang menutup setelah dipilih');

$db=DB::getInstance();$w='76.01';$o='1.03.0.00.0.00.01.0000';
$period=$db->query('SELECT id,status_aktif FROM periode_rpjmd WHERE kd_wilayah=? AND periode_mulai=2020 AND periode_selesai=2025',[$w])->fetch();
$ok((bool)$period&&(int)$period['status_aktif']===1,'periode RPJMD 2020-2025 tersedia di profil');
$renstra=$db->query('SELECT id,status FROM renstra_neo WHERE kd_wilayah=? AND kd_opd=? AND periode_id=? AND is_deleted=0',[$w,$o,$period['id']])->fetch();
$ok((bool)$renstra,'Renstra PUPR 2020-2025 tersedia');
$counts=$db->query('SELECT
 (SELECT COUNT(*) FROM iku_opd_neo WHERE renstra_id=? AND is_deleted=0) iku,
 (SELECT COUNT(*) FROM program_renstra_neo p JOIN sasaran_renstra_neo s ON s.id=p.sasaran_id JOIN tujuan_renstra_neo t ON t.id=s.tujuan_id JOIN misi_renstra_neo m ON m.id=t.misi_id WHERE m.renstra_id=? AND p.is_deleted=0) program,
 (SELECT COUNT(*) FROM sub_kegiatan_renstra_neo sk JOIN kegiatan_renstra_neo k ON k.id=sk.kegiatan_renstra_id JOIN program_renstra_neo p ON p.id=k.program_id JOIN sasaran_renstra_neo s ON s.id=p.sasaran_id JOIN tujuan_renstra_neo t ON t.id=s.tujuan_id JOIN misi_renstra_neo m ON m.id=t.misi_id WHERE m.renstra_id=? AND sk.is_deleted=0) sub_kegiatan',[$renstra['id'],$renstra['id'],$renstra['id']])->fetch();
$ok((int)$counts['iku']===7&&(int)$counts['program']===10&&(int)$counts['sub_kegiatan']===73,'struktur dan IKU Renstra historis lengkap');
$rpjmd=$db->query('SELECT status FROM rpjmd_kabupaten_neo WHERE kd_wilayah=? AND berlaku_mulai=? AND berlaku_sampai=? AND is_deleted=0',[$w,'2020-01-01','2025-12-31'])->fetch();
$ok((bool)$rpjmd&&$rpjmd['status']==='draft','RPJMD 2020-2025 tersimpan sebagai draft');
echo"PHASE 52 PROFILE/RENSTRA HISTORY TESTS COMPLETE\n";
