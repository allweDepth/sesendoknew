<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

class ProfilController extends Controller
{
  public function index()
  {
    if (!Auth::check()) {
      header('Location: ' . app_url('/'));
      exit;
    }

    $this->view('profil/index', [], 'app');
  }

  private function json(callable $callback): void
  {
    header('Content-Type: application/json;charset=UTF-8');
    try {
      if (!Auth::check()) throw new RuntimeException('Sesi login tidak valid');
      echo JsonResponse::success('Berhasil', [], $callback());
    } catch (Throwable $e) {
      echo JsonResponse::error($e->getMessage(), 400);
    }
  }


  public function save(): void
  {
    $this->json(function () {
      $u = $_SESSION['user'];
      $db = DB::getInstance();
      $id = (int)($u['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('User profil tidak valid');

      $nama = trim((string)($_POST['nama'] ?? ''));
      $nip = trim((string)($_POST['nip'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));
      $kontak = trim((string)($_POST['kontak_person'] ?? ''));
      $theme = trim((string)($_POST['theme'] ?? 'auto'));
      $warna = trim((string)($_POST['warna_tbl'] ?? 'non'));
      $ket = trim((string)($_POST['ket'] ?? ''));
      $fontSize = trim((string)($_POST['font_size'] ?? ''));

      $errors = [];
      if ($nama === '') $errors['nama'] = 'Nama lengkap wajib diisi';
      if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email tidak valid';
      if ($nip !== '' && (!preg_match('/^[0-9]{1,18}$/', $nip))) $errors['nip'] = 'NIP maksimal 18 digit angka';
      if (!in_array($theme, ['auto', 'light', 'dark'], true)) $errors['theme'] = 'Theme tidak valid';
      if (!in_array($warna, ['non', 'red', 'green', 'blue', 'orange', 'purple', 'black'], true)) $errors['warna_tbl'] = 'Warna tabel tidak valid';
      if ($fontSize !== '' && (!is_numeric($fontSize) || (float)$fontSize <= 0 || (float)$fontSize > 999.99)) $errors['font_size'] = 'Font size tidak valid';
      if (mb_strlen($ket) > 250) $errors['ket'] = 'Keterangan maksimal 250 karakter';
      if ($errors) {
        http_response_code(422);
        echo JsonResponse::error('Periksa kembali data profil', 422, $errors);
        exit;
      }

      $duplicate = $db->query('SELECT id FROM user_sesendok_biila WHERE email=? AND id<>? LIMIT 1', [$email, $id])->fetch();
      if ($duplicate) {
        http_response_code(422);
        echo JsonResponse::error('Email sudah digunakan user lain', 422, ['email' => 'Email sudah digunakan']);
        exit;
      }

      $data = [
        'nama' => $nama,
        'nip' => $nip === '' ? null : $nip,
        'email' => $email,
        'kontak_person' => $kontak,
        'theme' => $theme,
        'warna_tbl' => $warna,
        'ket' => $ket,
      ];
      if ($fontSize !== '') $data['font_size'] = (float)$fontSize;

      $periodeId = (int)($_POST['periode_id'] ?? 0);
      $tahun = (int)($_POST['tahun'] ?? 0);
      if ($periodeId > 0 || $tahun > 0) {
        $p = $db->query('SELECT id,periode_mulai,periode_selesai FROM periode_rpjmd WHERE id=? AND COALESCE(status_aktif,1)=1', [$periodeId])->fetch();
        if (!$p || $tahun < (int)$p['periode_mulai'] || $tahun > (int)$p['periode_selesai']) {
          http_response_code(422);
          echo JsonResponse::error('Tahun harus berada dalam rentang periode aktif', 422, ['tahun' => 'Tahun tidak sesuai rentang dokumen']);
          exit;
        }
        $regional = in_array($u['type_user'] ?? '', ['super_admin', 'admin_wilayah', 'tapd'], true);
        if (!$regional) {
          $allowed = $db->query('SELECT 1 FROM renstra_neo WHERE periode_id=? AND kd_wilayah=? AND kd_opd=? LIMIT 1', [$periodeId, $u['kd_wilayah'], $u['kd_opd']])->fetchColumn();
          if (!$allowed) throw new RuntimeException('Periode tersebut bukan Renstra aktif OPD pengguna');
        }
        $data['tahun'] = $tahun;
      }

      $db->update('user_sesendok_biila', $data, 'WHERE id=?', [$id]);
      $saved = $db->query('SELECT id,nama,nip,email,kontak_person,font_size,theme,warna_tbl,ket,tahun FROM user_sesendok_biila WHERE id=? LIMIT 1', [$id])->fetch();
      if (!$saved) throw new RuntimeException('Profil gagal diverifikasi setelah penyimpanan');

      foreach ($saved as $key => $value) $_SESSION['user'][$key] = $value;
      return $saved;
    });
  }

  public function photo(): void
  {
    if (!Auth::check()) {
      http_response_code(401);
      exit;
    }
    $photo = (string)($_SESSION['user']['photo'] ?? '');
    $projectRoot = realpath(dirname(__DIR__, 2));
    $file = false;

    if ($projectRoot && str_starts_with($photo, 'storage/uploads/')) {
      $storageRoot = realpath($projectRoot . '/storage/uploads');
      $candidate = realpath($projectRoot . '/' . ltrim($photo, '/'));
      if ($storageRoot && $candidate && str_starts_with($candidate, $storageRoot . DIRECTORY_SEPARATOR) && is_file($candidate)) $file = $candidate;
    }

    if (!$file && $projectRoot) {
      $legacy = ltrim($photo, '/');
      foreach ([$legacy, preg_replace('#^(?:images?|img)/avatar/#', 'assets/img/avatar/', $legacy)] as $relative) {
        if (!$relative) continue;
        $candidate = realpath($projectRoot . '/public/' . $relative);
        $publicRoot = realpath($projectRoot . '/public');
        if ($candidate && $publicRoot && str_starts_with($candidate, $publicRoot . DIRECTORY_SEPARATOR) && is_file($candidate)) {
          $file = $candidate;
          break;
        }
      }
    }

    if (!$file && $projectRoot) $file = realpath($projectRoot . '/public/assets/img/avatar/default.png');
    if (!$file || !is_file($file)) {
      http_response_code(404);
      exit;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'image/png';
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
  }

  public function periods(): void
  {
    $this->json(function () {
      $u = $_SESSION['user'];
      $db = DB::getInstance();
      $regional = in_array($u['type_user'] ?? '', ['super_admin', 'admin_wilayah', 'tapd'], true);
      $sql = 'SELECT DISTINCT p.id,p.periode_mulai,p.periode_selesai,p.keterangan FROM periode_rpjmd p';
      $params = [];
      if (!$regional) {
        $sql .= ' INNER JOIN renstra_neo r ON r.periode_id=p.id AND r.kd_wilayah=? AND r.kd_opd=?';
        $params = [$u['kd_wilayah'], $u['kd_opd']];
      }
      $sql .= ' WHERE COALESCE(p.status_aktif,1)=1 ORDER BY p.periode_mulai DESC';
      return ['scope' => $regional ? 'RPJMD' : 'RENSTRA', 'periods' => $db->query($sql, $params)->fetchAll(), 'selected_year' => (int)$u['tahun']];
    });
  }

  public function selectPeriod(): void
  {
    $this->json(function () {
      $id = (int)($_POST['periode_id'] ?? 0);
      $year = (int)($_POST['tahun'] ?? 0);
      $u = $_SESSION['user'];
      $db = DB::getInstance();
      $p = $db->query('SELECT id,periode_mulai,periode_selesai FROM periode_rpjmd WHERE id=? AND COALESCE(status_aktif,1)=1', [$id])->fetch();
      if (!$p || $year < (int)$p['periode_mulai'] || $year > (int)$p['periode_selesai']) throw new InvalidArgumentException('Tahun harus berada dalam rentang periode aktif');
      $regional = in_array($u['type_user'] ?? '', ['super_admin', 'admin_wilayah', 'tapd'], true);
      if (!$regional) {
        $allowed = $db->query('SELECT 1 FROM renstra_neo WHERE periode_id=? AND kd_wilayah=? AND kd_opd=? LIMIT 1', [$id, $u['kd_wilayah'], $u['kd_opd']])->fetchColumn();
        if (!$allowed) throw new RuntimeException('Periode tersebut bukan Renstra aktif OPD pengguna');
      }
      $_SESSION['user']['tahun'] = $year;
      $_SESSION['user']['periode_id'] = $id;
      $db->update('user_sesendok_biila', ['tahun' => $year], 'WHERE id=?', [(int)$u['id']]);
      return ['tahun' => $year, 'periode_id' => $id];
    });
  }

  public function uploadPhoto(): void
  {
    $this->json(function () {
      $file = $_FILES['photo'] ?? [];
      if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Pilih gambar profil');
      if ((int)$file['size'] > 3 * 1024 * 1024) throw new InvalidArgumentException('Foto profil maksimal 3 MB');
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
      $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
      if (!$ext) throw new InvalidArgumentException('Foto harus JPG, PNG, atau WebP');
      $u = $_SESSION['user'];
      $old = (string)($u['photo'] ?? '');
      $relative = 'storage/uploads/' . preg_replace('/[^A-Za-z0-9._-]/', '_', ($u['kd_wilayah'] ?? 'wilayah') . '-' . ($u['kd_opd'] ?? 'daerah')) . '/profil';
      $dir = dirname(__DIR__, 2) . '/' . $relative;
      if (!is_dir($dir) && !mkdir($dir, 0770, true)) throw new RuntimeException('Folder foto profil tidak dapat dibuat');
      $name = 'user-' . (int)$u['id'] . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
      if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Foto gagal disimpan');
      $path = $relative . '/' . $name;
      $db = DB::getInstance();
      $db->update('user_sesendok_biila', ['photo' => $path], 'WHERE id=?', [(int)$u['id']]);
      $saved = (string)($db->query('SELECT photo FROM user_sesendok_biila WHERE id=? LIMIT 1', [(int)$u['id']])->fetchColumn() ?: '');
      if ($saved !== $path) {
        @unlink($dir . '/' . $name);
        throw new RuntimeException('Foto profil gagal tersimpan di database');
      }
      $_SESSION['user']['photo'] = $path;
      if (str_starts_with($old, 'storage/uploads/') && $old !== $path) {
        $oldFile = dirname(__DIR__, 2) . '/' . $old;
        if (is_file($oldFile)) @unlink($oldFile);
      }
      return ['photo' => $path, 'url' => app_url('/profil/photo')];
    });
  }
}
