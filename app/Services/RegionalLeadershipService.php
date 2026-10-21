<?php

class RegionalLeadershipService
{
    public static function titles(string $code, string $region): array
    {
        $parts = explode('.', $code);
        $head = count($parts) === 1 ? 'Gubernur' : ((int)($parts[1] ?? 0) >= 71 || preg_match('/^KOTA\b/i', $region) ? 'Walikota' : 'Bupati');
        $region = trim($region);
        return ['kepala' => trim($head . ' ' . $region), 'wakil' => trim('Wakil ' . $head . ' ' . $region), 'sekda' => trim('Sekretaris Daerah ' . $region)];
    }

    public static function legacyRole(array $row): ?string
    {
        if (!in_array($row['kd_opd'] ?? '', ['BUPATI', 'GUBERNUR', 'WALIKOTA', 'SETDA'], true)) return null;
        $title = trim($row['nama_jabatan'] ?? '');
        if (preg_match('/^Sekretaris Daerah\b/i', $title)) return 'sekda';
        if (preg_match('/^(Wakil\s+)?(Gubernur|Wali\s*kota|Bupati)\b/i', $title, $match)) return empty($match[1]) ? 'kepala' : 'wakil';
        return null;
    }

    // Virtual positions exist even before any official is entered.
    public static function merge(array $rows, array $officials, array $titles, string $opd, bool $canManage): array
    {
        $saved = array_column($officials, null, 'jenis_pimpinan');
        $aliases = [];
        $local = [];
        foreach ($rows as $row) {
            $role = self::legacyRole($row);
            if ($role) {
                $aliases[$row['struktur_key']] = 'wilayah:' . $role;
                // Keep real legacy officials visible; migration sample officials are placeholders.
                if (!isset($saved[$role]) && ($row['username_insert'] ?? '') !== 'migration_phase55') $saved[$role] = $row;
            } elseif ($row['kd_opd'] === $opd) {
                $local[] = $row;
            }
        }
        $result = [];
        foreach ($titles as $role => $title) {
            $official = $saved[$role] ?? [];
            $result[] = array_merge($official, [
                'id' => 'wilayah-' . $role, 'jenis_pimpinan' => $role,
                'nama_jabatan' => $title, 'nama_jabatan_display' => $title,
                'nama_pegawai' => $official['nama_pejabat'] ?? $official['nama_pegawai'] ?? '',
                'struktur_key' => 'wilayah:' . $role,
                'parent_key' => $role === 'kepala' ? 'wilayah:0' : 'wilayah:kepala',
                'can_manage' => $canManage, 'eselon' => $role === 'sekda' ? 'II.a' : '',
            ]);
        }
        foreach ($local as $row) {
            $row['parent_key'] = $aliases[$row['parent_key']] ?? $row['parent_key'];
            if (empty($row['parent_id'])) $row['parent_key'] = 'wilayah:sekda';
            $row['can_manage'] = false; // Controller supplies the local OPD permission.
            $result[] = $row;
        }
        return $result;
    }

    private static function upload(string $field, array $user): ?string
    {
        $file = $_FILES[$field] ?? [];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Unggah ' . $field . ' gagal');
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new InvalidArgumentException('Ukuran file maksimal 5 MB');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $types = $field === 'foto' ? ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'] : ['application/pdf'=>'pdf'];
        if (!isset($types[$mime])) throw new InvalidArgumentException($field === 'foto' ? 'Foto harus JPG, PNG, atau WebP' : 'File SK harus PDF');
        $relative = 'uploads/pimpinan-wilayah/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $user['kd_wilayah']) . '/' . (int)$user['tahun'];
        $dir = dirname(__DIR__, 2) . '/public/' . $relative;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new RuntimeException('Folder unggahan gagal dibuat');
        $path = $relative . '/' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
        if (!move_uploaded_file($file['tmp_name'], dirname(__DIR__, 2) . '/public/' . $path)) throw new RuntimeException('File gagal disimpan');
        return $path;
    }

    public static function save($db, array $user, array $input): void
    {
        if (($user['type_user'] ?? '') !== 'admin_wilayah') throw new RuntimeException('Data pimpinan hanya dapat diisi role daerah (Admin Wilayah)');
        $role = $input['jenis_pimpinan'] ?? '';
        if (!in_array($role, ['kepala', 'wakil', 'sekda'], true)) throw new InvalidArgumentException('Jenis pimpinan tidak valid');
        $data = [];
        foreach (['nama_pejabat' => 255, 'nomor_sk_pengangkatan' => 150] as $field => $limit) {
            $data[$field] = trim((string)($input[$field] ?? ''));
            if ($data[$field] === '' || mb_strlen($data[$field]) > $limit) throw new InvalidArgumentException('Nama dan nomor SK wajib diisi sesuai batas panjang');
        }
        foreach (['tanggal_sk_pengangkatan', 'tmt_jabatan'] as $field) {
            $value = (string)($input[$field] ?? '');
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Tanggal SK dan TMT harus tanggal yang valid');
            $data[$field] = $value;
        }
        $files = [];
        try {
            foreach (['file_sk', 'foto'] as $field) {
                $path = self::upload($field, $user);
                if ($path !== null) { $data[$field] = $path; $files[] = $path; }
            }
            $data['username_update'] = $user['username'] ?? 'system';
            $columns = array_keys($data);
            $updates = implode(',', array_map(static fn($col) => "$col=VALUES($col)", $columns));
            $columns = array_merge(['kd_wilayah','tahun','jenis_pimpinan','username_insert'], $columns);
            $db->query('INSERT INTO pimpinan_wilayah_neo (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ') ON DUPLICATE KEY UPDATE ' . $updates . ',tgl_update=NOW()', array_merge([$user['kd_wilayah'], $user['tahun'], $role, $user['username'] ?? 'system'], array_values($data)));
        } catch (Throwable $e) {
            foreach ($files as $file) @unlink(dirname(__DIR__, 2) . '/public/' . $file);
            throw $e;
        }
    }
}
