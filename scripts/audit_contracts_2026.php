<?php
/** Audit read-only hasil impor kontrak PUPR Pasangkayu 2026. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../app/Core/DB.php';
$db=DB::getInstance(); $w='76.01'; $o='1.03.0.00.0.00.01.0000'; $y=2026;
$one=static fn($sql,$p=[])=>$db->query($sql,$p)->fetch();
$scope=[$w,$o,$y];
$result=[];
$result['contracts']=$one('SELECT COUNT(*) n,SUM(nilai_kontrak) nilai,SUM(total_anggaran) pagu,SUM(nilai_kontrak>=total_anggaran) invalid FROM kontrak_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',$scope);
$result['items']=$one('SELECT COUNT(*) n,SUM(ki.nilai_kontrak>ki.pagu) over_budget,SUM(ABS(x.allocated-k.nilai_kontrak)>0.01) header_mismatch FROM kontrak_neo k JOIN (SELECT kontrak_id,SUM(nilai_kontrak) allocated FROM kontrak_item_neo WHERE is_deleted=0 GROUP BY kontrak_id) x ON x.kontrak_id=k.id JOIN kontrak_item_neo ki ON ki.kontrak_id=k.id AND ki.is_deleted=0 WHERE k.kd_wilayah=? AND k.kd_opd=? AND k.tahun=? AND k.is_deleted=0',$scope);
$result['vendors']=$one("SELECT COUNT(DISTINCT excel_source_id) source_ids,SUM(nama_perusahaan REGEXP '^[[:space:]]*[0-9]+[[:space:]]+') prefixed_names FROM rekanan_neo WHERE kd_wilayah=? AND excel_source_id IS NOT NULL AND is_deleted=0",[$w]);
$result['links']=$one('SELECT COUNT(*) rab_rows,COUNT(DISTINCT r.kontrak_id) rab_contracts,SUM(k.id IS NULL OR ki.id IS NULL OR d.id IS NULL) broken FROM rab_paket_neo r LEFT JOIN kontrak_neo k ON k.id=r.kontrak_id AND k.is_deleted=0 LEFT JOIN kontrak_item_neo ki ON ki.id=r.kontrak_item_id AND ki.kontrak_id=r.kontrak_id AND ki.is_deleted=0 LEFT JOIN dpa_neo d ON d.id=r.id_dpa AND d.is_deleted=0 WHERE r.kd_wilayah=? AND r.kd_opd=? AND r.tahun=? AND r.is_deleted=0',$scope);
$result['dpa']=$one('SELECT COUNT(*) n,SUM(jumlah) total FROM dpa_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',$scope);
$result['without_rab']=$db->query('SELECT k.nomor_kontrak,k.uraian_kontrak FROM kontrak_neo k LEFT JOIN rab_paket_neo r ON r.kontrak_id=k.id AND r.is_deleted=0 WHERE k.kd_wilayah=? AND k.kd_opd=? AND k.tahun=? AND k.is_deleted=0 GROUP BY k.id HAVING COUNT(r.id)=0 ORDER BY k.id',$scope)->fetchAll();
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
