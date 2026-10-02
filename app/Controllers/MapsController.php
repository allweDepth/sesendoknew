<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

class MapsController extends Controller
{
    private const MANAGER_ROLES = ['admin_opd', 'kepala_opd', 'pa_kpa'];
    private const MAX_FILE_BYTES = 32 * 1024 * 1024;

    public function index(): void
    {
        $config = require __DIR__ . '/../../config/maps.php';
        $user = $this->requireUser();
        $this->view('maps/index', [
            'canManageLayers' => $this->canManageLayers($user),
            'googleMapsApiKey' => $config['google_maps_api_key'],
        ]);
    }

    public function layers(): void
    {
        $this->beginJson();
        try {
            $user = $this->requireUser();
            [$where, $params] = $this->scopeFilter($user);
            $rows = DB::getInstance()->query(
                "SELECT id,nama_layer,original_name,ukuran,kd_wilayah,kd_opd,username_insert,tgl_insert,components_json
                 FROM maps_layers WHERE is_deleted=0{$where} ORDER BY tgl_insert DESC,id DESC",
                $params
            )->fetchAll();
            foreach ($rows as &$row) {
                $row['id'] = (int)$row['id'];
                $row['ukuran'] = (int)$row['ukuran'];
                $row['components'] = json_decode((string)$row['components_json'], true) ?: ['shp'];
                unset($row['components_json']);
            }
            unset($row);
            echo JsonResponse::success('Daftar layer peta', [], [
                'rows' => $rows,
                'can_manage' => $this->canManageLayers($user),
            ]);
        } catch (Throwable $e) {
            echo JsonResponse::error($e->getMessage(), 400);
        }
    }

    public function upload(): void
    {
        $this->beginJson();
        $directory = null;
        try {
            $user = $this->requireUser();
            if (!$this->canManageLayers($user)) {
                throw new RuntimeException('Hanya admin OPD, kepala OPD, atau PA/KPA yang dapat menambahkan layer.');
            }
            $files = $this->normalizeFiles($_FILES['files'] ?? []);
            if (!$files || count($files) > 4) {
                throw new InvalidArgumentException('Pilih satu file .shp dan file pendamping .shx, .dbf, atau .prj bila tersedia.');
            }

            $byExtension = [];
            $baseName = null;
            $totalSize = 0;
            foreach ($files as $file) {
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    throw new InvalidArgumentException('Upload gagal. Pilih file kembali dan pastikan batas ukuran server mencukupi.');
                }
                $name = basename((string)($file['name'] ?? ''));
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($extension, ['shp', 'shx', 'dbf', 'prj'], true) || isset($byExtension[$extension])) {
                    throw new InvalidArgumentException('Hanya satu file untuk setiap komponen .shp, .shx, .dbf, dan .prj yang diterima.');
                }
                $componentBase = strtolower(pathinfo($name, PATHINFO_FILENAME));
                if ($componentBase === '' || ($baseName !== null && $componentBase !== $baseName)) {
                    throw new InvalidArgumentException('Nama file .shp dan file pendamping harus sama, hanya berbeda ekstensi.');
                }
                $baseName = $componentBase;
                $size = (int)($file['size'] ?? 0);
                if ($size < 1 || $size > self::MAX_FILE_BYTES) {
                    throw new InvalidArgumentException('Setiap file shapefile harus berukuran maksimal 32 MB.');
                }
                $totalSize += $size;
                $byExtension[$extension] = $file;
            }
            if (!isset($byExtension['shp'])) {
                throw new InvalidArgumentException('File .shp wajib dipilih.');
            }
            if ($totalSize > 96 * 1024 * 1024) {
                throw new InvalidArgumentException('Ukuran seluruh komponen shapefile maksimal 96 MB.');
            }

