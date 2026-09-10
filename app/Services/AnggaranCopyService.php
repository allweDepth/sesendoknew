<?php
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/PaguLimitService.php';

/*
|--------------------------------------------------------------------------
| ANGGARAN COPY SERVICE
|--------------------------------------------------------------------------
| Menurunkan dokumen perencanaan/penganggaran secara berjenjang:
|
|   RKPD > Renja > RKA > DPA > RKPD-P > Renja-P > RKA-P > DPPA
|
| Sifat operasi adalah SINKRONISASI (upsert), bukan sekadar salin sekali:
|
|   - baris sumber yang telah disetujui TAPD dan belum ada di tahap tujuan
|     -> INSERT
|   - baris sumber yang sudah pernah diturunkan
|     -> UPDATE (nilai tahap tujuan diganti mengikuti sumber yang disetujui)
|   - baris tahap tujuan yang sumbernya sudah tidak ada / tidak lagi disetujui
|     -> dinolkan (dokumen perubahan) atau dihapus lunak (dokumen murni)
|
| Seluruh operasi terkurung pada scope yang sama: tahun, kd_wilayah, kd_opd.
|--------------------------------------------------------------------------
*/

class AnggaranCopyService
{
    private DB $db;
    private array $user;

    private const TABLES = [
        'rkpd'    => 'rkpd_neo',
        'renja'   => 'renja_neo',
        'rka'     => 'rka_neo',
        'dpa'     => 'dpa_neo',
        'rkpd_p'  => 'rkpd_p_neo',
        'renja_p' => 'renja_p_neo',
        'rka_p'   => 'rka_p_neo',
        'dppa'    => 'dppa_neo',
    ];

    private const TRANSITIONS = [
        'rkpd:renja', 'renja:rkpd', 'renja:rka', 'rka:dpa',
        'rkpd:rkpd_p', 'renja:renja_p', 'rka:rka_p',
        'renja_p:rka_p', 'rka_p:dppa', 'dpa:dppa',
    ];

    /** Dokumen perubahan: baris yatim dinolkan, bukan dihapus. */
    private const CHANGE_DOCS = ['rkpd_p', 'renja_p', 'rka_p', 'dppa'];

    public function __construct(array $user = [])
    {
        $this->db = DB::getInstance();
        $this->user = $user;
    }

    public static function table(string $logical): string
    {
        if (!isset(self::TABLES[$logical])) throw new InvalidArgumentException('Tahap anggaran tidak valid');
        return self::TABLES[$logical];
    }

