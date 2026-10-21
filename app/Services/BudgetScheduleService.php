<?php
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/BudgetScheduleDefaults.php';

class BudgetScheduleService
{
    public const STAGES = [
        'renstra' => ['Renstra','renstra','kunci_renstra'],
        'rkpd' => ['RKPD','rkpd','kunci_rkpd'],
        'renja' => ['Renja','renja','kunci_renja'],
        'rka' => ['RKA','rka','kunci_rka'],
        'dpa' => ['DPA','dpa','kunci_dpa'],
        'rkpd_p' => ['RKPD Perubahan','rkpd_perubahan','kunci_rkpd_perubahan'],
        'renja_p' => ['Renja Perubahan','renja_p','kunci_renja_p'],
        'rka_p' => ['RKA Perubahan','rka_p','kunci_rka_p'],
        'dppa' => ['DPPA','dppa','kunci_dppa'],
        'kua_ppas' => ['KUA PPAS','kua_ppas',null],
        'rapbd' => ['RAPBD','rapbd',null],
        'rak' => ['RAK','rak',null],
        'kua_ppas_perubahan' => ['KUA PPAS Perubahan','kua_ppas_perubahan',null],
        'apbd_perubahan' => ['APBD Perubahan','apbd_perubahan',null],
    ];

    public static function stage(string $table): ?string
    {
        $key = preg_replace('/_neo$/', '', $table);
        if (isset(self::STAGES[$key])) return $key;
        if (in_array($key, ['misi_renstra','tujuan_renstra','sasaran_renstra','indikator_sasaran_renstra','program_renstra','indikator_program_renstra','kegiatan_renstra','sub_kegiatan_renstra','renstra_skpd','tujuan_sasaran_renstra'], true)) return 'renstra';
        return null;
    }

    public static function canManage(array $user): bool
    {
        return in_array($user['type_user'] ?? '', ['super_admin','admin_wilayah','tapd'], true);
    }

    public static function timezone(): DateTimeZone
    {
        return new DateTimeZone(getenv('SESENDOK_TIMEZONE') ?: 'Asia/Makassar');
    }

    private static function date(?string $value): ?DateTimeImmutable
    {
        if (!$value) return null;
        $value = str_replace('T', ' ', trim($value));
        foreach (['Y-m-d H:i:s','Y-m-d H:i'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value, self::timezone());
            if ($date && $date->format($format) === $value) return $date;
        }
        return null;
    }

    public static function evaluate(string $stage, array $settings, ?DateTimeImmutable $now = null): array
    {
        [$label,$period,$lock] = self::STAGES[$stage];
        $start = self::date($settings['awal_' . $period] ?? null);
        $end = self::date($settings['akhir_' . $period] ?? null);
        $locked = (int)($settings['kunci'] ?? 0) === 1 || ($lock && (int)($settings[$lock] ?? 0) === 1);
        $now ??= new DateTimeImmutable('now', self::timezone());
        $configured = $start && $end && $start < $end;
        $enabled = !$locked && $configured && $now >= $start && $now <= $end;
        $status = $enabled ? 'Dibuka' : ($locked ? 'Dikunci' : (!$configured ? 'Belum diatur' : 'Di luar jadwal'));
        $message = 'Input ' . $label . ': ' . $status . '.';
        if ($configured) $message .= ' Jadwal ' . $start->format('d-m-Y H:i') . ' s.d. ' . $end->format('d-m-Y H:i') . ' (' . self::timezone()->getName() . ').';
        return ['enabled'=>(bool)$enabled,'locked'=>(bool)$locked,'status'=>$status,'label'=>$label,'message'=>$message,'start'=>$start ? $start->format(DATE_ATOM) : null,'end'=>$end ? $end->format(DATE_ATOM) : null];
    }

    public static function settings($db, array $user): array
    {
        return $db->query('SELECT * FROM pengaturan_neo WHERE kd_wilayah=? AND tahun=? AND is_deleted=0 ORDER BY id DESC LIMIT 1', [$user['kd_wilayah'] ?? '', (int)($user['tahun'] ?? 0)])->fetch() ?: [];
    }

    public static function statuses($db, array $user): array
    {
        $settings = self::settings($db, $user);
        $result = [];
        foreach (self::STAGES as $stage => $_) $result[$stage] = self::evaluate($stage, $settings);
        return $result;
    }

    public static function assertOpen($db, array $user, string $table): void
    {
        $stage = self::stage($table);
        if (!$stage || self::canManage($user)) return;
        $status = self::evaluate($stage, self::settings($db, $user));
        if (!$status['enabled']) throw new RuntimeException($status['message'] . ' Hubungi TAPD wilayah.');
    }

