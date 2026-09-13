<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class KepegawaianController extends Controller
{
  private $allowedTables = [
    'asn',
    'pppk',
    'riwayat_jabatan',
    'riwayat_pangkat',
    'cuti',
    'sk_pegawai',
    'pejabat_tahunan',
    'absensi',
    'penugasan_subkegiatan',
    'dokumen_pegawai'
  ];

  public function index()
  {
    if (!Auth::check()) {
      header('Location: ' . app_url('/'));
      exit;
    }

    $tbl = $_GET['tbl'] ?? 'asn';

    if (!in_array($tbl, $this->allowedTables)) {
      $tbl = 'asn';
    }

    $this->view('referensi/index', [
      'tbl' => $tbl
    ], 'app');
  }

  private function structureUser(): array
  {
    if (!Auth::check()) throw new RuntimeException('Sesi login tidak valid');
    $user = Auth::scopedUser();
    if (($user['type_user'] ?? '') === 'admin_wilayah' && (empty($user['kd_opd']) || $user['kd_opd'] === '0')) {
      $user['kd_opd'] = 'SETDA';
      $user['structure_scope'] = 'regional';
    }
    if (empty($user['kd_wilayah']) || empty($user['kd_opd']) || $user['kd_opd'] === '0') {
      throw new RuntimeException('Pilih satu OPD untuk membuka struktur organisasi');
    }
    return $user;
  }

  private function canManageStructure(array $user): bool
  {
    return in_array($user['type_user'] ?? '', ['admin_wilayah', 'admin_opd', 'kepala_opd', 'pa_kpa'], true);
  }

  public function structure(): void
  {
    try {
      $user = $this->structureUser();
    } catch (Throwable $e) {
      http_response_code(403);
      echo htmlspecialchars($e->getMessage());
      return;
    }
    $this->view('kepegawaian/struktur', ['canManage' => $this->canManageStructure($user), 'regionalScope' => (($user['structure_scope'] ?? '') === 'regional'), 'scopeOpd' => $user['kd_opd']], 'app');
  }

  public function structureData(): void
  {
    header('Content-Type: application/json;charset=UTF-8');
    try {
      $u = $this->structureUser();
      $db = DB::getInstance();
      $scopeOpds = (($u['structure_scope'] ?? '') === 'regional') ? ['BUPATI', 'SETDA'] : [$u['kd_opd'], 'SETDA', 'BUPATI'];
      $marks = implode(',', array_fill(0, count($scopeOpds), '?'));
      $rowParams = array_merge([$u['kd_wilayah'], $u['tahun']], $scopeOpds);
      $rows = $db->query("SELECT s.*,CONCAT_WS(' ',a.gelar_depan,a.nama,a.gelar) nama_pegawai,a.nip,CONCAT(s.kd_opd,':',s.id) struktur_key,CONCAT(COALESCE(s.parent_kd_opd,s.kd_opd),':',COALESCE(s.parent_id,0)) parent_key FROM struktur_jabatan_opd_neo s LEFT JOIN db_asn_pemda_neo a ON a.id=s.pegawai_id AND a.is_deleted=0 WHERE s.kd_wilayah=? AND s.tahun=? AND s.kd_opd IN ($marks) AND s.status_jabatan='AKTIF' AND s.berlaku_mulai<=CURDATE() AND (s.berlaku_sampai IS NULL OR s.berlaku_sampai>=CURDATE()) AND s.is_deleted=0 ORDER BY s.kd_opd,s.urutan,s.id", $rowParams)->fetchAll();
      $employeeOpds = (($u['structure_scope'] ?? '') === 'regional') ? ['BUPATI', 'SETDA'] : [$u['kd_opd']];
      $marks = implode(',', array_fill(0, count($employeeOpds), '?'));
      $employees = $db->query("SELECT id,CONCAT_WS(' ',gelar_depan,nama,gelar) nama,nip,jabatan,kd_opd FROM db_asn_pemda_neo WHERE kd_wilayah=? AND kd_opd IN ($marks) AND is_deleted=0 AND disable=0 AND COALESCE(aktif,1)=1 ORDER BY nama", array_merge([$u['kd_wilayah']], $employeeOpds))->fetchAll();
      echo json_encode(['success' => true, 'data' => ['rows' => $rows, 'employees' => $employees]], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
  }

  public function structureSave(): void
  {
    header('Content-Type: application/json;charset=UTF-8');
    try {
      $u = $this->structureUser();
      if (!$this->canManageStructure($u)) throw new RuntimeException('Struktur jabatan hanya dapat diatur Admin OPD, Kepala OPD, atau PA/KPA');
      if (empty($_SESSION['csrf_token']) || ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '') !== $_SESSION['csrf_token']) throw new RuntimeException('CSRF validation gagal');
      $id = (int)($_POST['id'] ?? 0);
      $targetOpd = trim((string)($_POST['target_kd_opd'] ?? $u['kd_opd']));
      $regional = (($u['structure_scope'] ?? '') === 'regional');
      if ($targetOpd !== $u['kd_opd'] && !($regional && in_array($targetOpd, ['BUPATI', 'SETDA'], true))) throw new RuntimeException('Scope struktur tidak boleh diubah');
      $employeeId = (int)($_POST['pegawai_id'] ?? 0);
      $name = trim((string)($_POST['nama_jabatan'] ?? ''));
      if (!$employeeId || $name === '') throw new InvalidArgumentException('Jabatan dan pejabat ASN wajib dipilih');
      $db = DB::getInstance();
      if (!$db->query('SELECT id FROM db_asn_pemda_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0 AND disable=0 LIMIT 1', [$employeeId, $u['kd_wilayah'], $targetOpd])->fetch()) throw new RuntimeException('ASN tidak aktif atau bukan bagian dari scope ini');
      $parentToken = trim((string)($_POST['parent_id'] ?? ''));
      $parentParts = $parentToken !== '' ? explode(':', $parentToken, 2) : [];
      $parent = !empty($parentParts[1]) ? (int)$parentParts[1] : null;
      $parentOpd = trim((string)($_POST['parent_kd_opd'] ?? ($parentParts[0] ?? $targetOpd)));
      if ($parent && !$db->query("SELECT id FROM struktur_jabatan_opd_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND status_jabatan='AKTIF' AND is_deleted=0", [$parent, $u['kd_wilayah'], $parentOpd, $u['tahun']])->fetch()) throw new RuntimeException('Atasan langsung tidak valid');
      if ($parent === $id && $id) throw new InvalidArgumentException('Jabatan tidak dapat menjadi atasan dirinya sendiri');
      $skNumber = trim((string)($_POST['nomor_sk_pengangkatan'] ?? ''));
      $skDate = trim((string)($_POST['tanggal_sk_pengangkatan'] ?? ''));
      $tmt = trim((string)($_POST['tmt_jabatan'] ?? $skDate));
      if ($skNumber === '' || $skDate === '') throw new InvalidArgumentException('Nomor dan tanggal SK pengangkatan wajib diisi');
      $effective = $tmt ?: $skDate;
      $existing = $id ? $db->query('SELECT * FROM struktur_jabatan_opd_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0', [$id, $u['kd_wilayah'], $targetOpd, $u['tahun']])->fetch() : null;
      $data = ['pegawai_id' => $employeeId, 'parent_id' => $parent, 'parent_kd_opd' => $parent ? $parentOpd : null, 'nama_jabatan' => $name, 'kelompok_jabatan' => trim((string)($_POST['kelompok_jabatan'] ?? '')), 'eselon' => trim((string)($_POST['eselon'] ?? '')), 'nomor_sk_pengangkatan' => $skNumber, 'tanggal_sk_pengangkatan' => $skDate, 'tmt_jabatan' => $effective, 'berlaku_mulai' => $effective, 'berlaku_sampai' => null, 'status_jabatan' => 'AKTIF', 'urutan' => max(1, (int)($_POST['urutan'] ?? 1)), 'keterangan' => trim((string)($_POST['keterangan'] ?? ''))];
      $newPeriod = !$existing || (int)$existing['pegawai_id'] !== $employeeId || ($existing['nomor_sk_pengangkatan'] ?? '') !== $skNumber || ($existing['berlaku_mulai'] ?? '') !== $effective;
      $db->begin();
      try {
        if ($newPeriod) {
          $old = $existing ?: $db->query("SELECT * FROM struktur_jabatan_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND nama_jabatan=? AND status_jabatan='AKTIF' AND is_deleted=0 ORDER BY berlaku_mulai DESC,id DESC LIMIT 1", [$u['kd_wilayah'], $targetOpd, $u['tahun'], $name])->fetch();
          $oldId = (int)($old['id'] ?? 0);
          if ($oldId) {
            $db->query("UPDATE struktur_jabatan_opd_neo SET berlaku_sampai=DATE_SUB(?,INTERVAL 1 DAY),status_jabatan='BERAKHIR',tgl_update=NOW(),username_update=? WHERE id=?", [$effective, $u['username'] ?? 'system', $oldId]);
            $db->query('UPDATE riwayat_jabatan_neo SET tanggal_selesai=DATE_SUB(?,INTERVAL 1 DAY),tgl_update=NOW(),username_update=? WHERE sumber_struktur_id=? AND is_deleted=0 AND tanggal_selesai IS NULL', [$effective, $u['username'] ?? 'system', $oldId]);
          }
          $data += ['kd_wilayah' => $u['kd_wilayah'], 'kd_opd' => $targetOpd, 'tahun' => $u['tahun'], 'tgl_insert' => date('Y-m-d H:i:s'), 'username_insert' => $u['username'] ?? 'system', 'is_deleted' => 0];
          $id = $db->insert('struktur_jabatan_opd_neo', $data);
          if ($oldId) $db->query('UPDATE struktur_jabatan_opd_neo SET parent_id=?,tgl_update=NOW(),username_update=? WHERE parent_id=? AND parent_kd_opd=? AND kd_wilayah=? AND tahun=? AND status_jabatan=\'AKTIF\' AND is_deleted=0', [$id, $u['username'] ?? 'system', $oldId, $targetOpd, $u['kd_wilayah'], $u['tahun']]);
          $db->insert('riwayat_jabatan_neo', ['tahun' => $u['tahun'], 'kd_wilayah' => $u['kd_wilayah'], 'kd_opd' => $targetOpd, 'pegawai_id' => $employeeId, 'sumber_struktur_id' => $id, 'nomor_sk' => $skNumber, 'jabatan' => $name, 'unit_kerja' => $targetOpd, 'tmt' => $effective, 'keterangan' => $data['keterangan'], 'username_insert' => $u['username'] ?? 'system']);
        } else {
          $data += ['tgl_update' => date('Y-m-d H:i:s'), 'username_update' => $u['username'] ?? 'system'];
          $db->update('struktur_jabatan_opd_neo', $data, 'WHERE id=?', [$id]);
        }
        $db->commit();
      } catch (Throwable $e) { $db->rollback(); throw $e; }
      echo json_encode(['success' => true, 'message' => 'Struktur jabatan berhasil disimpan', 'data' => ['id' => $id]], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
  }

  public function structureDelete(): void
  {
    header('Content-Type: application/json;charset=UTF-8');
    try {
      $u = $this->structureUser();
      if (!$this->canManageStructure($u)) throw new RuntimeException('Hanya Admin OPD, Kepala OPD, atau PA/KPA yang dapat menghapus jabatan');
      if (empty($_SESSION['csrf_token']) || ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '') !== $_SESSION['csrf_token']) throw new RuntimeException('CSRF validation gagal');
      $id = (int)($_POST['id'] ?? 0);
      $targetOpd = trim((string)($_POST['target_kd_opd'] ?? $u['kd_opd']));
      if ($targetOpd !== $u['kd_opd'] && !(($u['structure_scope'] ?? '') === 'regional' && in_array($targetOpd, ['BUPATI', 'SETDA'], true))) throw new RuntimeException('Scope struktur tidak boleh diubah');
      $db = DB::getInstance();
      $db->begin();
      try {
        $db->query('UPDATE struktur_jabatan_opd_neo SET parent_id=NULL,parent_kd_opd=NULL,tgl_update=NOW(),username_update=? WHERE parent_id=? AND parent_kd_opd=? AND kd_wilayah=? AND tahun=? AND is_deleted=0', [$u['username'] ?? 'system', $id, $targetOpd, $u['kd_wilayah'], $u['tahun']]);
        $db->query("UPDATE struktur_jabatan_opd_neo SET berlaku_sampai=CURDATE(),status_jabatan='BERAKHIR',tgl_update=NOW(),username_update=? WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=?", [$u['username'] ?? 'system', $id, $u['kd_wilayah'], $targetOpd, $u['tahun']]);
        $db->query('UPDATE riwayat_jabatan_neo SET tanggal_selesai=CURDATE(),tgl_update=NOW(),username_update=? WHERE sumber_struktur_id=? AND is_deleted=0 AND tanggal_selesai IS NULL', [$u['username'] ?? 'system', $id]);
        $db->commit();
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
      echo json_encode(['success' => true, 'message' => 'Masa berlaku jabatan diakhiri; riwayat tetap tersimpan']);
    } catch (Throwable $e) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
  }
}
