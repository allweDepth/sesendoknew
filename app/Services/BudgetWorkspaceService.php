<?php

require_once __DIR__.'/BudgetScheduleService.php';

final class BudgetWorkspaceService
{
    private const EDITORS = ['super_admin','admin_wilayah','admin_opd','kepala_opd','pa_kpa'];

    private const TABLES = [
        'renja'=>'renja_neo', 'rka'=>'rka_neo', 'dpa'=>'dpa_neo',
        'renja_p'=>'renja_p_neo', 'rka_p'=>'rka_p_neo', 'dppa'=>'dppa_neo',
    ];

    public function __construct(private array $user, private string $document)
    {
        $this->table = self::TABLES[$document] ?? throw new InvalidArgumentException('Dokumen anggaran tidak valid.');
        $this->db = DB::getInstance();
    }

    private DB $db;
    private string $table;

    private function scope(): array
    {
        $scope = [$this->user['kd_wilayah'] ?? '', $this->user['kd_opd'] ?? '', (int)($this->user['tahun'] ?? 0)];
        if (!$scope[0] || !$scope[1] || $scope[1] === '0' || !$scope[2]) {
            throw new InvalidArgumentException('Pilih OPD pada konteks aktif untuk membuka informasi subkegiatan.');
        }
        return $scope;
    }

    private function codes(string $sub): array
    {
        if (!preg_match('/^\d+\.\d{2}\.\d{2}\.\d+\.\d{2}\.\d+$/D', $sub)) {
            throw new InvalidArgumentException('Kode subkegiatan tidak valid.');
        }
        return [implode('.', array_slice(explode('.', $sub), 0, 3)), substr($sub, 0, strrpos($sub, '.')), $sub];
    }