            $scope = $this->requireOpdScope($user);
            $scopeDirectory = preg_replace('/[^A-Za-z0-9._-]/', '_', $scope['kd_wilayah'] . '-' . $scope['kd_opd']);
            $relativeDirectory = 'storage/uploads/maps/' . $scopeDirectory . '/' . bin2hex(random_bytes(16));
            $directory = dirname(__DIR__, 2) . '/' . $relativeDirectory;
            if (!mkdir($directory, 0770, true) && !is_dir($directory)) {
                throw new RuntimeException('Folder penyimpanan layer peta tidak dapat dibuat.');
            }

            foreach ($byExtension as $extension => $file) {
                if (!move_uploaded_file((string)$file['tmp_name'], $directory . '/layer.' . $extension)) {
                    throw new RuntimeException('File ' . $extension . ' gagal disimpan.');
                }
            }

            $displayName = trim((string)($_POST['nama_layer'] ?? ''));
            if ($displayName === '') $displayName = pathinfo((string)$byExtension['shp']['name'], PATHINFO_FILENAME);
            $displayName = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $displayName) ?? '');
            $nameLength = function_exists('mb_strlen') ? mb_strlen($displayName) : strlen($displayName);
            if ($displayName === '' || $nameLength > 160) {
                throw new InvalidArgumentException('Nama layer harus berisi 1–160 karakter.');
            }

            $db = DB::getInstance();
            $db->insert('maps_layers', [
                'nama_layer' => $displayName,
                'original_name' => basename((string)$byExtension['shp']['name']),
                'storage_dir' => $relativeDirectory,
                'components_json' => json_encode(array_keys($byExtension), JSON_THROW_ON_ERROR),
                'ukuran' => $totalSize,
                'kd_wilayah' => $scope['kd_wilayah'],
                'kd_opd' => $scope['kd_opd'],
                'username_insert' => (string)($user['username'] ?? 'user'),
                'user_id' => (int)$user['id'],
                'tgl_insert' => date('Y-m-d H:i:s'),
                'is_deleted' => 0,
            ]);
            echo JsonResponse::success('Layer berhasil disimpan untuk OPD Anda.', [], ['id' => (int)$db->lastInsertId()]);
        } catch (Throwable $e) {
            if ($directory !== null && is_dir($directory)) {
                foreach (glob($directory . '/*') ?: [] as $path) {
                    if (is_file($path)) unlink($path);
                }
                rmdir($directory);
            }
            echo JsonResponse::error($e->getMessage(), 400);
        }
    }

    public function file(): void
    {
        $user = $this->requireUser();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $part = strtolower((string)($_GET['part'] ?? ''));
        if (!$id || !in_array($part, ['shp', 'shx', 'dbf', 'prj'], true)) {
            http_response_code(400);
            exit('Permintaan file peta tidak valid.');
        }

        [$where, $params] = $this->scopeFilter($user, 'm.');
        $row = DB::getInstance()->query(
            "SELECT m.storage_dir,m.components_json,m.original_name
             FROM maps_layers m WHERE m.id=? AND m.is_deleted=0{$where} LIMIT 1",
            [$id, ...$params]
        )->fetch();
        $components = $row ? (json_decode((string)$row['components_json'], true) ?: []) : [];
        if (!$row || !in_array($part, $components, true)) {
            http_response_code(404);
            exit('Komponen shapefile tidak ditemukan.');
        }

        $root = realpath(dirname(__DIR__, 2) . '/storage/uploads/maps');
        $directory = realpath(dirname(__DIR__, 2) . '/' . $row['storage_dir']);
        $file = $directory ? realpath($directory . '/layer.' . $part) : false;
        if (!$root || !$directory || !$file || !str_starts_with($directory, $root . DIRECTORY_SEPARATOR)
            || !str_starts_with($file, $directory . DIRECTORY_SEPARATOR) || !is_file($file)) {
            http_response_code(404);
            exit('File shapefile tidak tersedia.');
        }

        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . rawurlencode((string)$row['original_name']) . '"');
        readfile($file);
        exit;
    }

    public function delete(): void
    {
        $this->beginJson();
        try {
            $user = $this->requireUser();
            if (!$this->canManageLayers($user)) {
                throw new RuntimeException('Role Anda tidak dapat menghapus layer peta.');
            }
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) throw new InvalidArgumentException('Layer peta tidak valid.');
            [$where, $params] = $this->scopeFilter($user);
            $db = DB::getInstance();
            $exists = $db->query("SELECT id FROM maps_layers WHERE id=? AND is_deleted=0{$where} LIMIT 1", [$id, ...$params])->fetch();
            if (!$exists) throw new RuntimeException('Layer tidak ditemukan dalam wilayah/OPD Anda.');
            $db->update('maps_layers', ['is_deleted' => 1], 'WHERE id=?', [$id]);
            echo JsonResponse::success('Layer berhasil dihapus dari peta.');
        } catch (Throwable $e) {
            echo JsonResponse::error($e->getMessage(), 400);
        }
    }

    private function beginJson(): void
    {
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
    }

    private function requireUser(): array
    {
        if (!Auth::check()) throw new RuntimeException('Sesi login tidak valid.');
        return Auth::user();
    }

    private function canManageLayers(array $user): bool
    {
        return in_array((string)($user['type_user'] ?? ''), self::MANAGER_ROLES, true)
            && !empty($user['kd_wilayah'])
            && !empty($user['kd_opd'])
            && (string)$user['kd_opd'] !== '0';
    }

    private function requireOpdScope(array $user): array
    {
        $wilayah = trim((string)($user['kd_wilayah'] ?? ''));
        $opd = trim((string)($user['kd_opd'] ?? ''));
        if ($wilayah === '' || $opd === '' || $opd === '0') {
            throw new RuntimeException('Pilih atau lengkapi wilayah dan OPD sebelum mengelola layer peta.');
        }
        return ['kd_wilayah' => $wilayah, 'kd_opd' => $opd];
    }

    private function scopeFilter(array $user, string $alias = ''): array
    {
        $prefix = $alias !== '' ? $alias : '';
        $where = '';
        $params = [];
        $role = (string)($user['type_user'] ?? '');
        if ($role === 'super_admin') {
            foreach (['kd_wilayah' => 'scope_kd_wilayah', 'kd_opd' => 'scope_kd_opd'] as $column => $sessionKey) {
                if (!empty($_SESSION[$sessionKey])) {
                    $where .= " AND {$prefix}{$column}=?";
                    $params[] = (string)$_SESSION[$sessionKey];
                }
            }
        } elseif (in_array($role, ['admin_wilayah', 'tapd'], true)) {
            $wilayah = trim((string)($user['kd_wilayah'] ?? ''));
            if ($wilayah === '') throw new RuntimeException('Wilayah pengguna belum ditentukan.');
            $where .= " AND {$prefix}kd_wilayah=?";
            $params[] = $wilayah;
            if (!empty($_SESSION['scope_kd_opd'])) {
                $where .= " AND {$prefix}kd_opd=?";
                $params[] = (string)$_SESSION['scope_kd_opd'];
            }
        } else {
            $scope = $this->requireOpdScope($user);
            $where .= " AND {$prefix}kd_wilayah=? AND {$prefix}kd_opd=?";
            $params[] = $scope['kd_wilayah'];
            $params[] = $scope['kd_opd'];
        }
        return [$where, $params];
    }

    private function normalizeFiles(array $upload): array
    {
        if (!isset($upload['name']) || !is_array($upload['name'])) return [];
        $files = [];
        foreach ($upload['name'] as $index => $name) {
            if ((int)($upload['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $files[] = [
                'name' => (string)$name,
                'tmp_name' => (string)($upload['tmp_name'][$index] ?? ''),
                'size' => (int)($upload['size'][$index] ?? 0),
                'error' => (int)($upload['error'][$index] ?? UPLOAD_ERR_NO_FILE),
            ];
        }
        return $files;
    }
}
