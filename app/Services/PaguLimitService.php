<?php

require_once __DIR__ . '/../Core/DB.php';

class PaguLimitService
{
    private DB $db;
    private array $user;

    private const DOCUMENTS = [
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
        $amount = (float)($data['jumlah'] ?? 0);

        if ($wilayah === '' || $opd === '' || $opd === '0' || !$tahun) {
            throw new RuntimeException('Scope wilayah, OPD, dan tahun untuk validasi pagu tidak lengkap.');
        }
        if ($amount < 0) throw new InvalidArgumentException('Nilai pagu tidak boleh negatif.');

        $limit = $this->db->query(
            'SELECT pagu_maksimal FROM batas_pagu_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND dokumen=? AND is_deleted=0 LIMIT 1 FOR UPDATE',
            [$wilayah, $opd, $tahun, $document]
        )->fetch();
        if (!$limit) {
            throw new RuntimeException('Batas pagu ' . strtoupper($document) . ' untuk OPD ini belum ditetapkan admin wilayah.');
        }

        $sql = "SELECT COALESCE(SUM(jumlah),0) total FROM `$table` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0";
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
