<?php
/** Idempotent schema extension; no fabricated SK number or promotion date. */
return static function(PDO $pdo):void {
    foreach(['riwayat_jabatan_neo','riwayat_pangkat_neo']as$table){$columns=array_column($pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC),'Field');if(!in_array('tanggal_sk',$columns,true))$pdo->exec("ALTER TABLE `$table` ADD tanggal_sk DATE NULL AFTER nomor_sk");if($table==='riwayat_jabatan_neo'&&!in_array('kelompok',$columns,true))$pdo->exec("ALTER TABLE `$table` ADD kelompok INT NULL AFTER jabatan");$indexes=array_column($pdo->query("SHOW INDEX FROM `$table`")->fetchAll(PDO::FETCH_ASSOC),'Key_name');if(!in_array('idx_history_current',$indexes,true))$pdo->exec("ALTER TABLE `$table` ADD INDEX idx_history_current (pegawai_id,kd_wilayah,kd_opd,tmt,is_deleted)");}
    $pdo->exec('ALTER TABLE db_asn_pemda_neo MODIFY jabatan VARCHAR(255) NULL');
};
