<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../app/Core/DB.php';
$path=$argv[1]??'';
if (!is_file($path)) throw new RuntimeException('JSON DPA 2024 tidak ditemukan');
$payload=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
$db=DB::getInstance();
$db->query('CREATE TABLE IF NOT EXISTS rekening_kegiatan_historis (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,tahun YEAR NOT NULL,kd_wilayah VARCHAR(25) NOT NULL,kode VARCHAR(50) NOT NULL,level ENUM("program","kegiatan","sub_kegiatan") NOT NULL,uraian VARCHAR(500) NOT NULL,sumber VARCHAR(255) NULL,UNIQUE KEY uq_rekening_historis(tahun,kd_wilayah,kode,level)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$rows=[];
foreach (($payload['documents']??[]) as $document) {
    $code=(string)($document['sub_kegiatan']??'');
    $program=preg_replace('/\s+/u',' ',trim((string)($document['program']??'')));
    $kegiatan=preg_replace('/\s+/u',' ',trim((string)($document['kegiatan']??'')));
    $name=preg_replace('/\s+/u',' ',trim((string)($document['nama_sub_kegiatan']??'')));
    $name=preg_replace('/\s*:\s*(?:Dana|Pendapatan|DAK|Lain).*/u','',$name);
    if ($code===''||$name==='') continue;
    $rows[$code]=['program'=>$program,'kegiatan'=>$kegiatan,'sub_kegiatan'=>$name,'source'=>$document['source_file']??'PDF DPA 2024'];
}
foreach ($rows as $code=>$names) {
    $programCode=preg_match('/^([0-9.]+)\s*-/', $names['program'],$m)?$m[1]:null;
    $kegiatanCode=preg_match('/^([0-9.]+)\s*-/', $names['kegiatan'],$m)?$m[1]:null;
    foreach ([[$programCode,'program',$names['program']],[$kegiatanCode,'kegiatan',$names['kegiatan']],[$code,'sub_kegiatan',$names['sub_kegiatan']]] as [$historicalCode,$level,$label]) {
        if (!$historicalCode||trim((string)$label)==='') continue;
        $db->query('INSERT INTO rekening_kegiatan_historis(tahun,kd_wilayah,kode,level,uraian,sumber) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE uraian=VALUES(uraian),sumber=VALUES(sumber)',[2024,'76.01',$historicalCode,$level,trim((string)$label),$names['source']]);
    }
}
echo json_encode(['tahun'=>2024,'mappings'=>count($rows)],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
