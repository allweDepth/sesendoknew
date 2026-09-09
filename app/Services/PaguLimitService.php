<?php

require_once __DIR__ . '/../Core/DB.php';

class PaguLimitService
{
    private DB $db;
    private array $user;

    private const DOCUMENTS = [
        'rkpd_neo' => 'rkpd',
        'rkpd_p_neo' => 'rkpd_p',
        'renja_neo' => 'renja',
        'rka_neo' => 'rka',
        'dpa_neo' => 'dpa',
        'renja_p_neo' => 'renja_p',
        'rka_p_neo' => 'rka_p',
        'dppa_neo' => 'dppa',
    ];

    public function __construct(array $user = [])
    {
        $this->db = DB::getInstance();
        $this->user = $user;
    }

    public function validate(string $table, array $data, ?int $excludeId = null): void
    {
        $document = self::DOCUMENTS[$table] ?? null;
        if ($document === null) return;

        $wilayah = (string)($data['kd_wilayah'] ?? $this->user['kd_wilayah'] ?? '');
        $opd = (string)($data['kd_opd'] ?? $this->user['kd_opd'] ?? '');
        $tahun = (int)($data['tahun'] ?? $this->user['tahun'] ?? 0);
        $amount = (float)($data[in_array($table,['rkpd_neo','rkpd_p_neo'],true)?'pagu':'jumlah'] ?? 0);

        if ($wilayah === '' || $opd === '' || $opd === '0' || !$tahun) {
            throw new RuntimeException('Scope wilayah, OPD, dan tahun untuk validasi pagu tidak lengkap.');
        }
        if ($amount < 0) throw new InvalidArgumentException('Nilai pagu tidak boleh negatif.');

        // Pagu sebuah uraian DPA/DPPA tidak boleh diturunkan melewati jumlah
        // alokasi kontrak yang sudah disetujui. Draft tidak mengunci pagu.
        if ($excludeId && in_array($table, ['dpa_neo', 'dppa_neo'], true)) {
            $stage = $table === 'dpa_neo' ? 'dpa' : 'dppa';
            $contracted = (float)($this->db->query(
                'SELECT COALESCE(SUM(ci.nilai_kontrak),0) total
                   FROM kontrak_item_neo ci
                   JOIN kontrak_neo k ON k.id=ci.kontrak_id AND k.is_deleted=0 AND k.setujui=1
                  WHERE ci.tahap=? AND ci.anggaran_id=? AND ci.is_deleted=0',
                [$stage, $excludeId]
            )->fetch()['total'] ?? 0);
            if ($amount + 0.01 < $contracted) {
                throw new RuntimeException(
                    'Pagu ' . strtoupper($stage) . ' tidak boleh lebih kecil dari akumulasi kontrak yang telah disetujui Rp ' .
                    number_format($contracted, 2, ',', '.') . '.'
                );
            }
        }

        $limit = $this->db->query(
            'SELECT pagu_maksimal FROM batas_pagu_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND dokumen=? AND is_deleted=0 LIMIT 1 FOR UPDATE',
            [$wilayah, $opd, $tahun, $document]
        )->fetch();
        if (!$limit) {
            throw new RuntimeException('Batas pagu ' . strtoupper($document) . ' untuk OPD ini belum ditetapkan admin wilayah.');
        }

        $amountField=in_array($table,['rkpd_neo','rkpd_p_neo'],true)?'pagu':'jumlah';
        $sql = "SELECT COALESCE(SUM(`$amountField`),0) total FROM `$table` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0";
        $params = [$wilayah, $opd, $tahun];
        if ($excludeId) {
            $sql .= ' AND id<>?';
            $params[] = $excludeId;
        }
        $used = (float)($this->db->query($sql, $params)->fetch()['total'] ?? 0);
        $maximum = (float)$limit['pagu_maksimal'];
        $candidate = $used + $amount;
        if ($candidate > $maximum + 0.01) {
            $remaining = max(0, $maximum - $used);
            throw new RuntimeException(
                'Pagu ' . strtoupper($document) . ' melebihi batas OPD. Batas Rp ' . number_format($maximum, 0, ',', '.') .
                ', terpakai Rp ' . number_format($used, 0, ',', '.') .
                ', sisa Rp ' . number_format($remaining, 0, ',', '.') . '.'
            );
        }
    }
}
