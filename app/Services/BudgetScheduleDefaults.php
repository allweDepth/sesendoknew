<?php

/** Operational input templates derived from statutory milestones; never replace a saved window. */
class BudgetScheduleDefaults
{
    public const ANCHORS = [
        'rkpd' => 'Tanggal penetapan RKPD',
        'rkpd_provinsi' => 'Tanggal penetapan RKPD provinsi',
        'rkpd_p_provinsi' => 'Tanggal penetapan perubahan RKPD provinsi',
        'rkpd_berikutnya' => 'Tanggal penetapan RKPD tahun berikutnya',
        'rkpd_p' => 'Tanggal penetapan perubahan RKPD',
        'rpjmd' => 'Tanggal penetapan RPJMD',
        'surat_dpa' => 'Tanggal surat penyusunan DPA diterima OPD',
        'dpa' => 'Tanggal pengesahan DPA',
        'surat_dppa' => 'Tanggal surat penyusunan perubahan DPA diterima OPD',
    ];

    private static function day(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, BudgetScheduleService::timezone());
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Tanggal acuan jadwal harus valid (YYYY-MM-DD).');
        return $date;
    }

    private static function monthLater(DateTimeImmutable $day): string
    {
        $month = $day->modify('first day of next month');
        return $month->setDate((int)$month->format('Y'), (int)$month->format('m'), min((int)$day->format('d'), (int)$month->format('t')))->format('Y-m-d');
    }

    public static function generate(int $year, string $region, array $anchors = []): array
    {
        if ($year < 1902 || $year > 2155) throw new InvalidArgumentException('Tahun jadwal tidak valid.');
        $previous = $year - 1;
        $province = !str_contains($region, '.');
        foreach ($anchors as $key => $value) if ($value !== '' && isset(self::ANCHORS[$key])) self::day($value);
        // Week I/II/III are operationalised as dates 1–7 / 8–14 / 15–21.
        // TAPD can refine the windows using actual local enactments and circulars.
        $rkpdEnd = $province ? "$previous-06-30" : "$previous-07-07";
        $changeEnd = $province ? "$year-07-21" : "$year-07-28";
        if (!$province && !empty($anchors['rkpd_provinsi'])) $rkpdEnd=self::day($anchors['rkpd_provinsi'])->modify('+7 days')->format('Y-m-d');
        if (!$province && !empty($anchors['rkpd_p_provinsi'])) $changeEnd=self::day($anchors['rkpd_p_provinsi'])->modify('+7 days')->format('Y-m-d');
        if ($year===2026 && !empty($anchors['rkpd_berikutnya'])) $changeEnd=min($changeEnd,self::day($anchors['rkpd_berikutnya'])->modify('-7 days')->format('Y-m-d'));
        $changeStart=min("$year-06-01",self::day($changeEnd)->modify('-1 day')->format('Y-m-d'));
        $windows = [
            'rkpd' => ["$previous-01-01", $rkpdEnd],
            'renja' => ["$previous-01-01", self::monthLater(self::day($anchors['rkpd'] ?? $rkpdEnd))],
            'kua_ppas' => ["$previous-07-01", "$previous-08-14"],
            'rka' => ["$previous-08-22", "$previous-09-11"],
            'rapbd' => ["$previous-09-12", "$previous-11-30"],
            'rkpd_p' => [$changeStart, $changeEnd],
            'renja_p' => [$changeStart, self::monthLater(self::day($anchors['rkpd_p'] ?? $changeEnd))],
            'kua_ppas_perubahan' => ["$year-08-01", "$year-08-14"],
            'rka_p' => ["$year-08-22", "$year-09-07"],
            'apbd_perubahan' => ["$year-09-08", "$year-09-30"],
        ];
        $notes = [
            'rkpd'=>'Template input Januari sampai target penetapan RKPD; kabupaten/kota memakai rentang konservatif 7 hari kalender sejak target/penetapan RKPD provinsi (aturan 2026 memberi 7 hari kerja).',
            'renja'=>'Penyusunan sejak rancangan awal; penetapan maksimal 1 bulan setelah RKPD. Tanpa tanggal acuan, memakai target penetapan RKPD.',
            'kua_ppas'=>'Template pembahasan sampai target kesepakatan minggu II Agustus pada tahun sebelum tahun anggaran.',
            'rka'=>'Template input setelah target SE minggu III Agustus: 22 Agustus–11 September tahun sebelumnya; menyediakan waktu reviu dan penyusunan RAPBD. Ikuti SE yang sebenarnya.',
            'rapbd'=>'Template penyusunan/pembahasan; target persetujuan bersama paling lambat 30 November tahun sebelumnya.',
            'rkpd_p'=>'Template perubahan mulai Juni; target provinsi minggu III Juli; kabupaten/kota memakai 7 hari kalender secara konservatif. TA 2026 juga dibatasi paling lama 7 hari sebelum RKPD tahun berikutnya ditetapkan; isi tanggal acuannya.',
            'renja_p'=>'Batas penetapan 1 bulan setelah perubahan RKPD; tanpa tanggal acuan memakai target penetapan perubahan RKPD.',
            'kua_ppas_perubahan'=>'Template sampai target kesepakatan minggu II Agustus tahun berjalan.',
            'rka_p'=>'Template input setelah SE perubahan sampai sebelum penyampaian RAPBD-P minggu II September.',
            'apbd_perubahan'=>'Template pembahasan sampai target persetujuan 30 September tahun berjalan.',
            'renstra'=>'Menunggu tanggal RPJMD; penetapan Renstra paling lambat 1 bulan setelah RPJMD (Permendagri 86/2017 Pasal 123).',
            'dpa'=>'Menunggu tanggal surat penyusunan DPA diterima OPD. Default konservatif 6 hari kalender; sesuaikan batas dalam surat PPKD (Permendagri 77/2020 Lampiran V.D).',
            'dppa'=>'Menunggu tanggal surat penyusunan perubahan DPA; rentang konservatif 6 hari kalender, disesuaikan dengan SE dan penetapan perubahan APBD.',
            'rak'=>'Menunggu tanggal pengesahan DPA; penyampaian anggaran kas maksimal 3 hari sejak DPA disahkan (Permendagri 77/2020 Lampiran V.E).',
        ];
        foreach (['renstra'=>['rpjmd',null], 'dpa'=>['surat_dpa',6], 'dppa'=>['surat_dppa',6], 'rak'=>['dpa',3]] as $stage=>[$anchor,$days]) {
            if (empty($anchors[$anchor])) continue;
            $date=self::day($anchors[$anchor]);
            $windows[$stage]=[$date->format('Y-m-d'),$days===null ? self::monthLater($date) : $date->modify('+'.$days.' days')->format('Y-m-d')];
        }
        $fields=[];
        foreach ($windows as $stage=>[$start,$end]) {
            $period=BudgetScheduleService::STAGES[$stage][1];
            $fields['awal_'.$period]=$start.' 00:00:00';
            $fields['akhir_'.$period]=$end.' 23:59:59';
        }
        $sources = [
            ['label'=>'Permendagri 86/2017','url'=>'https://peraturan.bpk.go.id/Download/372669/Permendagri%20Nomor%2086%20Tahun%202017.pdf'],
            ['label'=>'Permendagri 77/2020','url'=>'https://peraturan.bpk.go.id/Download/155483/Permendagri%20Nomor%2077%20Tahun%202020.pdf'],
        ];
        if ($year===2026) {
            $sources[]=['label'=>'Pedoman RKPD 2026 — Permendagri 10/2025','url'=>'https://peraturan.bpk.go.id/Details/321470/permendagri-no-10-tahun-2025'];
            $sources[]=['label'=>'Pedoman APBD 2026 — Permendagri 14/2025','url'=>'https://peraturan.bpk.go.id/Details/331189/permendagri-no-14-tahun-2025'];
        }
        return ['fields'=>$fields,'notes'=>$notes,'sources'=>$sources,'notice'=>'Default adalah rentang input operasional berdasarkan tahapan regulasi. Tanggal penetapan dan SE daerah menjadi acuan pelaksanaan. Minggu I/II/III dipetakan ke tanggal 1–7/8–14/15–21; TAPD dapat menyesuaikan.','annual_notice'=>$year===2026 ? 'Pedoman tahunan 2026 sudah diperiksa.' : 'Template umum: periksa pedoman tahunan yang berlaku untuk TA '.$year.' sebelum menetapkan jadwal.'];
    }

    public static function fillMissing(array $row, int $year, string $region): array
    {
        $anchors=json_decode($row['jadwal_acuan_json']??'{}',true)?:[];
        $defaults=self::generate($year,$region,$anchors)['fields'];
        foreach (BudgetScheduleService::STAGES as [$label,$period]) {
            $start='awal_'.$period;$end='akhir_'.$period;
            if (empty($row[$start]) && empty($row[$end]) && isset($defaults[$start])) {$row[$start]=$defaults[$start];$row[$end]=$defaults[$end];}
        }
        return $row;
    }
}