    /*
    |--------------------------------------------------------------------------
    | Sinkronisasi tahap sumber -> tahap tujuan
    |--------------------------------------------------------------------------
    */
    public function copy(string $from, string $to, int $tahun, ?int $sourceId = null): array
    {
        if (!in_array($this->user['type_user'] ?? '', ['super_admin', 'admin_wilayah', 'admin_opd', 'kepala_opd', 'pa_kpa'], true)) {
            throw new RuntimeException('Tidak memiliki hak untuk memproses dokumen');
        }
        if (!in_array("$from:$to", self::TRANSITIONS, true)) throw new InvalidArgumentException('Urutan dokumen tidak diizinkan');
        if ($tahun < 2000 || $tahun > 2100) throw new InvalidArgumentException('Tahun tidak valid');

        $sourceTable = self::table($from);
        $targetTable = self::table($to);
        $wilayah = (string)($this->user['kd_wilayah'] ?? '');
        $opd     = (string)($this->user['kd_opd'] ?? '');
        if ($wilayah === '') throw new RuntimeException('Scope wilayah pengguna tidak lengkap');
        if ($opd === '' || $opd === '0') {
            throw new RuntimeException('Pilih satu OPD terlebih dahulu sebelum menurunkan dokumen; penurunan lintas OPD tidak diizinkan.');
        }

        $where  = 'tahun = ? AND kd_wilayah = ? AND kd_opd = ? AND is_deleted = 0 AND setujui = 1';
        $params = [$tahun, $wilayah, $opd];
        if ($sourceId) { $where .= ' AND id = ?'; $params[] = $sourceId; }

        $this->db->begin();
        try {
            $rows = $this->db->query("SELECT * FROM `$sourceTable` WHERE $where FOR UPDATE", $params)->fetchAll();

            // Renja -> RKPD diringkas per sub kegiatan (RKPD hanya menyimpan pagu agregat).
            if ($from === 'renja' && $to === 'rkpd') {
                $grouped = [];
                foreach ($rows as $row) {
                    $code = (string)$row['kd_sub_keg'];
                    if (!isset($grouped[$code])) {
                        $row['pagu'] = 0;
                        $row['indikator'] = $row['uraian'] ?? '';
                        $row['target'] = $row['volume'] ?? 0;
                        $grouped[$code] = $row;
                    }
                    $grouped[$code]['pagu'] += (float)($row['jumlah'] ?? 0);
                }
                $rows = array_values($grouped);
            }

            if (!$rows) throw new RuntimeException('Tidak ada dokumen sumber yang telah disetujui');

            $columns = array_column($this->db->query("SHOW COLUMNS FROM `$targetTable`")->fetchAll(), 'Field');
            $codes = array_values(array_unique(array_map(fn($r) => (string)$r['kd_sub_keg'], $rows)));

            $this->assertTargetOpen($targetTable, $tahun, $wilayah, $opd, $codes);
            $this->assertSinglePath($to, $targetTable, $sourceTable, $tahun, $wilayah, $opd, $codes);

            $copied = 0; $updated = 0; $unchanged = 0;
            $keptIds = [];

            foreach ($rows as $row) {
                [$linkWhere, $linkParams] = $this->linkKey($to, $sourceTable, $row);
                $existing = $this->db->query(
                    "SELECT * FROM `$targetTable` WHERE $linkWhere AND tahun=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0 LIMIT 1 FOR UPDATE",
                    array_merge($linkParams, [$tahun, $wilayah, $opd])
                )->fetch();

                if ($existing) {
                    $keptIds[] = (int)$existing['id'];
                    $payload = $this->mapRow($from, $to, $sourceTable, $row, $columns, 'update');
                    if (!$this->hasDiff($existing, $payload)) { $unchanged++; continue; }
                    (new PaguLimitService($this->user))->validate($targetTable, array_merge($existing, $payload), (int)$existing['id']);
                    $this->db->update($targetTable, $payload, 'WHERE id=?', [(int)$existing['id']]);
                    $updated++;
                } else {
                    $payload = $this->mapRow($from, $to, $sourceTable, $row, $columns, 'insert');
                    (new PaguLimitService($this->user))->validate($targetTable, $payload);
                    $newId = $this->db->insert($targetTable, $payload);
                    if (is_numeric($newId)) $keptIds[] = (int)$newId;
                    $copied++;
                }
            }

            // Baris tahap tujuan yang sumbernya sudah tidak disetujui / sudah dihapus.
            $removed = $sourceId ? 0 : $this->sweepOrphans($to, $targetTable, $sourceTable, $tahun, $wilayah, $opd, $keptIds, $columns);

            $this->db->insert('anggaran_workflow_log', [
                'source_table' => $sourceTable,
                'target_table' => $targetTable,
                'tahun'        => $tahun,
                'kd_wilayah'   => $wilayah,
                'kd_opd'       => $opd,
                'jumlah_data'  => $copied + $updated,
                'username'     => $this->user['username'] ?? 'system',
                'tgl_copy'     => date('Y-m-d H:i:s'),
            ]);

            $this->db->commit();
            return [
                'copied'    => $copied,
                'updated'   => $updated,
                'removed'   => $removed,
                'unchanged' => $unchanged,
                'skipped'   => $unchanged, // kompatibilitas pemanggil lama
                'from'      => $from,
                'to'        => $to,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Kunci penghubung baris sumber -> baris tujuan
    |--------------------------------------------------------------------------
    */
    private function linkKey(string $to, string $sourceTable, array $row): array
    {
        if ($to === 'rkpd')   return ['kd_sub_keg=?', [(string)$row['kd_sub_keg']]];
        if ($to === 'rkpd_p') return ['source_rkpd_id=?', [(int)$row['id']]];
        return ['source_table=? AND source_id=?', [$sourceTable, (int)$row['id']]];
    }

    /*
    |--------------------------------------------------------------------------
    | Tahap tujuan wajib terbuka (belum disetujui/dikunci)
    |--------------------------------------------------------------------------
    */
    private function assertTargetOpen(string $targetTable, int $tahun, string $wilayah, string $opd, array $codes): void
    {
        if (!$codes) return;
        $holders = implode(',', array_fill(0, count($codes), '?'));
        $locked = $this->db->query(
            "SELECT DISTINCT kd_sub_keg FROM `$targetTable`
              WHERE tahun=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0
                AND (COALESCE(setujui,0)=1 OR COALESCE(kunci,0)=1)
                AND kd_sub_keg IN ($holders)",
            array_merge([$tahun, $wilayah, $opd], $codes)
        )->fetchAll();
        if ($locked) {
            $list = implode(', ', array_column($locked, 'kd_sub_keg'));
            throw new RuntimeException('Dokumen tujuan pada sub kegiatan ' . $list . ' sudah disetujui dan dikunci. Buka persetujuan terlebih dahulu sebelum menurunkan ulang dari dokumen sumber.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DPPA hanya boleh diisi dari satu jalur (DPA atau RKA-P), tidak keduanya
    |--------------------------------------------------------------------------
    */
    private function assertSinglePath(string $to, string $targetTable, string $sourceTable, int $tahun, string $wilayah, string $opd, array $codes): void
    {
        if ($to !== 'dppa' || !$codes) return;
        $holders = implode(',', array_fill(0, count($codes), '?'));
        $other = $this->db->query(
            "SELECT DISTINCT source_table FROM `$targetTable`
              WHERE tahun=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0
                AND source_table IS NOT NULL AND source_table<>'' AND source_table<>?
                AND kd_sub_keg IN ($holders)",
            array_merge([$tahun, $wilayah, $opd, $sourceTable], $codes)
        )->fetch();
        if ($other) {
            throw new RuntimeException('DPPA pada sub kegiatan ini sudah diturunkan dari ' . strtoupper(str_replace('_neo', '', (string)$other['source_table'])) . '. Gunakan satu jalur saja agar rincian tidak berganda.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Baris yatim: sumbernya sudah tidak ada atau persetujuannya dicabut
    |--------------------------------------------------------------------------
    */
    private function sweepOrphans(string $to, string $targetTable, string $sourceTable, int $tahun, string $wilayah, string $opd, array $keptIds, array $columns): int
    {
        if ($to === 'rkpd') return 0; // RKPD tidak menyimpan jejak baris sumber

        $sql = "SELECT id FROM `$targetTable`
                 WHERE tahun=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0
                   AND COALESCE(setujui,0)=0 AND COALESCE(kunci,0)=0";
        $params = [$tahun, $wilayah, $opd];

        if ($to === 'rkpd_p') {
            $sql .= ' AND COALESCE(source_rkpd_id,0)>0';
        } else {
            $sql .= ' AND source_table=? AND COALESCE(source_id,0)>0';
            $params[] = $sourceTable;
        }
        if ($keptIds) {
            $sql .= ' AND id NOT IN (' . implode(',', array_fill(0, count($keptIds), '?')) . ')';
            $params = array_merge($params, $keptIds);
        }

        $orphans = $this->db->query($sql . ' FOR UPDATE', $params)->fetchAll();
        if (!$orphans) return 0;

        $now = date('Y-m-d H:i:s');
        $userName = $this->user['username'] ?? 'system';
        $isChange = in_array($to, self::CHANGE_DOCS, true) && in_array('status_perubahan', $columns, true);

        foreach ($orphans as $orphan) {
            if ($isChange) {
                $payload = ['status_perubahan' => 'hapus', 'jumlah' => 0];
                if (in_array('volume', $columns, true)) $payload['volume'] = 0;
                if (in_array('pagu', $columns, true))   $payload['pagu'] = 0;
                if (in_array('target', $columns, true)) $payload['target'] = 0;
                foreach (['vol_1', 'vol_2', 'vol_3', 'vol_4', 'vol_5'] as $field) {
                    if (in_array($field, $columns, true)) $payload[$field] = 0;
                }
            } else {
                $payload = ['is_deleted' => 1];
            }
            if (in_array('tgl_update', $columns, true))      $payload['tgl_update'] = $now;
            if (in_array('username_update', $columns, true)) $payload['username_update'] = $userName;
            $this->db->update($targetTable, $payload, 'WHERE id=?', [(int)$orphan['id']]);
        }
        return count($orphans);
    }

    /*
    |--------------------------------------------------------------------------
    | Apakah baris tujuan sudah identik dengan hasil pemetaan sumber
    |--------------------------------------------------------------------------
    */
    private function hasDiff(array $existing, array $payload): bool
    {
        foreach ($payload as $field => $value) {
            if (in_array($field, ['tgl_update', 'username_update'], true)) continue;
            if (!array_key_exists($field, $existing)) return true;
            $old = $existing[$field]; $new = $value;
            if (is_numeric($old) && is_numeric($new)) {
                if (abs((float)$old - (float)$new) > 0.000001) return true;
                continue;
            }
            if ((string)$old !== (string)$new) return true;
        }
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Pemetaan baris sumber -> payload tahap tujuan
    |--------------------------------------------------------------------------
    */
    private function mapRow(string $from, string $to, string $sourceTable, array $row, array $columns, string $mode = 'insert'): array
    {
        $payload = ['source_table' => $sourceTable, 'source_id' => (int)$row['id']];
        foreach ($columns as $column) {
            if (in_array($column, ['id', 'source_table', 'source_id', 'tgl_insert', 'username_insert', 'tgl_update', 'username_update'], true)) continue;
            if (array_key_exists($column, $row)) $payload[$column] = $row[$column];
        }

        if ($from === 'rkpd' && $to === 'renja') $payload += [
            'kd_wilayah' => $row['kd_wilayah'], 'kd_opd' => $row['kd_opd'], 'tahun' => $row['tahun'],
            'kd_sub_keg' => $row['kd_sub_keg'], 'uraian' => $row['indikator'] ?: 'Rincian dari RKPD',
            'volume' => $row['target'] ?: 1, 'jumlah' => $row['pagu'], 'harga_satuan' => $row['pagu'],
            'sumber_dana_id' => $row['sumber_dana_id'], 'keterangan' => $row['keterangan'],
        ];

        if ($from === 'renja' && $to === 'rkpd') $payload += [
            'kd_wilayah' => $row['kd_wilayah'], 'kd_opd' => $row['kd_opd'], 'tahun' => $row['tahun'],
            'kd_sub_keg' => $row['kd_sub_keg'], 'indikator' => $row['indikator'] ?? $row['uraian'] ?? '',
            'target' => $row['target'] ?? $row['volume'] ?? 0, 'pagu' => $row['pagu'] ?? $row['jumlah'] ?? 0,
            'sumber_dana_id' => $row['sumber_dana_id'] ?? null, 'keterangan' => $row['keterangan'] ?? null,
        ];

        // Dokumen perubahan menyimpan nilai awal sebagai pembanding.
        if (str_ends_with($to, '_p') || $to === 'dppa') {
            foreach (['jenis_standar_harga', 'id_standar_harga', 'komponen', 'spesifikasi', 'tkdn', 'pajak', 'harga_satuan', 'volume', 'jumlah'] as $field) {
                if (array_key_exists($field, $row) && in_array($field . '_awal', $columns, true)) $payload[$field . '_awal'] = $row[$field];
            }
            if (in_array('status_perubahan', $columns, true)) $payload['status_perubahan'] = 'awal';
        }

        if ($to === 'rkpd_p') {
            unset($payload['source_table'], $payload['source_id']);
            $payload['source_rkpd_id'] = (int)$row['id'];
            $payload['target_awal'] = $row['target'];
            $payload['pagu_awal']   = $row['pagu'];
            $payload['status_perubahan'] = 'awal';
        }

        // Hasil turunan selalu kembali ke status terbuka; persetujuan tahap
        // tujuan adalah keputusan tersendiri.
        $payload['setujui'] = 0;
        $payload['kunci'] = 0;
        $payload['disable'] = 0;
        $payload['is_deleted'] = 0;

        if ($mode === 'insert') {
            $payload['tgl_insert'] = date('Y-m-d H:i:s');
            $payload['username_insert'] = $this->user['username'] ?? 'system';
        } else {
            $payload['tgl_update'] = date('Y-m-d H:i:s');
            $payload['username_update'] = $this->user['username'] ?? 'system';
        }

        return array_intersect_key($payload, array_flip($columns));
    }
}
