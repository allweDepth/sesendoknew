<?php
/** CLI-only, scope-checked Tahap 3 update sourced from verified Excel workbooks. */
if (PHP_SAPI !== 'cli') exit;
chdir(dirname(__DIR__));
require 'app/Core/DB.php';require 'app/Services/ProcurementProcessService.php';
$db=DB::getInstance();$dir='outputs/tahap3-20261028/';$read=fn($f)=>json_decode(file_get_contents($dir.$f),true,512,JSON_THROW_ON_ERROR);
$plan=$read('model.json');$audit=$read('db-audit.json');$before=array_column($audit['kontrak_neo'],null,'id');$books=array_column(array_filter($read('verified-workbooks.json'),fn($w)=>$w['kind']==='offer'),null,'contract_id');
$actor='TAHAP3_20261029';$scope=['76.01','1.03.0.00.0.00.01.0000',2026];$report=[];
if(count($plan)!==21||count($books)!==13)throw new RuntimeException('Scope must contain 21 packages and 13 verified offers');
$db->begin();try{
$sakti=$db->query('SELECT id FROM rekanan_neo WHERE nama_perusahaan=? AND kd_wilayah=? AND is_deleted=0',['CV. SAKTI JAYA KONSTRUKSI',$scope[0]])->fetch();
$saktiId=$sakti['id']??$db->insert('rekanan_neo',['kd_wilayah'=>$scope[0],'nama_perusahaan'=>'CV. SAKTI JAYA KONSTRUKSI','alamat'=>'Jalan Andi Bandaco, Pasangkayu, Kabupaten Pasangkayu, Sulawesi Barat','email'=>'Sugisma144@gmail.com','npwp'=>'1000000011660588','direktur'=>'Sugisma Wijaya, S.T','jabatan'=>'Direktur','no_akta_pendirian'=>'33','tgl_akta_pendirian'=>'2026-10-23','nama_notaris_pendirian'=>'SYAHBUR BASO LUKKASA, S.H.','keterangan'=>'Profil perusahaan Tahap 3; NIB 2310260113328. Rekening belum tersedia.','username_insert'=>$actor]);
foreach($plan as$p){
$d=$db->query('SELECT * FROM dppa_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',[$p['dppa_id'],...$scope])->fetch();
if(!$d||$d['uraian']!==$p['package']||abs((float)$d['jumlah']-$p['budget'])>.001)throw new RuntimeException('DPPA mismatch '.$p['dppa_id']);
$id=$p['contract_id'];if(!$id){$report[]=$p+['result'=>'Belum dibuat: HPS dan kode RUP belum tersedia'];continue;}
$c=$db->query('SELECT * FROM kontrak_neo WHERE id=?',[$id])->fetch();if($c!=$before[$id])throw new RuntimeException('Contract changed since audit: '.$id);
if($c['kd_wilayah']!==$scope[0]||$c['kd_opd']!==$scope[1]||(int)$c['tahun']!==2026||$c['status_kontrak']!=='draft'||$c['kunci']||$c['setujui']||(int)$c['anggaran_id']!==(int)$d['id'])throw new RuntimeException('Contract outside editable scope: '.$id);
$vId=$p['vendor_id']==='SAKTI'?$saktiId:$p['vendor_id'];$v=$db->query('SELECT * FROM rekanan_neo WHERE id=? AND kd_wilayah=? AND is_deleted=0',[$vId,$scope[0]])->fetch();if(!$v)throw new RuntimeException('Missing company');
$official=function($role)use($db,$scope,$p){$x=$db->query('SELECT id FROM pejabat_tahunan_neo WHERE jenis_pejabat=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND kd_sub_keg=? AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$role,...$scope,$p['subactivity']])->fetch();if(!$x)throw new RuntimeException('Missing '.$role.' '.$p['subactivity']);return$x['id'];};
$process=ProcurementProcessService::decode($c['proses_pengadaan']);foreach($process as$code=>&$r){$r['tanggal']=in_array($code,['UNDANGAN_PL','JADWAL_DOKUMEN','BA_SURVEY_HARGA','PAKTA_INTEGRITAS'],true)?'2026-10-28':'2026-10-29';if($p['rup_verified']&&!empty($c['nomor_rup']))$r['nomor']=str_replace($c['nomor_rup'],$p['rup'],$r['nomor']??'');}unset($r);
$u=['uraian_kontrak'=>$d['uraian'],'rekanan_id'=>$vId,'nama_penyedia'=>$v['nama_perusahaan'],'total_anggaran'=>$p['budget'],'ppk_id'=>$official('PPK'),'pptk_id'=>$official('PPTK'),'pejabat_pengadaan_id'=>7,'jenis_kontrak'=>$p['consultancy']?'LUMSUM':'HARGA_SATUAN','tanggal_kontrak'=>'2026-10-29','tanggal_spmk'=>'2026-10-29','tanggal_mulai'=>'2026-10-29','tanggal_selesai'=>$p['consultancy']?'2026-11-27':'2026-12-31','waktu_pelaksanaan'=>$p['consultancy']?30:64,'proses_pengadaan'=>json_encode($process,JSON_UNESCAPED_SLASHES),'username_update'=>$actor,'tgl_update'=>date('Y-m-d H:i:s')];
if($p['rup_verified']){$u['nomor_rup']=$p['rup'];$u['nomor_kontrak']=str_replace($c['nomor_rup'],$p['rup'],$c['nomor_kontrak']);$u['nomor_spmk']=str_replace($c['nomor_rup'],$p['rup'],$c['nomor_spmk']);}
$item=$db->query('SELECT * FROM kontrak_item_neo WHERE kontrak_id=? AND is_deleted=0',[$id])->fetchAll();if(count($item)!==1||(int)$item[0]['anggaran_id']!==(int)$d['id'])throw new RuntimeException('Contract item mismatch '.$id);
if(isset($books[$id])){
$b=$books[$id];$total=round($b['actual_total'],2);if($total>$p['budget']||$total>$p['hps'])throw new RuntimeException('Offer exceeds budget');
$db->query('UPDATE rab_paket_neo SET is_deleted=1,username_update=?,tgl_update=NOW() WHERE kontrak_id=? AND is_deleted=0',[$actor,$id]);$real=0;
foreach($b['actual_rows']as$i=>$r){$note=$r['quantity']===null;$h=$note?null:$p['offer_items'][$real++];if(!$note&&$r['uraian']!==$h['uraian'])throw new RuntimeException('Excel item mismatch');
$row=['kontrak_id'=>$id,'kontrak_item_id'=>$item[0]['id'],'tahun'=>2026,'kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'id_renja_p'=>0,'id_dpa'=>0,'id_dppa'=>$d['id'],'nomor'=>(string)($r['nomor']??''),'uraian'=>$r['uraian'],'satuan'=>(string)($r['satuan']??''),'type'=>$note?'KETERANGAN':($p['consultancy']?'RAB KONSULTANSI 2026':'RAB KONSTRUKSI 2026'),'bobot'=>$note?0:round($r['amount']/$total*100,4),'urutan'=>$i+1,'keterangan'=>'Tahap 3; sumber Excel penawaran terverifikasi; tidak menambahkan item pembulatan.','username_insert'=>$actor];
foreach(['hps','penawaran','negoisasi']as$key){$row['vol_'.$key]=$note?null:$r['quantity'];$row['harga_sat_'.$key]=$note?null:sprintf('%.12F',$key==='hps'?$h['price']:$r['price']);$row['jumlah_'.$key]=$note?null:round($key==='hps'?$h['hps_amount']:$r['amount'],2);}$row['pajak']=$note?null:$r['tax'];$db->insert('rab_paket_neo',$row);
}
$u['nilai_hps']=$p['hps'];$u['nilai_kontrak']=$total;$db->update('kontrak_item_neo',['nilai_kontrak'=>$total,'username_update'=>$actor,'tgl_update'=>date('Y-m-d H:i:s')],'WHERE id=?',[$item[0]['id']]);
$sum=(float)$db->query('SELECT SUM(jumlah_penawaran) n FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0',[$id])->fetch()['n'];if(abs($sum-$total)>.011)throw new RuntimeException('DB/Excel sum mismatch');
}
$db->update('kontrak_neo',$u,'WHERE id=?',[$id]);$c=array_replace($c,$u);ProcurementProcessService::syncDocuments($db,(int)$id,$c,$actor);
$setting=$db->query('SELECT settings_json FROM kontrak_print_settings_neo WHERE kontrak_id=?',[$id])->fetch();if($setting){$s=json_decode($setting['settings_json'],true)?:[];if(isset($s['offer_print'])){$s['offer_print']['tanggal_surat']='2026-10-29';$db->query('UPDATE kontrak_print_settings_neo SET settings_json=?,updated_by=? WHERE kontrak_id=?',[json_encode($s),$actor,$id]);}}
$report[]=$p+['result'=>isset($books[$id])?'RAB dan kontrak diperbarui':'Metadata diperbarui; HPS belum tersedia'];
}
// Every contract and every DPPA row outside this explicit scope must remain byte-for-byte equivalent.
$ids=array_filter(array_column($plan,'contract_id'));foreach($before as$id=>$old)if(!in_array($id,$ids,true)&&$db->query('SELECT * FROM kontrak_neo WHERE id=?',[$id])->fetch()!=$old)throw new RuntimeException('Unrelated contract changed');
foreach($audit['dppa_neo']as$old)if($db->query('SELECT * FROM dppa_neo WHERE id=?',[$old['id']])->fetch()!=$old)throw new RuntimeException('DPPA changed');
if(!in_array('--apply',$argv,true)){$db->rollback();echo 'Dry run passed: 17 contracts, 13 RAB; other contracts and DPPA unchanged'.PHP_EOL;exit;}
$db->commit();file_put_contents($dir.'hasil-data.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));echo 'Applied: 17 contracts, 13 RAB; four pending HPS/RUP; other contracts and DPPA unchanged'.PHP_EOL;
}catch(Throwable$e){$db->rollback();throw$e;}
