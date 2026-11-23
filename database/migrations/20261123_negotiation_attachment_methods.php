<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../../app/Core/DB.php';
// Rincian penawaran melampiri hasil negosiasi untuk seluruh metode penyedia.
DB::getInstance()->query("UPDATE master_dokumen_pengadaan_neo SET metode_pemilihan=NULL WHERE kode='LAMPIRAN_NEGOSIASI' AND cara_pengadaan='PENYEDIA' AND is_deleted=0");
echo "Lampiran negosiasi tersedia lintas metode pengadaan penyedia.\n";
