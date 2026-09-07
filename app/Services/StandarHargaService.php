<?php

require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/PageSetupService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

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
        $pdf = PageSetupService::createPdf($setup,'L');
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
        if (($this->user['type_user'] ?? '') !== 'tapd') throw new Exception('Hanya TAPD yang dapat menyalin standar harga antar-tahun');
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

    /** Impor format ekspor SIPD 9 kolom sekaligus dengan mapping rekeningnya. */
    public function importSipd(string $type, string $file, int $year): array
    {
        $type = $this->validateType($type);
        if (($this->user['type_user'] ?? '') !== 'tapd') {
            throw new Exception('Hanya TAPD yang dapat mengimpor standar harga SIPD');
        }
        if ($year < 2000 || $year > 2100) throw new Exception('Tahun impor tidak valid');
        if (!is_file($file)) throw new Exception('File impor tidak ditemukan');

        $kdWilayah = (string)($this->user['kd_wilayah'] ?? '');
        if ($kdWilayah === '') throw new Exception('Wilayah pengguna tidak tersedia');
        $peraturanId = $this->resolvePeraturan($type, $year, $kdWilayah);
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($file)->getActiveSheet();
        $expected = [
            'kodekelompokbarang','uraiankelompokbarang','idstandarharga','kodebarang',
            'uraianbarang','spesifikasi','satuan','hargasatuan','koderekening'
        ];
        $headers = [];
        foreach ($sheet->rangeToArray('A1:I1', null, true, true, false)[0] as $index => $header) {
            $headers[$this->normalizeSipdHeader((string)$header)] = $index;
        }
        foreach ($expected as $header) if (!array_key_exists($header, $headers)) {
            throw new Exception("Format SIPD tidak valid: kolom {$header} tidak ditemukan");
        }

        $stats = ['total'=>0,'inserted'=>0,'updated'=>0,'mapped'=>0,'unmapped_accounts'=>0,'skipped'=>0,'tahun'=>$year,'tipe'=>$type];
        $unitCache = [];
        $this->db->begin();
        try {
            for ($rowNumber = 2, $last = $sheet->getHighestDataRow(); $rowNumber <= $last; $rowNumber++) {
                $values = $sheet->rangeToArray("A{$rowNumber}:I{$rowNumber}", null, true, true, false)[0];
                $code = trim((string)$values[$headers['kodebarang']]);
                $name = trim((string)$values[$headers['uraianbarang']]);
                if ($code === '' && $name === '') { $stats['skipped']++; continue; }
                if ($code === '' || $name === '') throw new Exception("Baris {$rowNumber}: kode dan uraian barang wajib diisi");
                $stats['total']++;
                $unitName = trim((string)$values[$headers['satuan']]);
                $unitId = $this->resolveOrCreateUnit($unitName, $peraturanId, $unitCache);
                $sipdId = trim((string)$values[$headers['idstandarharga']]);
                $price = $this->parseSipdNumber($values[$headers['hargasatuan']], $rowNumber);
                $data = [
                    'sipd_id'=>$sipdId !== '' ? $sipdId : null,
                    'kode'=>$code,
                    'kode_aset'=>trim((string)$values[$headers['kodekelompokbarang']]) ?: null,
                    'kode_kelompok'=>trim((string)$values[$headers['kodekelompokbarang']]) ?: null,
                    'kelompok_barang'=>trim((string)$values[$headers['uraiankelompokbarang']]) ?: null,
                    'uraian'=>$name,
                    'spesifikasi'=>trim((string)$values[$headers['spesifikasi']]) ?: null,
                    'satuan_id'=>$unitId,
                    'harga'=>$price,
                    'disable'=>0,'is_deleted'=>0,
                    'tgl_update'=>date('Y-m-d H:i:s'),
                    'username_update'=>$this->user['username'] ?? 'IMPORT_SIPD'
                ];
                $existing = $this->db->query(
                    "SELECT id FROM master_biaya WHERE tipe=? AND kd_wilayah=? AND tahun=? AND peraturan_id=? AND is_deleted=0 AND (kode=? OR (sipd_id IS NOT NULL AND sipd_id=?)) ORDER BY id LIMIT 1",
                    [$type,$kdWilayah,$year,$peraturanId,$code,$sipdId]
                )->fetch();
                if ($existing) {
                    $masterId=(int)$existing['id'];
                    $this->db->update('master_biaya',$data,'WHERE id=?',[$masterId]);
                    $stats['updated']++;
                } else {
                    $data += ['tipe'=>$type,'kd_wilayah'=>$kdWilayah,'tahun'=>$year,'peraturan_id'=>$peraturanId,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$this->user['username'] ?? 'IMPORT_SIPD'];
                    unset($data['tgl_update'],$data['username_update']);
                    $masterId=(int)$this->db->insert('master_biaya',$data);
                    $stats['inserted']++;
                }
                $accounts = preg_split('/\s*,\s*/', trim((string)$values[$headers['koderekening']]), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                foreach (array_unique($accounts) as $account) {
                    if (!$this->db->query('SELECT id FROM akun_neo WHERE kode=? AND is_deleted=0 LIMIT 1',[$account])->fetch()) $stats['unmapped_accounts']++;
                    $mapping=$this->db->query('SELECT id,is_deleted FROM master_biaya_akun WHERE master_biaya_id=? AND kd_akun=? AND peraturan_id=? LIMIT 1',[$masterId,$account,$peraturanId])->fetch();
                    if ($mapping) {
                        $this->db->update('master_biaya_akun',['is_deleted'=>0,'disable'=>0,'kd_wilayah'=>$kdWilayah,'tgl_update'=>date('Y-m-d H:i:s'),'username_update'=>$this->user['username'] ?? 'IMPORT_SIPD'],'WHERE id=?',[(int)$mapping['id']]);
                    } else {
                        $this->db->insert('master_biaya_akun',['master_biaya_id'=>$masterId,'kd_akun'=>$account,'kd_wilayah'=>$kdWilayah,'peraturan_id'=>$peraturanId,'disable'=>0,'is_deleted'=>0,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$this->user['username'] ?? 'IMPORT_SIPD']);
                    }
                    $stats['mapped']++;
                }
            }
            $this->db->commit();
            return $stats;
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    private function normalizeSipdHeader(string $header): string
    {
        return strtolower((string)preg_replace('/[^a-z0-9]/i','',trim($header)));
    }

    private function parseSipdNumber(mixed $value, int $row): float
    {
        if (is_numeric($value)) return (float)$value;
        $normalized = preg_replace('/[^0-9,.-]/','',(string)$value);
        if (str_contains($normalized, ',') && !str_contains($normalized, '.')) $normalized=str_replace(',','.',$normalized);
        else $normalized=str_replace(',','',$normalized);
        if (!is_numeric($normalized)) throw new Exception("Baris {$row}: harga satuan tidak valid");
        return (float)$normalized;
    }

    private function resolveOrCreateUnit(string $name, int $peraturanId, array &$cache): int
    {
        $key=mb_strtolower(trim($name));
        if ($key==='') throw new Exception('Satuan kosong pada workbook SIPD');
        if (isset($cache[$key])) return $cache[$key];
        $row=$this->db->query('SELECT id FROM satuan_neo WHERE peraturan_id=? AND is_deleted=0 AND (LOWER(TRIM(uraian))=? OR LOWER(TRIM(value))=? OR FIND_IN_SET(?,LOWER(REPLACE(sebutan_lain," ","")))>0) ORDER BY id LIMIT 1',[$peraturanId,$key,$key,str_replace(' ','',$key)])->fetch();
        if ($row) return $cache[$key]=(int)$row['id'];
        return $cache[$key]=(int)$this->db->insert('satuan_neo',['value'=>$name,'uraian'=>$name,'sebutan_lain'=>'','disable'=>0,'keterangan'=>'Dibuat otomatis dari impor SIPD','peraturan_id'=>$peraturanId,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$this->user['username'] ?? 'IMPORT_SIPD','is_deleted'=>0]);
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
