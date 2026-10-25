<?php
/** Import only validated Excel construction rows into existing 2026 drafts. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__.'/../app/Core/DB.php';
$db=DB::getInstance();
$plan=json_decode(file_get_contents($argv[1]??''),true,512,JSON_THROW_ON_ERROR);
$apply=in_array('--apply',$argv,true);
$actor='IMPORT_RAB_KONSTRUKSI_2026';
$allowedActors=['IMPORT_PERUBAHAN_PUPR_2026','PUPR_OFFICIALS_2026',$actor];
$mapped=[];$rowCount=0;
foreach ($plan['contracts'] as $c) {
    if (!hash_equals($c['rab_sha256'],hash_file('sha256',$c['rab_path'])) || !hash_equals($c['offer_sha256'],hash_file('sha256',$c['offer_path']))) throw new RuntimeException('Excel berubah setelah dibaca; baca ulang sebelum impor');
    $h=$db->query("SELECT * FROM kontrak_neo WHERE id=? AND tahun=2026 AND kd_wilayah='76.01' AND kd_opd='1.03.0.00.0.00.01.0000' AND is_deleted=0",[$c['id']])->fetch();
    if (!$h || !str_contains($h['jenis_pengadaan'],'KONSTRUKSI') || str_contains($h['jenis_pengadaan'],'KONSULTANSI') || $h['status_kontrak']!=='draft' || $h['setujui'] || $h['kunci'] || !$h['rekanan_id'] || $h['tahap']!=='dppa' || (float)$h['total_anggaran']>400000000 || abs((float)$h['total_anggaran']-$c['budget'])>.01) throw new RuntimeException('Kontrak berubah atau bukan draft konstruksi yang sesuai: '.$c['id']);
    if ($h['username_insert']!=='IMPORT_PERUBAHAN_PUPR_2026' || (!empty($h['username_update']) && !in_array($h['username_update'],$allowedActors,true))) throw new RuntimeException('Kontrak telah diubah pengguna; tidak ditimpa');
    $existing=$db->query('SELECT * FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY id',[$h['id']])->fetchAll();
    foreach ($existing as $r) if ($r['username_insert']!==$actor || (!empty($r['username_update']) && $r['username_update']!==$actor)) throw new RuntimeException('RAB pengguna tidak boleh ditimpa');
    if ($existing && count($existing)!==count($c['rab'])) throw new RuntimeException('Susunan RAB berubah');
    $totals=['hps'=>0,'penawaran'=>0,'negoisasi'=>0];
    foreach ($c['rab'] as $r) {
        if (mb_stripos($r['uraian'],'pembulatan')!==false || $r['type']!=='RAB KONSTRUKSI 2026') throw new RuntimeException('Hanya pekerjaan asli dari Excel yang diizinkan');
        foreach (array_keys($totals) as $key) {
            if ($r['vol_'.$key]<=0 || $r['harga_sat_'.$key]<=0 || abs(round($r['vol_'.$key]*$r['harga_sat_'.$key]*(1+$r['pajak']/100),2)-$r['jumlah_'.$key])>.011) throw new RuntimeException('Perhitungan Excel tidak cocok');
            $totals[$key]+=$r['jumlah_'.$key];
        }
    }
    if (abs($totals['negoisasi']-$c['nilai_kontrak'])>.01 || $totals['negoisasi']>$c['budget'] || $totals['hps']-$totals['penawaran']<=0 || $totals['hps']-$totals['penawaran']>300000 || $c['nilai_hps']>$c['budget'] || $c['nilai_kontrak']>$c['nilai_hps']) throw new RuntimeException('Total, pagu, atau selisih penawaran tidak sesuai');
    $items=$db->query('SELECT * FROM kontrak_item_neo WHERE kontrak_id=? AND is_deleted=0',[$h['id']])->fetchAll();
    if (count($items)!==1 || $items[0]['anggaran_id']!=$h['anggaran_id']) throw new RuntimeException('Tautan rincian anggaran berubah');
    $mapped[]=[$c,$h,$existing,$items[0]];$rowCount+=count($c['rab']);
}
if (count($mapped)!==19 || count(array_unique(array_column($plan['contracts'],'id')))!==19) throw new RuntimeException('Jumlah paket harus 19 yang tersedia HPS-nya');
if (!$apply) { echo json_encode(['status'=>'dry_run','contracts'=>count($mapped),'original_work_items'=>$rowCount,'rounding_items'=>0]).PHP_EOL;exit; }
$db->begin();
try {
    foreach ($mapped as [$c,$h,$existing,$item]) {
        foreach ($c['rab'] as $i=>$r) {
            $r+=['kontrak_id'=>$h['id'],'kontrak_item_id'=>$item['id'],'tahun'=>2026,'kd_wilayah'=>$h['kd_wilayah'],'kd_opd'=>$h['kd_opd'],'id_renja_p'=>0,'id_dpa'=>0,'id_dppa'=>$item['anggaran_id'],'bobot'=>round($r['jumlah_negoisasi']/$c['nilai_kontrak']*100,4),'is_deleted'=>0];
            if (isset($existing[$i])) { $r['username_update']=$actor;$r['tgl_update']=date('Y-m-d H:i:s');$db->update('rab_paket_neo',$r,'WHERE id=?',[$existing[$i]['id']]); }
            else { $r['username_insert']=$actor;$r['tgl_insert']=date('Y-m-d H:i:s');$db->insert('rab_paket_neo',$r); }
        }
        $db->query('UPDATE kontrak_item_neo SET nilai_kontrak=?,username_update=?,tgl_update=NOW() WHERE id=?',[$c['nilai_kontrak'],$actor,$item['id']]);
        $db->query('UPDATE kontrak_neo SET nilai_hps=?,nilai_kontrak=?,username_update=?,tgl_update=NOW() WHERE id=?',[$c['nilai_hps'],$c['nilai_kontrak'],$actor,$h['id']]);
    }
    $db->commit();echo json_encode(['status'=>'applied','contracts'=>count($mapped),'original_work_items'=>$rowCount,'rounding_items'=>0]).PHP_EOL;
} catch (Throwable $e) { $db->rollback();throw $e; }
