<?php

require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/PageSetupService.php';

class StandarHargaService
{
    private DB $db;
    private array $user;
    private const TYPES = ['ssh', 'hspk', 'asb', 'sbu'];

    public function __construct(array $user)
    {
        $this->db = DB::getInstance();
        $this->user = $user;
    }

    public function exportPdf(string $type): string
    {
        $type = $this->validateType($type);
        $scope = $this->scope();
        $rows = $this->db->query(
            "SELECT mb.kode, mb.kode_aset, mb.uraian, mb.spesifikasi,
                    s.uraian AS satuan, mb.harga, mb.tkdn
             FROM master_biaya mb
             LEFT JOIN satuan_neo s ON s.id = mb.satuan_id
             WHERE mb.tipe = ? AND mb.kd_wilayah = ? AND mb.tahun = ?
               AND mb.peraturan_id = ? AND mb.is_deleted = 0
             ORDER BY mb.kode ASC",
            [$type, $scope['kd_wilayah'], $scope['tahun'], $scope['peraturan_id']]
        )->fetchAll();

        $setup=PageSetupService::current($this->user);
        $pdf = new TCPDF(PageSetupService::orientation($setup,'L'), 'mm', PageSetupService::tcpdfFormat($setup), true, 'UTF-8');
        $pdf->SetCreator('seSendok');
        $pdf->SetTitle(strtoupper($type) . ' Tahun ' . $scope['tahun']);
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(true, 12);
        PageSetupService::applyPdf($pdf,$setup,[10,12,10,12]);
        $pdf->AddPage();
        $pdf->SetFont($setup['font'], 'B', max(10,(float)$setup['font_size']+3));
        $pdf->Cell(0, 8, 'DAFTAR ' . strtoupper($type) . ' TAHUN ' . $scope['tahun'], 0, 1, 'C');
        $pdf->Ln(2);

        $html = '<table border="1" cellpadding="4"><thead><tr style="font-weight:bold;background-color:#eeeeee">'
            . '<th width="4%">No</th><th width="12%">Kode</th><th width="12%">Kode Aset</th>'
            . '<th width="27%">Uraian</th><th width="20%">Spesifikasi</th><th width="8%">Satuan</th>'
            . '<th width="12%">Harga</th><th width="5%">TKDN</th></tr></thead><tbody>';

        foreach ($rows as $index => $row) {
            $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            $html .= '<tr><td width="4%">' . ($index + 1) . '</td>'
                . '<td width="12%">' . $escape($row['kode']) . '</td>'
                . '<td width="12%">' . $escape($row['kode_aset']) . '</td>'
                . '<td width="27%">' . $escape($row['uraian']) . '</td>'
                . '<td width="20%">' . $escape($row['spesifikasi']) . '</td>'
                . '<td width="8%">' . $escape($row['satuan']) . '</td>'
                . '<td width="12%" align="right">' . number_format((float)$row['harga'], 2, ',', '.') . '</td>'
                . '<td width="5%" align="right">' . number_format((float)$row['tkdn'], 2, ',', '.') . '</td></tr>';
        }

        if (!$rows) {
            $html .= '<tr><td colspan="8" align="center">Tidak ada data</td></tr>';
        }

        $pdf->SetFont($setup['font'], '', max(6,(float)$setup['font_size']-2));
        $pdf->writeHTML($html . '</tbody></table>', true, false, true, false, '');
        return $pdf->Output('', 'S');
    }

