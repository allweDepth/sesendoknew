<?php
require_once __DIR__ . '/../../app/Core/DB.php';
$db=DB::getInstance();
$scope=$db->query("SELECT kd_wilayah,kd_opd,tahun,COALESCE(username_insert,'system') username FROM trx_naskah_dinas ORDER BY id DESC LIMIT 1")->fetch();
if(!$scope)$scope=$db->query("SELECT kd_wilayah,kd_opd,tahun,COALESCE(username_insert,'system') username FROM kontrak_neo ORDER BY id DESC LIMIT 1")->fetch();
if(!$scope)throw new RuntimeException('Scope data contoh tidak ditemukan');
$classification=(int)($db->query('SELECT id FROM ref_klasifikasi_keamanan ORDER BY id LIMIT 1')->fetch()['id']??0);
$types=$db->query('SELECT id,nama,schema_json FROM ref_jenis_naskah ORDER BY id')->fetchAll();

$sample=function(string $field,string $label,string $type){
  $key=strtolower($field.' '.$label);
  if(str_contains($key,'tanggal'))return date('Y-m-d');
  if(str_contains($key,'nip'))return '198001012006041001';
  if(str_contains($key,'jabatan'))return 'Kepala Perangkat Daerah';
  if(str_contains($key,'nama'))return 'Pejabat Contoh seSendok';
  if(str_contains($key,'tempat'))return 'Pasangkayu';
  if(str_contains($key,'alamat'))return 'Kompleks Perkantoran Pemerintah Kabupaten Pasangkayu';
  if(str_contains($key,'nomor'))return 'TRACE/TND/'.date('Y');
  if(str_contains($key,'perihal')||str_contains($key,'tentang')||str_contains($key,'judul'))return 'Pelaksanaan Administrasi Pemerintahan Tahun '.date('Y');
  if(in_array($type,['editable_table','table'],true))return [['uraian'=>'Rincian kegiatan contoh','keterangan'=>'Data uji tampilan dan cetak PDF']];
  return 'Contoh '.$label.' disusun otomatis untuk menguji kelengkapan tampilan, alur kerja, dan hasil cetak Tata Naskah seSendok.';
};
$fill=function(array $schema) use(&$fill,$sample):array{
  $data=[];
  foreach($schema as $item){
    if(($item['type']??'')==='fields'){$data=array_merge($data,$fill($item['fields']??[]));continue;}
    $field=$item['field']??$item['name']??''; if($field==='')continue;
    $data[$field]=$sample($field,(string)($item['label']??$field),(string)($item['type']??'text'));
  }
  $data['penanda_tangan']='Pejabat Contoh seSendok';$data['jabatan_penandatangan']='Kepala Perangkat Daerah';return $data;
};

foreach($types as $type){
  $tag='TRACE_SAMPLE_TND_'.$type['id'];
  $existing=$db->query('SELECT id FROM trx_naskah_dinas WHERE keterangan=? LIMIT 1',[$tag])->fetch();
  if($existing)continue;
  $schema=json_decode((string)$type['schema_json'],true); if(!is_array($schema))$schema=[];
  $db->begin();
  try{
    $id=$db->insert('trx_naskah_dinas',['uuid'=>uniqid('tnd_',true),'jenis_id'=>$type['id'],'nomor'=>'TRACE/'.str_pad((string)$type['id'],3,'0',STR_PAD_LEFT).'/'.$scope['kd_opd'].'/'.$scope['tahun'],'nomor_urut'=>900+(int)$type['id'],'tahun'=>$scope['tahun'],'klasifikasi_id'=>$classification?:null,'tanggal_surat'=>date('Y-m-d'),'perihal'=>'Contoh '.$type['nama'],'status'=>'draft','kd_wilayah'=>$scope['kd_wilayah'],'kd_opd'=>$scope['kd_opd'],'username_insert'=>$scope['username'],'tgl_insert'=>date('Y-m-d H:i:s'),'keterangan'=>$tag,'workflow_status'=>'draft']);
    $db->insert('trx_naskah_struktur',['naskah_id'=>$id,'struktur_json'=>json_encode($fill($schema),JSON_UNESCAPED_UNICODE),'kd_wilayah'=>$scope['kd_wilayah'],'kd_opd'=>$scope['kd_opd'],'tahun'=>$scope['tahun'],'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$scope['username']]);
    $db->commit(); echo "SEEDED {$type['id']} {$type['nama']}\n";
  }catch(Throwable $e){$db->rollback();throw $e;}
}
