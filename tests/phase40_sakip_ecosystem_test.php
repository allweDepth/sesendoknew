<?php
require_once __DIR__.'/../app/Core/DB.php';
$db=DB::getInstance();$scope=['76.TEST','OPD.TEST',2099];
$required=[
 'iku_opd_neo'=>['renstra_id','sasaran_renstra_id','formula_perhitungan','target_t5'],
 'pohon_kinerja_neo'=>['parent_id','jenjang','jenis_kinerja','sumber_ref'],
 'perjanjian_kinerja_neo'=>['pihak_pertama_pegawai_id','pihak_kedua_pegawai_id','status'],
 'perjanjian_kinerja_detail_neo'=>['perjanjian_kinerja_id','kd_wilayah','iku_id','anggaran'],
 'pengukuran_kinerja_neo'=>['perjanjian_kinerja_detail_id','target_periode','realisasi_kumulatif','capaian_persen'],
 'evaluasi_renstra_neo'=>['jenis_evaluasi','target_kumulatif','rekomendasi_reviu'],
 'renja_sub_kegiatan_kinerja_neo'=>['sub_kegiatan_renstra_id','pohon_kinerja_id','pagu_indikatif','prakiraan_maju_pagu']];
foreach($required as $table=>$columns){$actual=array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(),'Field');foreach($columns as $column)if(!in_array($column,$actual,true))throw new RuntimeException("Kolom $table.$column belum tersedia");}
$profiles=require __DIR__.'/../app/Config/table_profiles.php';foreach(['iku_opd','pohon_kinerja','perjanjian_kinerja','perjanjian_kinerja_detail','pengukuran_kinerja','evaluasi_renstra','renja_kinerja'] as $profile)if(empty($profiles[$profile]))throw new RuntimeException("Profil UI $profile belum tersedia");
require_once __DIR__.'/../app/Services/DynamicTableService.php';$_SESSION['user']=['type_user'=>'admin_opd','kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2]];$service=new DynamicTableService();$method=(new ReflectionClass($service))->getMethod('performancePercentage');
foreach([[20,16,'maksimal',80],[20,16,'minimal',125],[20,16,'stabil',80]] as [$target,$realization,$polarity,$expected])if((float)$method->invoke($service,$target,$realization,$polarity)!==(float)$expected)throw new RuntimeException("Hitung capaian $polarity tidak sesuai");
$db->begin();
try{
 $iku=$db->insert('iku_opd_neo',['kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'kode_iku'=>'IKU.TEST','nama_indikator'=>'Indikator pengujian','satuan'=>'persen','baseline'=>10,'target_t1'=>20,'status'=>'draft','username_insert'=>'PHASE40_TEST']);
 $pohon=$db->insert('pohon_kinerja_neo',['kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2],'kode_kinerja'=>'KIN.TEST','uraian_kinerja'=>'Outcome pengujian','jenjang'=>'strategis','jenis_kinerja'=>'outcome','iku_id'=>$iku,'status'=>'draft','username_insert'=>'PHASE40_TEST']);
 $pk=$db->insert('perjanjian_kinerja_neo',['kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2],'nomor_dokumen'=>'PK/TEST','tanggal_dokumen'=>'2099-01-01','pihak_pertama_pegawai_id'=>1,'pihak_pertama_jabatan'=>'Kepala OPD','pihak_kedua_pegawai_id'=>2,'pihak_kedua_jabatan'=>'Kepala Daerah','username_insert'=>'PHASE40_TEST']);
 $detail=$db->insert('perjanjian_kinerja_detail_neo',['perjanjian_kinerja_id'=>$pk,'kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2],'pohon_kinerja_id'=>$pohon,'iku_id'=>$iku,'sasaran_kinerja'=>'Sasaran pengujian','indikator_kinerja'=>'Indikator pengujian','satuan'=>'persen','target'=>20,'anggaran'=>1000,'username_insert'=>'PHASE40_TEST']);
 $db->insert('pengukuran_kinerja_neo',['kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2],'perjanjian_kinerja_detail_id'=>$detail,'periode'=>'triwulanan','nomor_periode'=>1,'target_periode'=>5,'realisasi_periode'=>4,'realisasi_kumulatif'=>4,'capaian_persen'=>80,'username_insert'=>'PHASE40_TEST']);
 $linked=$db->query('SELECT d.id FROM perjanjian_kinerja_detail_neo d JOIN perjanjian_kinerja_neo p ON p.id=d.perjanjian_kinerja_id JOIN pohon_kinerja_neo k ON k.id=d.pohon_kinerja_id JOIN iku_opd_neo i ON i.id=d.iku_id WHERE d.id=? AND d.kd_wilayah=? AND d.kd_opd=?',[$detail,$scope[0],$scope[1]])->fetch();
 if(!$linked)throw new RuntimeException('Rantai integrasi IKU-Pohon Kinerja-PK tidak terbentuk');
 $db->rollback();
}catch(Throwable $e){$db->rollback();throw $e;}
echo "PHASE 40 SAKIP ECOSYSTEM TEST PASSED\n";