    public function copyYear(string $type, int $targetYear, array $onlyIds = []): array
    {
        $type = $this->validateType($type);
        if (!in_array($this->user['type_user'] ?? '', ['super_admin', 'admin_wilayah'], true)) {
            throw new Exception('Tidak memiliki hak akses untuk copy tahun');
        }
        if ($targetYear < 2000 || $targetYear > 2100) {
            throw new Exception('Tahun tujuan tidak valid');
        }

        $source = $this->scope();
        if ($targetYear === $source['tahun']) {
            throw new Exception('Tahun tujuan harus berbeda dari tahun sumber');
        }
        $targetPeraturan = $this->resolvePeraturan($type, $targetYear, $source['kd_wilayah']);

        $this->db->begin();
        try {
            $idFilter = '';
            $sourceParams = [$type, $source['kd_wilayah'], $source['tahun'], $source['peraturan_id']];
            if ($onlyIds) {
                $onlyIds = array_values(array_filter(array_map('intval', $onlyIds), fn($id) => $id > 0));
                if (!$onlyIds) throw new Exception('ID copy test tidak valid');
                $idFilter = ' AND id IN (' . implode(',', array_fill(0, count($onlyIds), '?')) . ')';
                $sourceParams = array_merge($sourceParams, $onlyIds);
            }

            $rows = $this->db->query(
                "SELECT * FROM master_biaya
                 WHERE tipe = ? AND kd_wilayah = ? AND tahun = ?
                   AND peraturan_id = ? AND is_deleted = 0 $idFilter
                 FOR UPDATE",
                $sourceParams
            )->fetchAll();

            $copied = 0;
            $skipped = 0;
            foreach ($rows as $row) {
                $exists = $this->db->query(
                    "SELECT id FROM master_biaya
                     WHERE tipe = ? AND kode = ? AND kd_wilayah = ? AND tahun = ?
                       AND peraturan_id = ? AND is_deleted = 0 LIMIT 1",
                    [$type, $row['kode'], $source['kd_wilayah'], $targetYear, $targetPeraturan]
                )->fetch();
                if ($exists) {
                    $skipped++;
                    continue;
                }

                $oldId = (int)$row['id'];
                unset($row['id']);
                $row['tahun'] = $targetYear;
                $row['peraturan_id'] = $targetPeraturan;
                $row['tgl_insert'] = date('Y-m-d H:i:s');
                $row['username_insert'] = $this->user['username'] ?? 'system';
                $row['tgl_update'] = null;
                $row['username_update'] = null;
                $newId = (int)$this->db->insert('master_biaya', $row);

                $mappings = $this->db->query(
                    "SELECT kd_akun, disable FROM master_biaya_akun
                     WHERE master_biaya_id = ? AND is_deleted = 0",
                    [$oldId]
                )->fetchAll();
                foreach ($mappings as $mapping) {
                    $this->db->insert('master_biaya_akun', [
                        'master_biaya_id' => $newId,
                        'kd_akun' => $mapping['kd_akun'],
                        'kd_wilayah' => $source['kd_wilayah'],
                        'peraturan_id' => $targetPeraturan,
                        'disable' => $mapping['disable'] ?? 0,
                        'is_deleted' => 0,
                        'tgl_insert' => date('Y-m-d H:i:s'),
                        'username_insert' => $this->user['username'] ?? 'system'
                    ]);
                }
                $copied++;
            }
            $this->db->commit();
            return ['copied' => $copied, 'skipped' => $skipped, 'target_year' => $targetYear];
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    private function scope(): array
    {
        $kdWilayah = $this->user['kd_wilayah'] ?? null;
        $tahun = (int)($this->user['tahun'] ?? 0);
        if (!$kdWilayah || !$tahun) throw new Exception('Scope wilayah/tahun pengguna tidak lengkap');
        return [
            'kd_wilayah' => $kdWilayah,
            'tahun' => $tahun,
            'peraturan_id' => $this->resolvePeraturan($this->user['_standar_type'] ?? 'ssh', $tahun, $kdWilayah)
        ];
    }

    private function resolvePeraturan(string $type, int $year, string $kdWilayah): int
    {
        $column = 'aturan_' . $this->validateType($type);
        $row = $this->db->query(
            "SELECT `$column` AS peraturan_id FROM pengaturan_neo
             WHERE kd_wilayah = ? AND tahun = ? AND disable = 0 AND is_deleted = 0
             ORDER BY id DESC LIMIT 1",
            [$kdWilayah, $year]
        )->fetch();
        if (!$row || empty($row['peraturan_id'])) {
            throw new Exception("Pengaturan/peraturan $type tahun $year tidak ditemukan");
        }
        return (int)$row['peraturan_id'];
    }

    private function validateType(string $type): string
    {
        $type = strtolower(trim($type));
        if (!in_array($type, self::TYPES, true)) throw new Exception('Jenis standar harga tidak valid');
        $this->user['_standar_type'] = $type;
        return $type;
    }
}