    public static function validate(array $settings): void
    {
        if (!empty($settings['jadwal_acuan_json'])) {
            $anchors=json_decode((string)$settings['jadwal_acuan_json'],true);
            if (!is_array($anchors)) throw new InvalidArgumentException('Tanggal acuan tidak valid.');
            BudgetScheduleDefaults::generate((int)($settings['tahun']??2026),(string)($settings['kd_wilayah']??''),$anchors);
        }

        foreach (self::STAGES as [$label,$period]) {
            $start = $settings['awal_' . $period] ?? null;
            $end = $settings['akhir_' . $period] ?? null;
            if (!$start && !$end) continue;
            $a = self::date($start); $b = self::date($end);
            if (!$a || !$b || $a >= $b) throw new InvalidArgumentException('Jadwal ' . $label . ': tanggal mulai dan selesai wajib valid; selesai harus setelah mulai.');
        }
    }

    public static function applyDefaults($db, array $user, array $anchors = []): void
    {
        if (!self::canManage($user)) throw new RuntimeException('Hanya TAPD atau administrator wilayah yang dapat mengatur jadwal.');
        $row=self::settings($db,$user);
        if (!$row) throw new RuntimeException('Konfigurasi tahun aktif belum dibuat administrator wilayah.');
        $saved=json_decode($row['jadwal_acuan_json']??'{}',true)?:[];
        $oldDefaults=BudgetScheduleDefaults::generate((int)$user['tahun'],$user['kd_wilayah'],$saved)['fields'];
        foreach (BudgetScheduleDefaults::ANCHORS as $key=>$_) if (isset($anchors[$key]) && trim((string)$anchors[$key])!=='') $saved[$key]=trim((string)$anchors[$key]);
        $row['jadwal_acuan_json']=json_encode($saved);
        $newDefaults=BudgetScheduleDefaults::generate((int)$user['tahun'],$user['kd_wilayah'],$saved)['fields'];
        foreach (self::STAGES as [$label,$period]) {
            $start='awal_'.$period;$end='akhir_'.$period;
            if (isset($oldDefaults[$start],$newDefaults[$start]) && ($row[$start]??null)===$oldDefaults[$start] && ($row[$end]??null)===$oldDefaults[$end]) {
                $row[$start]=$newDefaults[$start];$row[$end]=$newDefaults[$end];
            }
        }
        $filled=BudgetScheduleDefaults::fillMissing($row,(int)$user['tahun'],$user['kd_wilayah']);
        $data=['jadwal_acuan_json'=>$row['jadwal_acuan_json']];
        foreach ($filled as $key=>$value) if (str_starts_with($key,'awal_') || str_starts_with($key,'akhir_')) $data[$key]=$value;
        self::save($db,$user,$data);
    }

    public static function save($db, array $user, array $input): void
    {
        if (!self::canManage($user)) throw new RuntimeException('Hanya TAPD atau administrator wilayah yang dapat mengatur jadwal.');
        if (empty($user['kd_wilayah']) || empty($user['tahun'])) throw new RuntimeException('Wilayah dan tahun aktif wajib dipilih.');
        $row = self::settings($db, $user);
        if (!$row) throw new RuntimeException('Konfigurasi wilayah untuk tahun aktif belum tersedia. Administrator wilayah perlu membuat konfigurasi tahun terlebih dahulu.');
        $data = [];
        foreach (self::STAGES as [$label,$period,$lock]) {
            foreach (['awal_' . $period,'akhir_' . $period] as $field) {
                if (!array_key_exists($field, $input)) continue;
                $raw = trim((string)$input[$field]);
                $date = self::date($raw);
                if ($raw !== '' && !$date) throw new InvalidArgumentException('Format jadwal ' . $label . ' tidak valid.');
                $data[$field] = $date ? $date->format('Y-m-d H:i:s') : null;
            }
            if ($lock && array_key_exists($lock, $input)) $data[$lock] = self::boolean($input[$lock]);
        }
        if (array_key_exists('kunci', $input)) $data['kunci'] = self::boolean($input['kunci']);
        if (isset($input['jadwal_acuan_json'])) {
            $anchors=json_decode((string)$input['jadwal_acuan_json'],true);
            if (!is_array($anchors)) throw new InvalidArgumentException('Tanggal acuan tidak valid.');
            $anchors=array_intersect_key($anchors,BudgetScheduleDefaults::ANCHORS);
            BudgetScheduleDefaults::generate((int)$user['tahun'],$user['kd_wilayah'],$anchors);
            $data['jadwal_acuan_json']=json_encode($anchors);
        }
        if (!$data) throw new InvalidArgumentException('Tidak ada jadwal yang diubah.');
        self::validate(array_replace($row, $data));
        $data['username_update'] = $user['username'] ?? 'system';
        $data['tgl_update'] = (new DateTimeImmutable('now', self::timezone()))->format('Y-m-d H:i:s');
        $db->update('pengaturan_neo', $data, 'WHERE id=? AND kd_wilayah=? AND tahun=?', [$row['id'],$user['kd_wilayah'],$user['tahun']]);
    }

    private static function boolean($value): int
    {
        if (!in_array((string)$value, ['0','1'], true)) throw new InvalidArgumentException('Status kunci harus 0 atau 1.');
        return (int)$value;
    }
}
