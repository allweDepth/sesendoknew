<?php
require_once __DIR__.'/../app/Core/DB.php';

$source=__DIR__.'/../tmp/pdfs/renstra-table.json';
if(!is_file($source)) throw new RuntimeException('Ekstraksi Renstra tidak ditemukan: '.$source);
$pdfRows=json_decode(file_get_contents($source),true,512,JSON_THROW_ON_ERROR);
$byCode=[]; foreach($pdfRows as $row) $byCode[$row['code']]=$row;

$db=DB::getInstance();
$wilayah='76.01'; $opd='1.03.0.00.0.00.01.0000'; $actor='SEED_PUPR_DOC_2025_2026';
$paguTotal=241474151501.00;
$number=static function($v):float { $v=trim((string)$v); return $v===''?0.0:(float)str_replace(',','.',str_replace('.','',$v)); };
$targetFor=static function(array $pdfRow,int $year)use($number):float {
  $targets=$pdfRow['detail']['targets']??[];
  return $number($targets[$year===2025?0:1]??0);
};
$iku2025=['IKU-01'=>3.45,'IKU-02'=>21.15,'IKU-03'=>90,'IKU-04'=>0.31,'IKU-05'=>2,'IKU-06'=>100,'IKU-07'=>62.57];

$renstra=$db->query('SELECT id FROM renstra_neo WHERE kd_wilayah=? AND kd_opd=? AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$wilayah,$opd])->fetch();
if(!$renstra) throw new RuntimeException('Renstra PUPR aktif tidak ditemukan. Jalankan importer Renstra lebih dahulu.');
$renstraId=(int)$renstra['id'];
$subs=$db->query("SELECT sk.id sub_id,rk.kode kd_sub_keg,rk.uraian sub_nama,k.kode_kegiatan,k.uraian kegiatan_nama,p.id program_id,p.kode_program,p.uraian program_nama,sk.indikator_keluaran,sk.satuan,sk.lokasi,sk.kelompok_sasaran,sk.anggaran_t1
  FROM sub_kegiatan_renstra_neo sk JOIN kegiatan_renstra_neo k ON k.id=sk.kegiatan_renstra_id JOIN program_renstra_neo p ON p.id=k.program_id JOIN rekening_kegiatan rk ON rk.id=sk.master_sub_kegiatan_id
  WHERE p.sasaran_id IN (SELECT s.id FROM sasaran_renstra_neo s JOIN tujuan_renstra_neo t ON t.id=s.tujuan_id JOIN misi_renstra_neo m ON m.id=t.misi_id WHERE m.renstra_id=?) AND sk.is_deleted=0 ORDER BY rk.kode",[$renstraId])->fetchAll();
if(count($subs)!==73) throw new RuntimeException('Harus ada 73 subkegiatan Renstra; ditemukan '.count($subs).'.');

$insertBudget=static function($db,string $table,string $sourceTable,int $sourceId,array $s,int $year,int $sshId,float $amount,string $note,bool $change=false)use($wilayah,$opd,$actor):int {
  $data=['source_table'=>$sourceTable,'source_id'=>$sourceId,'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'kd_sub_keg'=>$s['kd_sub_keg'],
    'kd_akun'=>'5.1.02.02.05.0044','kel_rek'=>'5.1.02','objek_belanja'=>'Belanja barang/jasa placeholder','uraian'=>'Rincian indikatif '.$s['sub_nama'],
    'jenis_kelompok'=>'Belanja Operasi','kelompok'=>'Rincian pagu subkegiatan','jenis_standar_harga'=>'ssh','id_standar_harga'=>$sshId,
    'komponen'=>'Komponen sementara pagu '.$s['kd_sub_keg'],'spesifikasi'=>'Placeholder; wajib dirinci saat penyusunan definitif','tkdn'=>0,'pajak'=>0,
    'harga_satuan'=>1,'vol_1'=>$amount,'sat_1'=>'Paket','volume'=>$amount,'jumlah'=>$amount,'sumber_dana_id'=>1,
    'keterangan'=>$note,'disable'=>0,'kunci'=>0,'setujui'=>0,'is_deleted'=>0,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$actor];
  if($change) $data+=['jenis_standar_harga_awal'=>'ssh','id_standar_harga_awal'=>$sshId,'komponen_awal'=>$data['komponen'],'spesifikasi_awal'=>$data['spesifikasi'],'tkdn_awal'=>0,'pajak_awal'=>0,'harga_satuan_awal'=>1,'volume_awal'=>$amount,'jumlah_awal'=>$amount,'status_perubahan'=>'awal'];
  return (int)$db->insert($table,$data);
};

$db->begin();
try {
  // Bersihkan hanya rantai tahunan PUPR 2025-2026. Renstra dan IKU resmi dipertahankan.
  foreach(['pengukuran_kinerja_neo','perjanjian_kinerja_detail_neo','perjanjian_kinerja_neo','evaluasi_renstra_neo','pohon_kinerja_neo','renja_sub_kegiatan_kinerja_neo','evaluasi_renja_neo'] as $table)
    $db->query("DELETE FROM `$table` WHERE kd_wilayah=? AND kd_opd=? AND ".($table==='evaluasi_renstra_neo'?'tahun_evaluasi':'tahun').' IN (2025,2026)',[$wilayah,$opd]);
  foreach(['rkpd_neo','renja_neo','rka_neo','dpa_neo','rkpd_p_neo','renja_p_neo','rka_p_neo','dppa_neo'] as $table)
    $db->query("DELETE FROM `$table` WHERE kd_wilayah=? AND kd_opd=? AND tahun IN (2025,2026)",[$wilayah,$opd]);
  $db->query('DELETE FROM batas_pagu_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun IN (2025,2026)',[$wilayah,$opd]);

  $ssh=[];
  foreach([2025,2026] as $year){
    $code='PUPR-PAGU-'.$year;
    $found=$db->query('SELECT id FROM master_biaya WHERE kode=? AND kd_wilayah=? AND tahun=? LIMIT 1',[$code,$wilayah,$year])->fetch();
    $data=['tipe'=>'ssh','kode'=>$code,'kelompok_barang'=>'Komponen sementara penyusunan anggaran PUPR','uraian'=>'Komponen placeholder pagu Renstra PUPR '.$year,'spesifikasi'=>'Harga satuan sementara; wajib dirinci OPD pada dokumen definitif','satuan_id'=>1,'harga'=>1,'keterangan'=>'DUMMY TERKENDALI','kd_wilayah'=>$wilayah,'tahun'=>$year,'peraturan_id'=>4,'disable'=>0,'is_deleted'=>0,'username_insert'=>$actor];
    if($found){$db->update('master_biaya',$data,'WHERE id=?',[$found['id']]);$ssh[$year]=(int)$found['id'];}else $ssh[$year]=(int)$db->insert('master_biaya',$data);
    foreach(['renja','rka','dpa','renja_p','rka_p','dppa'] as $doc)$db->insert('batas_pagu_opd_neo',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'dokumen'=>$doc,'pagu_maksimal'=>$paguTotal,'keterangan'=>$year===2025?'Pagu transisi dummy memakai pagu indikatif Renstra 2026 karena PDF tidak memuat kolom pagu 2025':'Pagu indikatif Renstra PUPR 2026','username_insert'=>$actor,'is_deleted'=>0]);
  }

  $annualIds=[];
  foreach([2025,2026] as $year){
    foreach($subs as $s){
      if(!isset($byCode[$s['kd_sub_keg']])) throw new RuntimeException('Kode tidak ditemukan dalam ekstraksi PDF: '.$s['kd_sub_keg']);
      $target=$targetFor($byCode[$s['kd_sub_keg']],$year); $amount=(float)$s['anggaran_t1'];
      $note=($year===2025?'DUMMY TRANSISI 2025; pagu memakai pagu indikatif Renstra 2026. ':'DUMMY 2026 sesuai pagu indikatif Renstra. ').'Belum merupakan dokumen ditetapkan.';
      $rkpd=(int)$db->insert('rkpd_neo',['renstra_sub_kegiatan_id'=>$s['sub_id'],'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'kd_program'=>$s['kode_program'],'kd_kegiatan'=>$s['kode_kegiatan'],'kd_sub_keg'=>$s['kd_sub_keg'],'indikator'=>$s['indikator_keluaran'],'target'=>$target,'satuan_id'=>1,'pagu'=>$amount,'sumber_dana_id'=>1,'lokasi'=>$s['lokasi'],'kelompok_sasaran'=>$s['kelompok_sasaran'],'status'=>'draft','disable'=>0,'kunci'=>0,'setujui'=>0,'keterangan'=>$note,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$actor,'is_deleted'=>0]);
      $renja=$insertBudget($db,'renja_neo','rkpd_neo',$rkpd,$s,$year,$ssh[$year],$amount,$note);
      $rka=$insertBudget($db,'rka_neo','renja_neo',$renja,$s,$year,$ssh[$year],$amount,$note);
      $dpa=$insertBudget($db,'dpa_neo','rka_neo',$rka,$s,$year,$ssh[$year],$amount,$note);
      $rkpdP=(int)$db->insert('rkpd_p_neo',['source_rkpd_id'=>$rkpd,'renstra_sub_kegiatan_id'=>$s['sub_id'],'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'kd_program'=>$s['kode_program'],'kd_kegiatan'=>$s['kode_kegiatan'],'kd_sub_keg'=>$s['kd_sub_keg'],'indikator'=>$s['indikator_keluaran'],'target_awal'=>$target,'pagu_awal'=>$amount,'target'=>$target,'satuan_id'=>1,'pagu'=>$amount,'sumber_dana_id'=>1,'lokasi'=>$s['lokasi'],'kelompok_sasaran'=>$s['kelompok_sasaran'],'status_perubahan'=>'awal','status'=>'draft','disable'=>0,'kunci'=>0,'setujui'=>0,'keterangan'=>$note,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$actor,'is_deleted'=>0]);
      $renjaP=$insertBudget($db,'renja_p_neo','rkpd_p_neo',$rkpdP,$s,$year,$ssh[$year],$amount,$note,true);
      $rkaP=$insertBudget($db,'rka_p_neo','renja_p_neo',$renjaP,$s,$year,$ssh[$year],$amount,$note,true);
      $dppa=$insertBudget($db,'dppa_neo','rka_p_neo',$rkaP,$s,$year,$ssh[$year],$amount,$note,true);
      $renjaK=(int)$db->insert('renja_sub_kegiatan_kinerja_neo',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'kd_sub_keg'=>$s['kd_sub_keg'],'sub_kegiatan_renstra_id'=>$s['sub_id'],'indikator_keluaran'=>$s['indikator_keluaran']?:('Keluaran '.$s['sub_nama']),'satuan'=>$s['satuan']?:'Dokumen','target'=>$target,'lokasi'=>$s['lokasi'],'kelompok_sasaran'=>$s['kelompok_sasaran'],'pagu_indikatif'=>$amount,'sumber_dana'=>'Pendanaan daerah (dummy)','catatan_penting'=>$note,'status'=>'draft','username_insert'=>$actor,'is_deleted'=>0]);
      for($q=1;$q<=4;$q++)$db->insert('evaluasi_renja_neo',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'triwulan'=>$q,'kd_sub_keg'=>$s['kd_sub_keg'],'renja_kinerja_id'=>$renjaK,'indikator'=>$s['indikator_keluaran']?:('Keluaran '.$s['sub_nama']),'satuan'=>$s['satuan']?:'Dokumen','target_tahunan'=>$target,'target_triwulan'=>$target/4,'realisasi_triwulan'=>0,'realisasi_kumulatif'=>0,'pagu_anggaran'=>$amount,'realisasi_anggaran_triwulan'=>0,'realisasi_anggaran_kumulatif'=>0,'faktor_pendorong'=>'Belum diisi','faktor_penghambat'=>'Belum diisi','tindak_lanjut'=>'Diisi OPD pada periode evaluasi','status'=>'draft','username_insert'=>$actor,'is_deleted'=>0]);
      $annualIds[$year][$s['kd_sub_keg']]=compact('rkpd','renja','rka','dpa','rkpdP','renjaP','rkaP','dppa','renjaK');
    }
  }

  $ikus=$db->query('SELECT * FROM iku_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND renstra_id=? AND is_deleted=0 ORDER BY kode_iku',[$wilayah,$opd,$renstraId])->fetchAll();
  foreach([2025,2026] as $year){
    $pk=(int)$db->insert('perjanjian_kinerja_neo',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'nomor_dokumen'=>'DUMMY/PK-PUPR/'.$year,'tanggal_dokumen'=>$year.'-01-02','jenis'=>'awal','pihak_pertama_pegawai_id'=>1,'pihak_pertama_jabatan'=>'Kepala Dinas PUPR','pihak_kedua_pegawai_id'=>2,'pihak_kedua_jabatan'=>'Atasan Langsung','dasar_dokumen'=>'Renstra PUPR 2025-2030 dan Renja '.$year,'status'=>'draft','keterangan'=>'Dataset contoh; pejabat dan nomor wajib diverifikasi','username_insert'=>$actor,'is_deleted'=>0]);
    $fallbackTree=null;
    foreach($ikus as $i=>$iku){
      $target=$year===2025?($iku2025[$iku['kode_iku']]??0):(float)$iku['target_t1'];
      $tree=(int)$db->insert('pohon_kinerja_neo',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'renstra_id'=>$renstraId,'tahun'=>$year,'kode_kinerja'=>$iku['kode_iku'].'-'.$year,'uraian_kinerja'=>'Meningkatkan '.$iku['nama_indikator'],'jenjang'=>'strategis','jenis_kinerja'=>'outcome','iku_id'=>$iku['id'],'indikator'=>$iku['nama_indikator'],'satuan'=>$iku['satuan'],'target'=>$target,'penanggung_jawab_pegawai_id'=>1,'sumber_ref'=>$iku['program_renstra_id']?'program_renstra':'sasaran_renstra','sumber_id'=>$iku['program_renstra_id']?:$iku['sasaran_renstra_id'],'hubungan_sebab_akibat'=>'Capaian program dan subkegiatan mendukung IKU OPD','status'=>'draft','keterangan'=>'Pohon kinerja contoh terhubung IKU','username_insert'=>$actor,'is_deleted'=>0]);
      if($fallbackTree===null)$fallbackTree=$tree;
      if($iku['program_renstra_id'])$db->query('UPDATE renja_sub_kegiatan_kinerja_neo rk JOIN sub_kegiatan_renstra_neo sk ON sk.id=rk.sub_kegiatan_renstra_id JOIN kegiatan_renstra_neo k ON k.id=sk.kegiatan_renstra_id SET rk.pohon_kinerja_id=? WHERE rk.kd_wilayah=? AND rk.kd_opd=? AND rk.tahun=? AND k.program_id=?',[$tree,$wilayah,$opd,$year,$iku['program_renstra_id']]);
      $programBudget=$iku['program_renstra_id']?(float)$db->query('SELECT COALESCE(SUM(sk.anggaran_t1),0) total FROM sub_kegiatan_renstra_neo sk JOIN kegiatan_renstra_neo k ON k.id=sk.kegiatan_renstra_id WHERE k.program_id=? AND sk.is_deleted=0',[$iku['program_renstra_id']])->fetch()['total']:$paguTotal;
      $detail=(int)$db->insert('perjanjian_kinerja_detail_neo',['perjanjian_kinerja_id'=>$pk,'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'nomor_urut'=>$i+1,'pohon_kinerja_id'=>$tree,'iku_id'=>$iku['id'],'sasaran_kinerja'=>'Meningkatnya kualitas dan cakupan layanan infrastruktur dasar','indikator_kinerja'=>$iku['nama_indikator'],'satuan'=>$iku['satuan'],'target'=>$target,'program_kegiatan'=>'Program Renstra terkait IKU','anggaran'=>$programBudget,'sumber_anggaran'=>'dpa','keterangan'=>'Dataset contoh terhubung IKU dan DPA','username_insert'=>$actor,'is_deleted'=>0]);
      for($q=1;$q<=4;$q++)$db->insert('pengukuran_kinerja_neo',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'perjanjian_kinerja_detail_id'=>$detail,'periode'=>'triwulanan','nomor_periode'=>$q,'target_periode'=>$target/4,'realisasi_periode'=>0,'realisasi_kumulatif'=>0,'capaian_persen'=>0,'analisis_capaian'=>'Belum ada realisasi; data awal contoh','kendala'=>'Belum diisi','tindak_lanjut'=>'Diisi OPD saat pengukuran','status'=>'draft','username_insert'=>$actor,'is_deleted'=>0]);
      $db->insert('evaluasi_renstra_neo',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'renstra_id'=>$renstraId,'tahun_evaluasi'=>$year,'jenis_evaluasi'=>'tahunan','iku_id'=>$iku['id'],'indikator'=>$iku['nama_indikator'],'satuan'=>$iku['satuan'],'target_tahunan'=>$target,'target_kumulatif'=>$target,'realisasi_tahunan'=>0,'realisasi_kumulatif'=>0,'capaian_persen'=>0,'pagu_anggaran'=>$programBudget,'realisasi_anggaran'=>0,'faktor_pendorong'=>'Belum diisi','faktor_penghambat'=>'Belum diisi','tindak_lanjut'=>'Diisi pada evaluasi tahunan','rekomendasi_reviu'=>'Belum dievaluasi','status'=>'draft','username_insert'=>$actor,'is_deleted'=>0]);
    }
    $db->query('UPDATE renja_sub_kegiatan_kinerja_neo SET pohon_kinerja_id=? WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND pohon_kinerja_id IS NULL',[$fallbackTree,$wilayah,$opd,$year]);
  }
  $db->commit();
  echo json_encode(['years'=>[2025,2026],'subactivities'=>count($subs),'official_2026_pagu'=>$paguTotal,'note_2025'=>'provisional using 2026 Renstra pagu'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;
} catch(Throwable $e){$db->rollback();throw $e;}
