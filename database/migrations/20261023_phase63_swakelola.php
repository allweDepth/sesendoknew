<?php
/** Idempotent import. Existing document snapshots are never rewritten. */
require_once __DIR__.'/../../app/Core/DB.php';
require_once __DIR__.'/../../app/Services/SwakelolaDocumentService.php';
$db=DB::getInstance();$catalog=SwakelolaDocumentService::catalog();
// DDL intentionally precedes the data transaction (MySQL DDL commits implicitly).
$db->query("CREATE TABLE IF NOT EXISTS swakelola_sumber_neo (
    id varchar(20) NOT NULL PRIMARY KEY,tipe_swakelola varchar(4) NOT NULL,nama_file varchar(255) NOT NULL,
    sha256 char(64) NOT NULL,jumlah_blok int NOT NULL,versi varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$db->query("CREATE TABLE IF NOT EXISTS dokumen_pengadaan_paragraf_neo (
    id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    master_bagian_id bigint unsigned NULL,dokumen_bagian_id bigint unsigned NULL,parent_id bigint unsigned NULL,
    jenis_blok enum('paragraph','table','row','cell') NOT NULL,urutan int NOT NULL DEFAULT 0,
    isi longtext NULL,format_data longtext NULL,
    INDEX idx_paragraf_master(master_bagian_id,parent_id,urutan),
    INDEX idx_paragraf_hasil(dokumen_bagian_id,parent_id,urutan),
    FOREIGN KEY (parent_id) REFERENCES dokumen_pengadaan_paragraf_neo(id) ON DELETE CASCADE,
    CHECK ((master_bagian_id IS NULL) <> (dokumen_bagian_id IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$db->begin();
try {
    foreach($catalog['sources'] as $source)$db->query('INSERT INTO swakelola_sumber_neo(id,tipe_swakelola,nama_file,sha256,jumlah_blok,versi) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nama_file=VALUES(nama_file),sha256=VALUES(sha256),jumlah_blok=VALUES(jumlah_blok),versi=VALUES(versi)',[$source['id'],$source['type'],$source['filename'],$source['sha256'],$source['blocks'],$catalog['version']]);
    foreach ($catalog['templates'] as $template) {
        $values=['kode'=>$template['code'],'nama'=>$template['name'],'kelompok'=>$template['group'],
            'cara_pengadaan'=>'SWAKELOLA','jenis_pengadaan'=>'SEMUA','tipe_swakelola'=>$template['type'],
            'bentuk_kontrak'=>str_contains($template['name'],'Pokok Perjanjian')?'PERJANJIAN_SWAKELOLA':'SEMUA',
            'dasar_hukum'=>'Format dari berkas MDS yang disediakan pengguna; Keputusan Deputi I LKPP Nomor 2 Tahun 2022.',
            'sumber_format'=>$template['filename'],'versi'=>$catalog['version'],'berlaku_mulai'=>'2022-01-01','aktif'=>1,'is_deleted'=>0];
        $old=$db->query('SELECT id FROM master_dokumen_pengadaan_neo WHERE kode=? AND versi=?',[$template['code'],$catalog['version']])->fetch();
        if ($old) {$id=(int)$old['id'];$db->update('master_dokumen_pengadaan_neo',$values,'WHERE id=?',[$id]);}
        else {$values['username_insert']='migration_phase63';$id=(int)$db->insert('master_dokumen_pengadaan_neo',$values);}
        $db->query("UPDATE master_dokumen_pengadaan_bagian_neo SET is_deleted=1 WHERE master_id=? AND kode_bagian='IDENTITAS'",[$id]);
        foreach (['ISI'=>$template['blocks']] as $code=>$blocks) {
            $db->query('INSERT INTO master_dokumen_pengadaan_bagian_neo (master_id,kode_bagian,judul,urutan,isi_template,format_data,petunjuk_edit,wajib,username_insert,is_deleted) VALUES(?,?,?,?,?,?,?,?,?,0) ON DUPLICATE KEY UPDATE isi_template=VALUES(isi_template),format_data=VALUES(format_data),petunjuk_edit=VALUES(petunjuk_edit),is_deleted=0',
                [$id,$code,$code==='ISI'?'Format MDS lengkap':'Identitas paket dan pelaksana',$code==='ISI'?2:1,
                 json_encode($blocks,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                 json_encode(['editor'=>'swakelola_blocks','source'=>$template['source'],'start'=>$template['start'],'end'=>$template['end']]),
                 'Lengkapi isian dan sunting paragraf atau sel tabel sesuai paket sebelum diajukan.',1,'migration_phase63']);
            $section=$db->query('SELECT id FROM master_dokumen_pengadaan_bagian_neo WHERE master_id=? AND kode_bagian=?',[$id,$code])->fetch();
            SwakelolaDocumentService::storeBlocks($db,'master_bagian_id',(int)$section['id'],$blocks);
        }
    }
    // Supersede the four abbreviated masters without touching their saved outputs.
    $db->query("UPDATE master_dokumen_pengadaan_neo SET aktif=0 WHERE kode IN ('SWAKELOLA_I','SWAKELOLA_II','SWAKELOLA_III','SWAKELOLA_IV')");
    $db->commit();
} catch (Throwable $e) {$db->rollback();throw $e;}
echo count($catalog['sources']).' sumber dan '.count($catalog['templates'])." master swakelola diimpor.\n";
