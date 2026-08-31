<?php
require_once __DIR__ . '/../Core/DB.php';

class AnggaranCopyService
{
    private DB $db;
    private array $user;
    private const TABLES = ['rkpd'=>'rkpd_neo','renja'=>'renja_neo','rka'=>'rka_neo','dpa'=>'dpa_neo','rkpd_p'=>'rkpd_p_neo','renja_p'=>'renja_p_neo','rka_p'=>'rka_p_neo','dppa'=>'dppa_neo'];
    private const TRANSITIONS = ['rkpd:renja','renja:rka','rka:dpa','rkpd:rkpd_p','renja:renja_p','rka:rka_p','dpa:dppa'];

    public function __construct(array $user = []) { $this->db = DB::getInstance(); $this->user = $user; }
    public static function table(string $logical): string
    {
        if (!isset(self::TABLES[$logical])) throw new InvalidArgumentException('Tahap anggaran tidak valid');
        return self::TABLES[$logical];
    }

    public function copy(string $from, string $to, int $tahun, ?int $sourceId = null): array
    {
        if (!in_array($this->user['type_user'] ?? '', ['super_admin','admin_wilayah','admin_opd'], true)) throw new RuntimeException('Tidak memiliki hak untuk memproses dokumen');
        if (!in_array("$from:$to", self::TRANSITIONS, true)) throw new InvalidArgumentException('Urutan dokumen tidak diizinkan');
        if ($tahun < 2000 || $tahun > 2100) throw new InvalidArgumentException('Tahun tidak valid');
        $sourceTable = self::table($from); $targetTable = self::table($to);
        $wilayah = (string)($this->user['kd_wilayah'] ?? ''); $opd = (string)($this->user['kd_opd'] ?? '');
        if ($wilayah === '') throw new RuntimeException('Scope wilayah pengguna tidak lengkap');
        $where = 'tahun = ? AND kd_wilayah = ? AND is_deleted = 0 AND setujui = 1'; $params = [$tahun, $wilayah];
        if ($opd !== '' && $opd !== '0') { $where .= ' AND kd_opd = ?'; $params[] = $opd; }
        if ($sourceId) { $where .= ' AND id = ?'; $params[] = $sourceId; }

        $this->db->begin();
        try {
            $rows = $this->db->query("SELECT * FROM `$sourceTable` WHERE $where FOR UPDATE", $params)->fetchAll();
            if (!$rows) throw new RuntimeException('Tidak ada dokumen sumber yang telah disetujui');
            $columns = array_column($this->db->query("SHOW COLUMNS FROM `$targetTable`")->fetchAll(), 'Field');
            $copied = 0; $skipped = 0;
            foreach ($rows as $row) {
                $linkField = $to === 'rkpd_p' ? 'source_rkpd_id' : 'source_id';
                $linkParams = $to === 'rkpd_p' ? [(int)$row['id']] : [$sourceTable, (int)$row['id']];
                $linkWhere = $to === 'rkpd_p' ? 'source_rkpd_id = ?' : 'source_table = ? AND source_id = ?';
                if ($this->db->query("SELECT id FROM `$targetTable` WHERE $linkWhere AND is_deleted = 0 LIMIT 1", $linkParams)->fetch()) { $skipped++; continue; }
                $this->db->insert($targetTable, $this->mapRow($from, $to, $sourceTable, $row, $columns)); $copied++;
            }
            $this->db->insert('anggaran_workflow_log', ['source_table'=>$sourceTable,'target_table'=>$targetTable,'tahun'=>$tahun,'kd_wilayah'=>$wilayah,'kd_opd'=>$opd ?: null,'jumlah_data'=>$copied,'username'=>$this->user['username'] ?? 'system','tgl_copy'=>date('Y-m-d H:i:s')]);
            $this->db->commit();
            return ['copied'=>$copied,'skipped'=>$skipped,'from'=>$from,'to'=>$to];
        } catch (Throwable $e) { $this->db->rollback(); throw $e; }
    }

    private function mapRow(string $from, string $to, string $sourceTable, array $row, array $columns): array
    {
        $payload = ['source_table'=>$sourceTable,'source_id'=>(int)$row['id']];
        foreach ($columns as $column) if (!in_array($column, ['id','source_table','source_id','tgl_update','username_update'], true) && array_key_exists($column, $row)) $payload[$column] = $row[$column];
        if ($from === 'rkpd' && $to === 'renja') $payload += ['kd_wilayah'=>$row['kd_wilayah'],'kd_opd'=>$row['kd_opd'],'tahun'=>$row['tahun'],'kd_sub_keg'=>$row['kd_sub_keg'],'uraian'=>$row['indikator'] ?: 'Rincian dari RKPD','volume'=>$row['target'] ?: 1,'jumlah'=>$row['pagu'],'harga_satuan'=>$row['pagu'],'sumber_dana_id'=>$row['sumber_dana_id'],'keterangan'=>$row['keterangan']];
        if (str_ends_with($to, '_p') || $to === 'dppa') {
            foreach (['jenis_standar_harga','id_standar_harga','komponen','spesifikasi','tkdn','pajak','harga_satuan','volume','jumlah'] as $field) if (array_key_exists($field, $row) && in_array($field.'_awal', $columns, true)) $payload[$field.'_awal'] = $row[$field];
            if (in_array('status_perubahan', $columns, true)) $payload['status_perubahan'] = 'awal';
        }
        if ($to === 'rkpd_p') { unset($payload['source_table'], $payload['source_id']); $payload['source_rkpd_id']=(int)$row['id']; $payload['target_awal']=$row['target']; $payload['pagu_awal']=$row['pagu']; $payload['status_perubahan']='awal'; }
        $payload['setujui']=0; $payload['kunci']=0; $payload['disable']=0; $payload['is_deleted']=0; $payload['tgl_insert']=date('Y-m-d H:i:s'); $payload['username_insert']=$this->user['username'] ?? 'system';
        return array_intersect_key($payload, array_flip($columns));
    }
}