    public function read(string $sub): array
    {
        $scope = $this->scope();
        [$program, $activity] = $this->codes($sub);
        $condition = 'kd_wilayah=? AND kd_opd=? AND tahun=? AND kd_sub_keg=? AND is_deleted=0';
        $meta = $this->db->query("SELECT * FROM sub_keg_renja_neo WHERE $condition ORDER BY id DESC LIMIT 1", [...$scope,$sub])->fetch() ?: [];
        $target = $this->db->query("SELECT * FROM renja_sub_kegiatan_kinerja_neo WHERE $condition ORDER BY id DESC LIMIT 1", [...$scope,$sub])->fetch() ?: [];
        $budget = $this->db->query(
            "SELECT COUNT(*) items, COALESCE(SUM(jumlah),0) total, MAX(kunci) locked, MAX(setujui) approved FROM {$this->table} WHERE $condition",
            [...$scope,$sub]
        )->fetch();
        if (!$budget['items'] && (!$meta || !in_array($this->document, ['renja','rka'], true))) {
            throw new RuntimeException('Subkegiatan tidak ditemukan dalam dokumen dan OPD aktif.');
        }

        $funds = $this->db->query(
            "SELECT COALESCE(NULLIF(r.sumber_dana_teks,''),s.uraian,'') sumber_dana,SUM(r.jumlah) jumlah
             FROM {$this->table} r LEFT JOIN sumber_dana_neo s ON s.id=r.sumber_dana_id
             WHERE r.kd_wilayah=? AND r.kd_opd=? AND r.tahun=? AND r.kd_sub_keg=? AND r.is_deleted=0
             GROUP BY 1 ORDER BY 1", [...$scope,$sub]
        )->fetchAll();
        $labels = $this->db->query(
            'SELECT kode,uraian FROM rekening_kegiatan WHERE kode IN (?,?,?,?)', [$program,$activity,$sub,substr($program,0,strrpos($program,'.'))]
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        $labels = array_replace($labels, $this->db->query(
            'SELECT kode,uraian FROM rekening_kegiatan_historis WHERE kd_wilayah=? AND tahun=? AND kode IN (?,?,?)',
            [$scope[0],$scope[2],$program,$activity,$sub]
        )->fetchAll(PDO::FETCH_KEY_PAIR));
        $organization = $this->db->query('SELECT uraian FROM organisasi_neo WHERE kd_wilayah=? AND kode=? AND is_deleted=0 ORDER BY id LIMIT 1', array_slice($scope,0,2))->fetchColumn();
        $source = json_decode($meta['keterangan_json'] ?? '', true)['indikator'] ?? [];
        $asIndicator = static fn(?string $text, ?string $value) => $text
            ? [['tolok_ukur'=>$text,'target'=>$value ?? '','satuan'=>'']] : [];
        $default = [
            $sub => [
                'prioritas_provinsi'=>'','prioritas_kabupaten'=>'','label'=>'','lokasi'=>$meta['lokasi'] ?? $target['lokasi'] ?? '',
                'rincian_lokasi'=>'','mulai'=>$meta['awal_pelaksanaan'] ?? '','selesai'=>$meta['akhir_pelaksanaan'] ?? '',
                'anggaran_n1'=>$target['prakiraan_maju_pagu'] ?? '', 'anggaran_n2'=>'',
                'indikator'=>!empty($target['indikator_keluaran']) ? [[
                    'tolok_ukur'=>$target['indikator_keluaran'],'target'=>$target['target'],'satuan'=>$target['satuan'],
                ]] : $asIndicator($meta['tolak_ukur_keluaran'] ?? null,$meta['target_kinerja_keluaran'] ?? null),
            ],
            $program => ['indikator'=>$asIndicator($source['Capaian Program'][0] ?? $meta['tolak_ukur_capaian_keg'] ?? null, $source['Capaian Program'][1] ?? $meta['target_kinerja_capaian_keg'] ?? null)],
            $activity => [
                'kelompok_sasaran'=>$target['kelompok_sasaran'] ?? '', 'prioritas_nasional'=>'','keluaran'=>[],
                'hasil'=>$asIndicator($source['Hasil'][0] ?? $meta['tolak_ukur_hasil'] ?? null,$source['Hasil'][1] ?? $meta['target_kinerja_hasil'] ?? null),
            ],
        ];
        foreach ($this->db->query(
            'SELECT kode,data_json FROM anggaran_metadata_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND dokumen=? AND kode IN (?,?,?)',
            [...$scope,$this->document,$program,$activity,$sub]
        ) as $row) {
            $default[$row['kode']] = array_replace($default[$row['kode']],json_decode($row['data_json'],true,512,JSON_THROW_ON_ERROR));
        }
        $canEdit = in_array($this->user['type_user'] ?? '',self::EDITORS,true);
        $schedule = BudgetScheduleService::evaluate($this->document, BudgetScheduleService::settings($this->db,$this->user));
        $canEdit = $canEdit && (BudgetScheduleService::canManage($this->user) || $schedule['enabled']);
        $programLocked = $this->db->query(
            "SELECT 1 FROM {$this->table} WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND kd_sub_keg LIKE ?
             AND is_deleted=0 AND (kunci=1 OR setujui=1) LIMIT 1", [...$scope,$program.'.%']
        )->fetchColumn();
        return [
            'code'=>$sub,'program_code'=>$program,'activity_code'=>$activity,
            'name'=>$labels[$sub] ?? $meta['uraian'] ?? $sub, 'program_name'=>$labels[$program] ?? $program,
            'activity_name'=>$labels[$activity] ?? $activity, 'bidang'=>$labels[substr($program,0,strrpos($program,'.'))] ?? '',
            'organization'=>$organization ?: $scope[1], 'funds'=>$funds, 'fund_text'=>$meta['sumber_dana'] ?? '',
            'budget'=>$meta[in_array($this->document,['renja_p','rka_p','dppa'],true) ? 'jumlah_pagu_p' : 'jumlah_pagu'] ?? $budget['total'], 'detail_total'=>$budget['total'],
            'sub'=>$default[$sub], 'program'=>$default[$program], 'activity'=>$default[$activity],
            'can_edit_sub'=>$canEdit && !$budget['locked'] && !$budget['approved']
                && ($budget['items'] || (empty($meta['kunci']) && empty($meta['setujui']))),
            'can_edit_indicators'=>$canEdit && !$programLocked,
        ];
    }

    public function save(string $sub, string $mode, array $input): void
    {
        if (!in_array($this->user['type_user'] ?? '',self::EDITORS,true)) throw new RuntimeException('Role ini hanya dapat melihat metadata anggaran.');
        $scope = $this->scope();
        [$program, $activity] = $this->codes($sub);
        $payload = match ($mode) {
            'sub' => [$sub=>$this->subInput($input, $scope[2])],
            'indicators' => [
                $program=>['indikator'=>$this->indicators($input['indikator_program'] ?? [])],
                $activity=>[
                    'kelompok_sasaran'=>$this->text($input['kelompok_sasaran'] ?? ''),
                    'prioritas_nasional'=>$this->text($input['prioritas_nasional'] ?? ''),
                    'keluaran'=>$this->indicators($input['keluaran'] ?? []),
                    'hasil'=>$this->indicators($input['hasil'] ?? []),
                ],
            ],
            default => throw new InvalidArgumentException('Jenis penyuntingan tidak valid.'),
        };
        $this->db->begin();
        try {
            BudgetScheduleService::assertOpen($this->db,$this->user,$this->document);
            $filter = $mode === 'sub' ? '=' : 'LIKE';
            $key = $mode === 'sub' ? $sub : $program.'.%';
            $locks = $this->db->query(
                "SELECT kunci,setujui FROM {$this->table} WHERE kd_wilayah=? AND kd_opd=? AND tahun=?
                 AND kd_sub_keg $filter ? AND is_deleted=0 FOR UPDATE", [...$scope,$key]
            )->fetchAll();
            foreach ($locks as $row) {
                if ($row['kunci'] || $row['setujui']) throw new RuntimeException('Anggaran dalam lingkup ini sudah disetujui atau dikunci.');
            }
            $state = $this->read($sub);
            if (!$state[$mode === 'sub' ? 'can_edit_sub' : 'can_edit_indicators']) {
                throw new RuntimeException('Metadata anggaran ini tidak dapat diubah.');
            }
            foreach ($payload as $code => $fields) {
                $this->db->query(
                    'INSERT INTO anggaran_metadata_neo (kd_wilayah,kd_opd,tahun,dokumen,kode,data_json,username_update)
                     VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE data_json=VALUES(data_json),username_update=VALUES(username_update)',
                    [...$scope,$this->document,$code,json_encode($fields,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$this->user['username'] ?? '']
                );
            }
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }

    private function subInput(array $input, int $year): array
    {
        $data = [];
        foreach (['prioritas_provinsi','prioritas_kabupaten','label','lokasi','rincian_lokasi'] as $field) {
            $data[$field] = $this->text($input[$field] ?? '');
        }
        foreach (['mulai','selesai'] as $field) {
            $value = $this->text($input[$field] ?? '',10);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d',$value);
            if ($value !== '' && (!$date || $date->format('Y-m-d') !== $value || (int)$date->format('Y') !== $year)) {
                throw new InvalidArgumentException('Tanggal pelaksanaan harus berada pada tahun anggaran aktif.');
            }
            $data[$field] = $value;
        }
        if ($data['mulai'] && $data['selesai'] && $data['mulai'] > $data['selesai']) throw new InvalidArgumentException('Tanggal akhir mendahului tanggal mulai.');
        foreach (['anggaran_n1','anggaran_n2'] as $field) {
            $value = $this->text($input[$field] ?? '',22);
            if ($value !== '' && !preg_match('/^\d{1,16}(?:\.\d{1,2})?$/D',$value)) throw new InvalidArgumentException('Prakiraan anggaran tidak valid.');
            $data[$field] = $value;
        }
        $data['indikator'] = $this->indicators($input['indikator'] ?? []);
        return $data;
    }

    private function indicators($rows): array
    {
        if (!is_array($rows) || count($rows)>30) throw new InvalidArgumentException('Maksimal 30 indikator per kelompok.');
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) throw new InvalidArgumentException('Format indikator tidak valid.');
            $entry = [
                'tolok_ukur'=>$this->text($row['tolok_ukur'] ?? ''),
                'target'=>$this->text($row['target'] ?? '',120),
                'satuan'=>$this->text($row['satuan'] ?? '',80),
            ];
            if ($entry['tolok_ukur'] !== '') $result[] = $entry;
            elseif ($entry['target'] !== '' || $entry['satuan'] !== '') throw new InvalidArgumentException('Tolok ukur wajib diisi untuk indikator dengan target atau satuan.');
        }
        return $result;
    }

    private function text($value, int $limit = 500): string
    {
        if (!is_string($value) || mb_strlen($value)>$limit) throw new InvalidArgumentException("Isian harus berupa teks dengan panjang maksimal $limit karakter.");
        return trim($value);
    }
}
