<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

class MapsController extends Controller
{
    private const MANAGER_ROLES = ['admin_opd', 'kepala_opd', 'pa_kpa'];
    private const MAX_FILE_BYTES = 32 * 1024 * 1024;
    private const MAX_LAYER_BYTES = 96 * 1024 * 1024;
    private const SHAPEFILE_COMPONENTS = [
        'shp', 'shx', 'dbf', 'prj', 'cpg', 'qix', 'sbn', 'sbx', 'ain', 'aih',
        'atx', 'ixs', 'mxs', 'shp.xml', 'shx.xml', 'dbf.xml',
    ];

    public function index(): void
    {
        $this->renderPage('map');
    }

    public function layersPage(): void
    {
        $this->renderPage('layers');
    }

    public function uploadPage(): void
    {
        $this->renderPage('upload');
    }

    private function renderPage(string $mode): void
    {
        $config = require __DIR__ . '/../../config/maps.php';
        $user = $this->requireUser();
        $this->view('maps/index', [
            'canManageLayers' => $this->canManageLayers($user),
            'googleMapsApiKey' => $config['google_maps_api_key'],
            'mode' => $mode,
        ]);
    }

    public function layers(): void
    {
        $this->beginJson();
        try {
            $user = $this->requireUser();
            [$where, $params] = $this->scopeFilter($user);
            $rows = DB::getInstance()->query(
                "SELECT id,nama_layer,original_name,ukuran,kd_wilayah,kd_opd,username_insert,tgl_insert,components_json,style_json
                 FROM maps_layers WHERE is_deleted=0{$where} ORDER BY tgl_insert DESC,id DESC",
                $params
            )->fetchAll();
            foreach ($rows as &$row) {
                $row['id'] = (int)$row['id'];
                $row['ukuran'] = (int)$row['ukuran'];
                $row['components'] = json_decode((string)$row['components_json'], true) ?: ['shp'];
                unset($row['components_json']);
                $row['style'] = json_decode((string)($row['style_json'] ?? ''), true) ?: [];
                unset($row['style_json']);
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

    public function saveStyle(): void
    {
        $this->beginJson();
        try {
            $user = $this->requireUser();
            if (!$this->canManageLayers($user)) {
                throw new RuntimeException('Role Anda tidak dapat mengubah simbologi layer.');
            }
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $style = json_decode((string)($_POST['style'] ?? ''), true);
            if (!$id || !is_array($style)) throw new InvalidArgumentException('Pengaturan simbologi tidak valid.');
            $validated = [
                'renderer' => ($style['renderer'] ?? 'simple') === 'categorized' ? 'categorized' : 'simple',
                'color' => $this->validColor($style['color'] ?? null),
                'fill_color' => $this->validColor($style['fill_color'] ?? null),
                'fill_opacity' => $this->boundedNumber($style['fill_opacity'] ?? null, 0, 1),
                'weight' => $this->boundedNumber($style['weight'] ?? null, 0.5, 10),
                'point_radius' => $this->boundedNumber($style['point_radius'] ?? null, 2, 16),
                'label_field' => $this->validFieldName($style['label_field'] ?? ''),
                'show_labels' => !empty($style['show_labels']),
                'category_field' => $this->validFieldName($style['category_field'] ?? ''),
                'categories' => $this->validateCategories($style['categories'] ?? []),
            ];
            if ($validated['renderer'] === 'categorized'
                && ($validated['category_field'] === '' || !$validated['categories'])) {
                throw new InvalidArgumentException('Pilih field dan klasifikasikan kategori sebelum menyimpan simbologi categorized.');
            }
            [$where, $params] = $this->scopeFilter($user);
            $db = DB::getInstance();
            $row = $db->query("SELECT id FROM maps_layers WHERE id=? AND is_deleted=0{$where} LIMIT 1", [$id, ...$params])->fetch();
            if (!$row) throw new RuntimeException('Layer tidak ditemukan dalam wilayah/OPD Anda.');
            $db->update('maps_layers', ['style_json' => json_encode($validated, JSON_THROW_ON_ERROR)], 'WHERE id=?', [$id]);
            echo JsonResponse::success('Simbologi layer berhasil disimpan.');
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
            if (count($files) !== 1 || strtolower(pathinfo($files[0]['name'], PATHINFO_EXTENSION)) !== 'zip') {
                throw new InvalidArgumentException('Unggah satu paket .zip berisi file SHP dan seluruh berkas pendampingnya.');
            }
            $archiveUpload = $files[0];
            if (($archiveUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || $archiveUpload['size'] < 1 || $archiveUpload['size'] > self::MAX_LAYER_BYTES) {
                throw new InvalidArgumentException('Paket ZIP gagal diunggah atau ukurannya melebihi 96 MB.');
            }
            $components = $this->readShapefileArchive($archiveUpload['tmp_name']);
            $totalSize = array_sum(array_map('strlen', $components));

            $scope = $this->requireOpdScope($user);
            $scopeDirectory = preg_replace('/[^A-Za-z0-9._-]/', '_', $scope['kd_wilayah'] . '-' . $scope['kd_opd']);
            $relativeDirectory = 'storage/uploads/maps/' . $scopeDirectory . '/' . bin2hex(random_bytes(16));
            $directory = dirname(__DIR__, 2) . '/' . $relativeDirectory;
            if (!mkdir($directory, 0770, true) && !is_dir($directory)) {
                throw new RuntimeException('Folder penyimpanan layer peta tidak dapat dibuat.');
            }

            foreach ($components as $extension => $contents) {
                if (file_put_contents($directory . '/layer.' . $extension, $contents, LOCK_EX) !== strlen($contents)) {
                    throw new RuntimeException('File ' . $extension . ' gagal disimpan.');
                }
            }

            $displayName = trim((string)($_POST['nama_layer'] ?? ''));
            if ($displayName === '') $displayName = pathinfo((string)$archiveUpload['name'], PATHINFO_FILENAME);
            $displayName = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $displayName) ?? '');
            $nameLength = function_exists('mb_strlen') ? mb_strlen($displayName) : strlen($displayName);
            if ($displayName === '' || $nameLength > 160) {
                throw new InvalidArgumentException('Nama layer harus berisi 1–160 karakter.');
            }

            $db = DB::getInstance();
            $db->insert('maps_layers', [
                'nama_layer' => $displayName,
                'original_name' => basename((string)$archiveUpload['name']),
                'storage_dir' => $relativeDirectory,
                'components_json' => json_encode(array_keys($components), JSON_THROW_ON_ERROR),
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
        if (!$id || !in_array($part, self::SHAPEFILE_COMPONENTS, true)) {
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

    private function readShapefileArchive(string $path): array
    {
        $archive = new ZipArchive();
        if ($archive->open($path) !== true) throw new InvalidArgumentException('Paket ZIP tidak dapat dibuka.');
        try {
            if ($archive->numFiles < 1 || $archive->numFiles > 64) {
                throw new InvalidArgumentException('Paket ZIP harus berisi maksimal 64 berkas.');
            }
            $shapefiles = [];
            $entries = [];
            $expandedBytes = 0;
            foreach (range(0, $archive->numFiles - 1) as $index) {
                $stat = $archive->statIndex($index);
                if (!$stat || str_ends_with((string)$stat['name'], '/')) continue;
                $name = basename(str_replace('\\', '/', (string)$stat['name']));
                $lowerName = strtolower($name);
                $extension = null;
                foreach (self::SHAPEFILE_COMPONENTS as $candidate) {
                    if (str_ends_with($lowerName, '.' . $candidate)) {
                        $extension = $candidate;
                        break;
                    }
                }
                if ($extension === null) continue;
                $baseName = substr($lowerName, 0, -strlen('.' . $extension));
                if ($baseName === '') throw new InvalidArgumentException('Nama komponen shapefile di dalam ZIP tidak valid.');
                $entries[] = ['index' => $index, 'extension' => $extension, 'base' => $baseName, 'size' => (int)$stat['size']];
                if ($extension === 'shp') $shapefiles[] = $baseName;
                $expandedBytes += (int)$stat['size'];
                if ((int)$stat['size'] > self::MAX_FILE_BYTES || $expandedBytes > self::MAX_LAYER_BYTES) {
                    throw new InvalidArgumentException('Batas komponen adalah 32 MB per file dan 96 MB seluruh shapefile.');
                }
            }
            if (count(array_unique($shapefiles)) !== 1) {
                throw new InvalidArgumentException('Paket ZIP harus berisi tepat satu file .shp.');
            }
            $shapefileBase = $shapefiles[0];
            $components = [];
            foreach ($entries as $entry) {
                if ($entry['base'] !== $shapefileBase) continue;
                if (isset($components[$entry['extension']])) {
                    throw new InvalidArgumentException('Paket ZIP berisi komponen shapefile duplikat.');
                }
                $contents = $archive->getFromIndex($entry['index']);
                if (!is_string($contents) || strlen($contents) !== $entry['size']) {
                    throw new RuntimeException('Berkas shapefile di dalam ZIP gagal dibaca.');
                }
                $components[$entry['extension']] = $contents;
            }
            if (!isset($components['shp'])) throw new InvalidArgumentException('File .shp wajib ada di dalam ZIP.');
            return $components;
        } finally {
            $archive->close();
        }
    }

    private function validateCategories($categories): array
    {
        if (!is_array($categories) || count($categories) > 100) {
            throw new InvalidArgumentException('Kategori harus berupa daftar maksimal 100 nilai.');
        }
        $validated = [];
        $seen = [];
        foreach ($categories as $category) {
            if (!is_array($category) || !array_key_exists('value', $category)) {
                throw new InvalidArgumentException('Format kategori simbologi tidak valid.');
            }
            $value = (string)$category['value'];
            if (strlen($value) > 255 || isset($seen[$value])) {
                throw new InvalidArgumentException('Nilai kategori terlalu panjang atau duplikat.');
            }
            $seen[$value] = true;
            $validated[] = ['value' => $value, 'color' => $this->validColor($category['color'] ?? null)];
        }
        return $validated;
    }

    private function validColor($value): string
    {
        $value = (string)$value;
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) throw new InvalidArgumentException('Warna simbologi tidak valid.');
        return strtolower($value);
    }

    private function boundedNumber($value, float $min, float $max): float
    {
        if (!is_numeric($value)) throw new InvalidArgumentException('Nilai simbologi tidak valid.');
        $number = (float)$value;
        if (!is_finite($number) || $number < $min || $number > $max) throw new InvalidArgumentException('Nilai simbologi berada di luar batas.');
        return $number;
    }

    private function validFieldName($value): string
    {
        $value = (string)$value;
        if ($value !== '' && !preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,10}$/', $value)) {
            throw new InvalidArgumentException('Nama field label tidak valid.');
        }
        return $value;
    }
}
