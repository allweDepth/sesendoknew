<?php
require_once __DIR__.'/../Core/Auth.php';
require_once __DIR__.'/../Core/DB.php';
class DashboardController extends Controller {
 public function index(){
  if(!Auth::check()){header('Location: '.app_url('/'));exit;}$u=Auth::scopedUser();$db=DB::getInstance();$year=(int)($u['tahun']??date('Y'));$module=preg_replace('/[^a-z_]/','',(string)($_GET['mod']??'utama'))?:'utama';
  $labels=['utama'=>'Dashboard Utama','referensi'=>'Referensi','standar_harga'=>'Standar Harga','usulan'=>'Usulan','perencanaan'=>'Perencanaan','penganggaran'=>'Penganggaran','kontrak'=>'Kontrak','realisasi'=>'Realisasi','sakip'=>'SAKIP & Kinerja','kepegawaian'=>'Kepegawaian','tata_naskah'=>'Tata Naskah','user_role'=>'User & Role OPD','berita'=>'Berita','pesan'=>'Pesan','profil'=>'Profil'];if(!isset($labels[$module]))$module='utama';
  $scope='kd_wilayah=?';$params=[$u['kd_wilayah']??''];if(!empty($u['kd_opd'])&&$u['kd_opd']!=='0'){$scope.=' AND kd_opd=?';$params[]=$u['kd_opd'];}$annual=$scope.' AND tahun=? AND is_deleted=0';$ap=[...$params,$year];
  $count=static function($db,$table,$where,$p){try{return(int)$db->query("SELECT COUNT(*) n FROM `$table` WHERE $where",$p)->fetch()['n'];}catch(Throwable $e){return 0;}};$sum=static function($db,$table,$field,$where,$p){try{return(float)$db->query("SELECT COALESCE(SUM(`$field`),0) n FROM `$table` WHERE $where",$p)->fetch()['n'];}catch(Throwable $e){return 0;}};
  $cards=[];$add=static function(&$cards,$label,$value,$icon,$tone='blue',$hint=''){ $cards[]=compact('label','value','icon','tone','hint');};
  if(in_array($module,['utama','perencanaan'],true)){$add($cards,'Subkegiatan Renstra',$count($db,'sub_kegiatan_renstra_neo','is_deleted=0',[]),'project diagram','blue','Fondasi rencana lima tahunan');$add($cards,'RKPD',$count($db,'rkpd_neo',$annual,$ap),'map outline','teal','Prioritas tahunan');$add($cards,'Target Renja',$count($db,'renja_sub_kegiatan_kinerja_neo',$annual,$ap),'tasks','violet','Target keluaran aktif');}
  if(in_array($module,['utama','penganggaran'],true)){$add($cards,'Pagu DPA',$sum($db,'dpa_neo','jumlah',$annual,$ap),'money bill alternate','green','Nilai dokumen tahun aktif');$add($cards,'Pagu DPPA',$sum($db,'dppa_neo','jumlah',$annual,$ap),'sync','orange','Sesudah perubahan');}
  if($module==='referensi')foreach([['rekening_kegiatan','Program–Subkegiatan','sitemap'],['akun_neo','Akun','book'],['sumber_dana_neo','Sumber Dana','coins'],['organisasi_neo','OPD','building']] as $x)$add($cards,$x[1],$count($db,$x[0],'is_deleted=0',[]),$x[2]);
  if($module==='standar_harga')foreach(['ssh','hspk','asb','sbu'] as $type)$add($cards,strtoupper($type),$count($db,'master_biaya','tipe=? AND tahun=? AND is_deleted=0',[$type,$year]),'calculator');
  if($module==='usulan')$add($cards,'Usulan Tahun Aktif',$count($db,'usulan_pembangunan_neo',$annual,$ap),'lightbulb','yellow');
  if($module==='kontrak'){$add($cards,'Kontrak',$count($db,'kontrak_neo',$annual,$ap),'file signature','violet');$add($cards,'Nilai Kontrak',$sum($db,'kontrak_neo','nilai_kontrak',$annual,$ap),'handshake','green');}
  if($module==='realisasi'){$add($cards,'Evaluasi Renja',$count($db,'evaluasi_renja_neo',$annual,$ap),'chart line','blue');$add($cards,'Realisasi Tercatat',$count($db,'daftar_realisasi_neo',$annual,$ap),'check circle','green');}
  if($module==='sakip')foreach([['iku_opd_neo','IKU'],['pohon_kinerja_neo','Pohon Kinerja'],['perjanjian_kinerja_neo','Perjanjian Kinerja'],['pengukuran_kinerja_neo','Pengukuran']] as $x){$w=$x[0]==='iku_opd_neo'?$scope.' AND is_deleted=0':$annual;$p=$x[0]==='iku_opd_neo'?$params:$ap;$add($cards,$x[1],$count($db,$x[0],$w,$p),'bullseye','violet');}
  if($module==='kepegawaian'){$add($cards,'ASN',$count($db,'db_asn_pemda_neo',$scope.' AND is_deleted=0',$params),'users','blue');$add($cards,'Pejabat Tahunan',$count($db,'pejabat_tahunan_neo',$annual,$ap),'id badge','teal');}
  if($module==='tata_naskah')$add($cards,'Naskah Dinas',$count($db,'trx_naskah_dinas',$annual,$ap),'file alternate','blue');
  if($module==='user_role')$add($cards,'Pengguna Aktif',$count($db,'user_sesendok_biila',$scope.' AND disable=0',$params),'user shield','violet');
  if($module==='berita')$add($cards,'Konten Aktif',$count($db,'halaman_berita','kd_wilayah=? AND aktif=1 AND is_deleted=0',[$u['kd_wilayah']]),'newspaper','orange');
  if($module==='pesan')$add($cards,'Pesan Publik',$count($db,'wallchat','is_deleted=0 AND type=?',['public']),'comments','teal');
  if($module==='profil'){$add($cards,'Tahun Aktif',$year,'calendar','blue');$add($cards,'Role',strtoupper(str_replace('_',' ',$u['type_user']??'viewer')),'user shield','violet');}if(!$cards)$add($cards,'Data Tahun Aktif',0,'database');
  $max=max(array_map(fn($c)=>is_numeric($c['value'])?(float)$c['value']:0,$cards))?:1;$this->view('dashboard/index',['module'=>$module,'title'=>$labels[$module],'cards'=>$cards,'max'=>$max,'year'=>$year,'user'=>$u]);
 }
}
