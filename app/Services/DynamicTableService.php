<?php

require_once __DIR__ . '/JsonResponse.php';

/**
 * ============================================================
 * DYNAMIC TABLE SERVICE v3.0 (FULL IDENTIK + ACTION VERSION)
 * ============================================================
 *
 * PERUBAHAN:
 * ------------------------------------------------------------
 * ❌ jenis  → diganti action
 * ❌ mode   → diganti action
 * ✅ module → hanya konteks (tidak mengganggu logic lama)
 *
 * Semua fitur lama DIPERTAHANKAN 100%
 * ============================================================
 */
/**
 * ============================================================
 * DYNAMIC TABLE SERVICE v3.2 — ENTERPRISE SAFE IMPORT READY
 * ============================================================
 *
 * ARSITEKTUR UTAMA:
 * ------------------------------------------------------------
 * Class ini adalah CORE BUSINESS ENGINE seluruh sistem.
 *
 * Semua operasi CRUD, LISTING, DROPDOWN, EXPORT, IMPORT
 * melewati service ini.
 *
 * DESIGN PRINCIPLE:
 * ------------------------------------------------------------
 * 1️⃣ Profile-driven (berbasis table_profiles.php)
 * 2️⃣ Schema-aware (ambil rules dari DB langsung)
 * 3️⃣ Role-aware (authorize + scope)
 * 4️⃣ Soft-lock aware
 * 5️⃣ Time-window aware
 * 6️⃣ Hierarchy-aware
 * 7️⃣ Duplicate-aware
 * 8️⃣ Audit trail ready
 * 9️⃣ Transaction safe
 *
 * ------------------------------------------------------------
 * 🔥 IMPORT ENGINE STATUS:
 * - Strict mode (rollback jika ada error)
 * - Relation mapping (text → foreign key)
 * - Multi relation support
 * - Relation cache (no query per row)
 * - Duplicate & hierarchy validation
 * - Auto session injection
 *
 * ------------------------------------------------------------
 * TIDAK ADA LOGIC LAMA YANG DIHAPUS
 * Hanya enhancement yang ditambahkan secara isolated.
 * ============================================================
 */
require_once __DIR__ . '/DynamicTable/autoload.php';

use App\Services\DynamicTable\DynamicImportHelper;

use App\Services\DynamicTable\DynamicMetadataService; // //

use App\Services\DynamicTable\DynamicConfigService; // //
use App\Services\DynamicTable\DynamicSanitizer; // //
//segala macam fungsi didalamnya
use App\Services\DynamicTable\DynamicDoc;
use App\Services\DynamicTable\DynamicResolver;

class DynamicTableService
{
  private array $cacheSatuan = [];
  private DB $db;
  private array $profiles;
  private array $user;
  // ======================================================
  // 🔥 CACHE RELATION IMPORT
  // Digunakan untuk menyimpan hasil lookup relasi
  // agar tidak query database per baris Excel
  // ======================================================
  private array $relationCache = [];
  /* =========================================================
INTERNAL CACHE (ANTI DOUBLE QUERY)
========================================================= */
  private static array $columnCache = [];
  private static array $schemaCache = [];
  private ?array $pengaturanAktifCache = null;
  private ?array $periodeAktifCache = null;
  // tambahan pemisahan class
  private $importHelper; // lazy //
  private $meta; // lazy //
  private $config; // lazy //
  private $sanitizer; // lazy //
  private $resolver; // 🔥 FIX: declare property untuk PHP 8.2+
  public function __construct()
  {
    $this->db = DB::getInstance();
    $this->profiles = require __DIR__ . '/../Config/table_profiles.php';
    $this->user = $_SESSION['user'] ?? [];
  }
  private function importHelper() // //
  {
    return $this->importHelper ??= new DynamicImportHelper(); // //
  }

  private function meta() // //
  {
    return $this->meta ??= new DynamicMetadataService(); // //
  }

  private function config() // //
  {
    return $this->config ??= new DynamicConfigService($this->user); // //
  }

  private function sanitizer() // //
  {
    return $this->sanitizer ??= new DynamicSanitizer($this); // //
  }
  private function resolver()
  {
    return $this->resolver ??= new DynamicResolver(
      $this->config(),
      $this->profiles,
      fn($table) => $this->getTableColumns($table) // 🔥 inject dari service
    );
  }
  /* =========================================================
MAIN HANDLER (ENTRY POINT) — HARDENED VERSION
---------------------------------------------------------
PERUBAHAN:
- Wajib action eksplisit
- Tidak ada fallback implicit
- Validasi action lebih awal
- Tetap kompatibel dengan arsitektur lama
========================================================= */
  public function handle(array $request): string
  {
    try {

      if (empty($request['action'])) {
        return JsonResponse::error("Action wajib dikirim");
      }
      // 🔥 CLEAN REQUEST (WAJIB)
      $request = array_filter($request, function ($v) {
        return $v !== null && $v !== '';
      });
      $action = $request['action'];

      $allowedActions = ['add', 'add_json', 'edit', 'edit_json', 'delete', 'dropdown', 'export', 'list', 'import'];

      if (!in_array($action, $allowedActions)) {
        return JsonResponse::error("Action tidak valid");
      }

      $tbl = $request['tbl'] ?? null;
      $req = $request['req'] ?? null;

      if (!$tbl || !isset($this->profiles[$tbl])) {
        return JsonResponse::error("Tabel tidak terdaftar");
      }

      $profile = $this->profiles[$tbl];

      $table = $profile['table'];

      if ($req && isset($this->profiles[$req])) {
        $reqProfile = $this->profiles[$req];
        $table = $reqProfile['table'];
        $request['_req_profile'] = $reqProfile;
      }

      return $this->executeAction($action, $tbl, $table, $profile, $request);
    } catch (\Throwable $e) {
      return JsonResponse::error($e->getMessage());
    }
  }

  //=========================================================
  // EXECUTE ACTION (NO IMPLICIT FALLBACK)
  //---------------------------------------------------------
  //PERUBAHAN:
  //- Listing hanya via action = 'list'
  //- Tidak ada default auto listing
  //- Lebih eksplisit & SPA konsisten
  //=========================================================
  private function executeAction(string $action, string $tbl, string $table, array $profile, array $request): string
  {
    switch ($action) {

      case 'add':
        $this->authorize('add', $table);
        return $this->insert($table, $request);
      case 'add_json':
        $this->authorize('add', $table);
        return $this->insertJson($table, $request);
      case 'edit':
        $mode = $request['mode'] ?? 'get'; // // 🔥 fallback aman

        if (!empty($request['id_row']) && $mode === 'get') {
          $this->authorize('view', $table);
          return $this->getById($table, $request['id_row']);
        }

        if (!empty($request['id_row']) && ($request['mode'] ?? '') === 'update') {
          $this->authorize('edit', $table);
          return $this->update($table, $request);
        }

        return JsonResponse::error("ID tidak ditemukan");
      case 'edit_json':
        $this->authorize('edit', $table);
        // // DEBUG GUARD
        if (empty($request['struktur_json'])) {
          return JsonResponse::error("struktur_json tidak terkirim"); // // pastikan request benar
        }
        return $this->updateJson($table, $request); // 🔥
      case 'delete':
        $this->authorize('delete', $table);
        $id = $request['id_row'] ?? null;

        if (!$id) {
          return JsonResponse::error("ID tidak valid");
        }

        return $this->delete($table, $profile, (int)$id);

      case 'dropdown':

        $tbl    = $_POST['tbl'] ?? null;
        $source = $_POST['source'] ?? null;

        $profileKey = $this->resolveProfileKey($tbl, $source);

        if (!$profileKey) {
          return JsonResponse::error("Source tidak ditemukan");
        }

        $parentValue = $_POST['parent_value'] ?? null;
        $kdAkun      = $_POST['kd_akun'] ?? null;

        return $this->loadDropdown($profileKey, $parentValue, $kdAkun);

      case 'export':
        $this->authorize('view', $table);
        return $this->export($table, $profile, $request, 'default');

      case 'list':
        $this->authorize('view', $table);
        return $this->listing($table, $profile, $request, 'default');

      case 'import':

        $this->authorize('add', $table);

        if (empty($_FILES['file']['tmp_name'])) {
          return JsonResponse::error("File tidak ditemukan");
        }

        $jmlHeader = $request['jml_header'] ?? 1;

        return $this->importStrict(
          $tbl,
          $_FILES['file']['tmp_name'],
          (int)$jmlHeader
        );

      default:
        return JsonResponse::error("Action tidak dikenali");
    }
  }
  /* =========================================================
GET SINGLE ROW
========================================================= */
  private function getById(string $table, int|string $id): string
  {
    $primaryKey = $this->getPrimaryKey($table);

    if (!$this->checkAccess($table, $id)) {
      return JsonResponse::error("Data tidak ditemukan atau tidak memiliki akses");
    }

    $row = $this->db->query(
      "SELECT * FROM `$table` WHERE `$primaryKey` = ? LIMIT 1",
      [$id]
    )->fetch();

    if (!$row) {
      return JsonResponse::error("Data tidak ditemukan");
    }

    // ======================================================
    // LOAD PROFILE (1x saja)
    // ======================================================
    $profile = $this->getProfileByTable($table);

    // ======================================================
    // 🔥 WRITE RELATIONS (TIDAK DIUBAH)
    // ======================================================
    if (!empty($profile['write_relations'])) {

      foreach ($profile['write_relations'] as $relTable => $rel) {

        $fk = $rel['fk'] ?? null;
        if (!$fk) continue;

        $rows = $this->db->query(
          "SELECT * FROM `$relTable` WHERE `$fk` = ?",
          [$id]
        )->fetchAll();

        if (!$rows) continue;

        $mode = $this->detectRelationMode($relTable);

        // JSON
        if ($mode === 'json') {

          $columns = $this->getTableColumns($relTable);

          $jsonField = in_array('struktur_json', $columns)
            ? 'struktur_json'
            : (in_array('meta_json', $columns) ? 'meta_json' : null);

          if ($jsonField && !empty($rows[0][$jsonField])) {

            $json = json_decode($rows[0][$jsonField], true);

            if (is_array($json)) {

              // ==========================================
              // 🔥 HAPUS FIELD JSON ASLI (STRING)
              // ==========================================
              unset($row[$jsonField]); // //

              // ==========================================
              // 🔥 HAPUS KEY DUPLIKAT DI DALAM JSON
              // ==========================================
              unset($json[$jsonField]); // //

              // ==========================================
              // MERGE AMAN
              // ==========================================
              $row = array_merge($row, $json); // //
            }
          }

          continue;
        }

        // KV
        if ($mode === 'kv') {

          foreach ($rows as $r) {
            if (isset($r['meta_key'])) {
              $row[$r['meta_key']] = $r['meta_value'] ?? null;
            }
          }

          continue;
        }

        // TABLE
        $row[$relTable] = $rows;
      }
    }

    // ======================================================
    // 🔥 READ RELATIONS (SELECT + STATIC + CALL SUPPORT)
    // ======================================================
    if (!empty($profile['read_relations'])) {

      foreach ($profile['read_relations'] as $relTable => $rel) {

        // =====================================
        // 🔥 MODE: CALL CLASS METHOD
        // =====================================
        if (!empty($rel['call'])) {

          $className = $rel['call']['class'] ?? null;
          $method = $rel['call']['method'] ?? null;

          if (!$className || !$method) continue;
          if (!class_exists($className)) continue;

          $instance = new $className();

          $paramsCall = [];

          foreach (($rel['params'] ?? []) as $paramKey => $mainCol) {

            if (!isset($row[$mainCol])) continue;

            $paramsCall[$paramKey] = $row[$mainCol];
          }

          $result = call_user_func([$instance, $method], $paramsCall);

          $row['_read'][$relTable] = $result;

          continue;
        }

        // =====================================
        // 🔥 MODE: SQL WHERE
        // =====================================
        $whereConfig = $rel['where'] ?? null;
        if (!$whereConfig || !is_array($whereConfig)) continue;

        $whereSql = [];
        $params = [];

        foreach ($whereConfig as $relCol => $mainCol) {

          // STATIC VALUE
          if (is_array($mainCol) && isset($mainCol['value'])) {
            $whereSql[] = "`$relCol` = ?";
            $params[] = $mainCol['value'];
            continue;
          }

          // NORMAL MAPPING
          if (!isset($row[$mainCol])) continue;

          $whereSql[] = "`$relCol` = ?";
          $params[] = $row[$mainCol];
        }

        if (empty($whereSql)) continue;

        $where = "WHERE " . implode(" AND ", $whereSql);

        // =====================================
        // 🔥 FIX: SUPPORT SELECT
        // =====================================
        $select = $rel['select'] ?? '*';

        if (is_array($select)) {
          $select = implode(",", $select);
        }

        $sql = "SELECT {$select} FROM `$relTable` {$where}";

        $relData = $this->db->query(
          $sql,
          $params
        )->fetchAll();
        // ==========================================
        // 🔥 APPLY ALIAS DI READ_RELATIONS mengganti nama properties
        // ==========================================
        if (!empty($rel['alias'])) { // //

          $doc = new \App\Services\DynamicTable\DynamicDoc(); // //

          $relData = $doc->apply(
            $relData, // //
            $rel['alias'] // //
          );
        }
        // =====================================
        // ISOLASI
        // =====================================
        $row['_read'][$relTable] = $relData;
      }
    }

    // ======================================================
    // SCHEMA (OPTIONAL)
    // ======================================================
    $schema = null;

    // ==========================================
    // 🔥 FIX: ambil schema dari read_relations
    // ==========================================
    if (!empty($row['_read']['cache_schema_naskah'][0]['schema_json'])) { // //

      $schema = json_decode(
        $row['_read']['cache_schema_naskah'][0]['schema_json'],
        true
      ); // //

    } elseif (!empty($profile['schema'])) { // fallback lama //

      $schema = $profile['schema'];
    }

    // ==========================================
    // 🔥 APPLY ALIAS SINGLE ROW
    // ==========================================
    $doc = new DynamicDoc(); // //

    $row = $doc->apply(
      [$row], // bungkus array //
      $profile['alias'] ?? []
    )[0] ?? $row;

    // ==========================================

    return JsonResponse::success(
      "Data ditemukan",
      [
        'schema' => $schema
      ],
      $row
    );
  }

  /* =========================================================
ROLE AUTHORIZATION (TIDAK DIUBAH)
========================================================= */
  private function authorize(string $action, string $table): void
  {
    $role = $this->user['type_user'] ?? 'viewer';

    $permissions = [
      'super_admin'   => ['add', 'edit', 'delete', 'view'],
      'admin_wilayah' => ['add', 'edit', 'delete', 'view'],
      'admin_opd'     => ['add', 'edit', 'delete', 'view'],
      'editor'        => ['add', 'edit', 'view'],
      'viewer'        => ['view']
    ];

    if (!in_array($action, $permissions[$role] ?? [])) {
      throw new Exception("Tidak memiliki hak akses");
    }
  }

  /* =========================================================
AUDIT TRAIL (TIDAK DIUBAH)
========================================================= */
  private function injectAudit(array $data, string $mode): array
  {
    $now  = date('Y-m-d H:i:s');
    $user = $this->user['username'] ?? 'system';

    if ($mode === 'insert') {
      $data['tgl_insert'] = $now;
      $data['username_insert'] = $user;
    }

    if ($mode === 'update') {
      $data['tgl_update'] = $now;
      $data['username_update'] = $user;
    }

    return $data;
  }
  /* =========================================================
INSERT (FULL IDENTIK LOGIC ASLI)
========================================================= */
  /* =========================================================
INSERT (FIXED STABLE VERSION v3.1)
========================================================= */
  private function insert(string $table, array $request): string
  {
    //=========================================
    // ANTI DOUBLE SUBMIT
    // =========================================

    $this->guardDoubleSubmit($request);
    $columns  = $this->getTableColumns($table);
    $filtered = [];

    // /* =====================================================
    // 1️⃣ FILTER FIELD SESUAI KOLOM TABEL
    // ===================================================== */
    foreach ($request as $key => $value) {

      // =========================================
      // IGNORE FIELD SISTEM
      // =========================================
      if (in_array($key, ['action', 'tbl', 'req'])) {
        continue;
      }

      // =========================================
      // HANYA FIELD YANG ADA DI KOLOM TABEL
      // =========================================
      if (in_array($key, $columns)) {
        $filtered[$key] = $value;
      }
    }

    //=====================================================
    // DEBUG JIKA FILTERED KOSONG
    // Tujuan:
    // Mengetahui kenapa request tidak cocok dengan kolom tabel
    // ===================================================== */

    if (empty($filtered)) {

      return JsonResponse::error(
        "Tidak ada data yang bisa disimpan",
        400,
        [

          // tabel yang sedang dipakai
          'table_used' => $table,

          // kolom yang ada di tabel database
          'table_columns' => $columns,

          // field yang dikirim dari request
          'request_fields' => array_keys($request)

        ]
      );
    }

    //=====================================================
    //2️⃣ NORMALISASI BOOLEAN & DATE
    //=====================================================
    foreach ($filtered as $field => $value) {

      // checkbox
      if ($value === 'on') {
        $filtered[$field] = 1;
      }

      // date format
      if (
        is_string($value)
        && $value !== ''
        && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)
      ) {

        $parts = explode('/', $value);

        $filtered[$field] = sprintf(
          '%04d-%02d-%02d',
          $parts[2],
          $parts[1],
          $parts[0]
        );
      }
    }

    /* =====================================================
3️⃣ AUTO FIELD RESOLUTION (SCOPE)
===================================================== */
    $filtered = $this->resolveAutoFields($table, $filtered);
    //date time
    $filtered = $this->normalizeDateTimeFields($table, $filtered);
    /* =====================================================
🔥 AUTO TAHAP BERDASARKAN TBL UNTUK INPUT KEGIATAN RENJA-DPPA di group_sub_kegiatan
===================================================== */

    $tbl = $request['tbl'] ?? null;   // tbl berasal dari request frontend

    // jika tbl ada dan field tahap belum diisi
    if ($tbl && isset($filtered['tahap']) === false) {

      // 🔥 panggil resolver tahap otomatis
      $tahap = $this->resolveTahap($tbl);

      // jika resolver menghasilkan tahap valid
      if ($tahap !== null) {

        // set nilai tahap ke data yang akan disimpan
        $filtered['tahap'] = $tahap;
      }
    }
    /* =====================================================
4️⃣ SPECIAL TABLE RULES
===================================================== */

    // 🔥 Periode RPJMD
    if ($table === 'periode_rpjmd') {

      $mulai   = (int)($filtered['periode_mulai'] ?? 0);
      $selesai = (int)($filtered['periode_selesai'] ?? 0);
      $kd_wilayah = $this->user['kd_wilayah'] ?? null;

      if (!$kd_wilayah) {
        return JsonResponse::error("Wilayah tidak ditemukan");
      }

      if ($mulai >= $selesai) {
        return JsonResponse::error("Periode tidak valid");
      }

      $cek = $this->db->query("
    SELECT id FROM periode_rpjmd
    WHERE kd_wilayah = ?
    AND (
        (? BETWEEN periode_mulai AND periode_selesai)
        OR
        (? BETWEEN periode_mulai AND periode_selesai)
    )
", [$kd_wilayah, $mulai, $selesai])->fetch();

      if ($cek) {
        return JsonResponse::error("Periode tumpang tindih");
      }

      if (!empty($filtered['status_aktif'])) {

        $this->db->query("
        UPDATE periode_rpjmd
        SET status_aktif = 0
        WHERE kd_wilayah = ?
    ", [$kd_wilayah]);

        $filtered['status_aktif'] = 1;
      } else {
        $filtered['status_aktif'] = 0;
      }
    }

    // 🔥 Auto periode untuk renstra
    $filtered = $this->resolver()->resolvePeriode($table, $filtered);
    $this->validateTimeWindow($table);

    // 🔥 Auto generate kode misi
    // if ($table === 'misi_renstra_neo') {

    //     $renstraId = $filtered['renstra_id'] ?? null;

    //     if (!$renstraId) {
    //         return JsonResponse::error("Renstra wajib dipilih");
    //     }

    //     $lastKode = $this->db->query("
    //         SELECT MAX(CAST(kode AS UNSIGNED)) as max_kode
    //         FROM misi_renstra_neo
    //         WHERE renstra_id = ?
    //     ", [$renstraId])->fetch()['max_kode'] ?? 0;

    //     $filtered['kode'] = $lastKode + 1;
    // }

    /* =====================================================
5️⃣ SYSTEM DEFAULT FIELD
===================================================== */
    if (in_array('disable', $columns) && !isset($filtered['disable'])) {
      $filtered['disable'] = 0;
    }

    if (in_array('is_deleted', $columns) && !isset($filtered['is_deleted'])) {
      $filtered['is_deleted'] = 0;
    }

    /* =====================================================
6️⃣ PERATURAN RESOLUTION (GLOBAL CLEAN)
===================================================== */
    $filtered = $this->resolver()->resolvePeraturan($table, $filtered);

    /* =====================================================
🔥 LOOKUP RESOLUTION
Digunakan untuk mengisi field turunan otomatis
contoh:
kd_sub_keg → nama_sub_keg
===================================================== */
    $profile = $this->getProfileByTable($table);
    $this->applyLookup($filtered, $profile);

    /* =====================================================
7️⃣ BUSINESS VALIDATION LAYER
===================================================== */
    $this->validateHierarchy($table, $filtered);
    $this->validateDuplicate($table, $filtered);
    // 🔥 VALIDASI MAPPING AKUN
    $this->validateAkunMapping($table, $filtered);

    /* =====================================================
        8️⃣ SANITATION & AUDIT
        ===================================================== */
    $filtered = $this->sanitizer()->applySanitization($table, $filtered);
    $filtered = $this->injectAudit($filtered, 'insert');

    /* =====================================================
        9️⃣ HYBRID VALIDATION (SCHEMA + PROFILE)
        ===================================================== */
    $profile = $this->getProfileByTable($table);
    $errors  = $this->validate($filtered, $table, $profile);

    if (!empty($errors)) {
      return JsonResponse::error("Validation gagal", 422, $errors);
    }

    /* =====================================================
🔟 FINAL TRANSACTION (SAFE INSERT VERSION)
===================================================== */

    return $this->runTransaction(function () use ($table, $filtered, $request) {

      /* =========================================
    1️⃣ VALIDASI DUPLICATE DALAM TRANSACTION
    ========================================= */

      // penting untuk mencegah race condition
      $this->validateDuplicate($table, $filtered);

      /* =========================================
    2️⃣ INSERT DATA SECARA AMAN
    ========================================= */

      // menggunakan safe insert
      $id = $this->insertSafe($table, $filtered);
      $profile = $this->getProfileByTable($table);

      // =====================================================
      // RELATIONS
      // =====================================================
      // =====================================================
      // RELATIONS (FIX: pakai engine terpusat)
      // =====================================================
      $this->handleWriteRelations(
        $table,
        $id,
        $request,
        $profile
      );

      // =====================================================
      // COUNTER
      // =====================================================
      if (!empty($profile['counter'])) {

        $counter = $profile['counter'];
        $tahun   = date('Y');

        $row = $this->db->query(
          "SELECT {$counter['value_field']} 
     FROM {$counter['table']} 
     WHERE {$counter['tahun_field']} = ?",
          [$tahun]
        )->fetch();

        if ($row) {

          $this->db->update(
            $counter['table'],
            [$counter['value_field'] => $row[$counter['value_field']] + 1],
            "WHERE {$counter['tahun_field']} = ?",
            [$tahun]
          );
        } else {

          $this->db->insert(
            $counter['table'],
            [
              $counter['tahun_field'] => $tahun,
              $counter['value_field'] => 1
            ]
          );
        }
      }
      /* =========================================
    3️⃣ RESPONSE SUCCESS
    ========================================= */

      return JsonResponse::success(
        "Data berhasil disimpan",
        [
          'insert_id' => $id
        ]
      );
    });
  }
  /* ======================================================
REQUEST GUARD (ANTI DOUBLE SUBMIT)
--------------------------------------------------------
Mencegah form submit dua kali
====================================================== */
  private function guardDoubleSubmit(array $request): void
  {
    ksort($request); // stabilkan urutan key

    $fingerprint = md5(json_encode($request));

    $last = $_SESSION['_last_request'] ?? null;

    if ($last === $fingerprint) {

      throw new Exception("Duplicate request terdeteksi.");
    }

    $_SESSION['_last_request'] = $fingerprint;
  }
  /* =========================================================
UPDATE (FIXED STABLE VERSION v3.1)
========================================================= */
  private function update(string $table, array $request): string
  {
    $columns    = $this->getTableColumns($table);
    $primaryKey = $this->getPrimaryKey($table);

    $id = $request['id_row'] ?? null;

    // // 🔥 inject primary key ke filtered agar validateDuplicate bisa exclude self
    $primaryKey = $this->getPrimaryKey($table); // // ambil pk tabel
    $request[$primaryKey] = $id; // // inject ke request supaya masuk filtered

    if (!$id) {
      return JsonResponse::error("ID tidak ditemukan");
    }

    unset($request['id']);

    /* =====================================================
1️⃣ FILTER FIELD SESUAI KOLOM
===================================================== */
    $filtered = [];

    foreach ($request as $key => $value) {

      /* =========================================
IGNORE SYSTEM FIELD
========================================= */

      if (in_array($key, ['action', 'tbl', 'req'])) {
        continue;
      }

      if (in_array($key, $columns)) {
        $filtered[$key] = $value;
      }
      // // 🔥 inject primary key manual
      $primaryKey = $this->getPrimaryKey($table); // //
      $filtered[$primaryKey] = $id; // //
    }

    if (empty($filtered)) {
      return JsonResponse::error("Tidak ada data yang bisa diupdate");
    }

    /* =====================================================
2️⃣ NORMALISASI BOOLEAN & DATE
===================================================== */
    foreach ($filtered as $field => $value) {

      if ($value === 'on') {
        $filtered[$field] = 1;
      }

      if (
        is_string($value)
        && $value !== ''
        && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)
      ) {

        $parts = explode('/', $value);

        $filtered[$field] = sprintf(
          '%04d-%02d-%02d',
          $parts[2],
          $parts[1],
          $parts[0]
        );
      }
    }

    /* =====================================================
3️⃣ LOAD OLD DATA
===================================================== */
    $oldData = $this->db->query(
      "SELECT * FROM `$table` WHERE `$primaryKey` = ?",
      [$id]
    )->fetch();

    if (!$oldData) {
      return JsonResponse::error("Data tidak ditemukan");
    }
    // ======================================================
    // 🔥 CEK PERUBAHAN NOMOR + TANGGAL
    // ======================================================

    $profile = $this->getProfileByTable($table);

    $versioningResult = $this->handleVersioning(
      $table,
      $profile,
      $request,
      $filtered,
      $oldData,
      $primaryKey
    );

    if ($versioningResult !== null) {
      return $versioningResult;
    }
    /* =====================================================
4️⃣ AUTO FIELD RESOLUTION
===================================================== */
    $filtered = $this->resolveAutoFields($table, $filtered);
    $filtered = $this->normalizeDateTimeFields($table, $filtered);
    $filtered = $this->resolver()->resolvePeraturan($table, $filtered);
    $filtered = $this->resolver()->resolvePeriode($table, $filtered);

    /* =====================================================
        🔥 LOOKUP RESOLUTION
        Update juga harus resolve lookup
        agar field turunan ikut berubah
        ===================================================== */
    $profile = $this->getProfileByTable($table);
    $this->applyLookup($filtered, $profile);

    /* =====================================================
5️⃣ BUSINESS VALIDATION
===================================================== */
    $this->validateHierarchy($table, $filtered);
    // 🔥 VALIDASI MAPPING AKUN
    $this->validateAkunMapping($table, $filtered);
    /* =====================================================
6️⃣ SANITATION & AUDIT
===================================================== */
    $filtered = $this->sanitizer()->applySanitization($table, $filtered);
    $filtered = $this->injectAudit($filtered, 'update');

    /* =====================================================
7️⃣ PRESERVE REQUIRED FIELDS
===================================================== */
    foreach ($oldData as $field => $value) {
      if (!isset($filtered[$field])) {
        $filtered[$field] = $value;
      }
    }

    /* =====================================================
8️⃣ VALIDATION HYBRID
===================================================== */
    $profile = $this->getProfileByTable($table);
    $errors  = $this->validate($filtered, $table, $profile, $id);

    if (!empty($errors)) {
      return JsonResponse::error("Validation gagal", 422, $errors);
    }

    /* =====================================================
9️⃣ DIFF CHECK
===================================================== */
    $diff = [];
    // var_dump($filtered);
    // var_dump($oldData);

    foreach ($filtered as $key => $value) {

      // cek apakah field ada di data lama
      if (array_key_exists($key, $oldData)) {

        // bandingkan nilai lama dan nilai baru
        if ($oldData[$key] != $value) {
          $diff[$key] = $value;
        }
      }
    }
    // var_dump($diff);
    // exit;
    if (empty($diff)) {
      return JsonResponse::success("Tidak ada perubahan");
    }

    /* =====================================================
🔟 FINAL TRANSACTION
===================================================== */

    return $this->runTransaction(function () use ($table, $primaryKey, $id, $diff, $oldData, $request) {

      /* =========================================
UPDATE DATA DENGAN DUPLICATE HANDLER
========================================= */

      try {

        $this->db->update(
          $table,
          $diff,
          "WHERE `$primaryKey` = ?",
          [$id]
        );
        // =====================================================
        // TAMBAHAN: WRITE RELATIONS (CONFIG BASED)
        // =====================================================
        $profile = $this->getProfileByTable($table);

        // =====================================================
        // WRITE RELATIONS (FIX CENTRAL ENGINE)
        // =====================================================
        $this->handleWriteRelations(
          $table,
          $id,
          $request,
          $profile
        );
      } catch (PDOException $e) {

        // SQLSTATE duplicate
        if ($e->getCode() === '23000') {

          return JsonResponse::error(
            "Update gagal karena data duplicate."
          );
        }

        throw $e;
      }

      $this->logActivity($table, $id, 'update', $oldData, $diff);

      return JsonResponse::success("Data berhasil diupdate");
    });
  }
  /* =========================================================
DELETE (FULL IDENTIK LOGIC ASLI)
========================================================= */
  private function delete(string $table, array $profile, int|string $id): string
  {
    $primaryKey = $this->getPrimaryKey($table);

    if (!$this->checkAccess($table, $id)) {
      return JsonResponse::error("Tidak memiliki akses");
    }

    $oldData = $this->db->query(
      "SELECT * FROM `$table` WHERE `$primaryKey` = ?",
      [$id]
    )->fetch();

    return $this->runTransaction(function () use ($table, $primaryKey, $id, $oldData, $profile) {
      // FIX: tambahkan $profile ke closure

      $columns = $this->getTableColumns($table);

      if (in_array('deleted_at', $columns)) {

        $this->db->update(
          $table,
          [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $this->user['username'] ?? 'system'
          ],
          "WHERE `$primaryKey` = ?",
          [$id]
        );
      } else {
        // =====================================================
        // TAMBAHAN: DELETE RELATIONS
        // =====================================================

        $this->deleteRelations($table, $id, $profile);
        // =====================================================

        $this->db->delete(
          $table,
          "WHERE `$primaryKey` = ?",
          [$id]
        );
      }
      // var_dump($primaryKey);
      // die();
      $this->logActivity($table, $id, 'delete', $oldData, null);
      return JsonResponse::success("Data berhasil dihapus");
    });
  }

  /* =========================================================
    BUILD QUERY (LISTING + SEARCH + SCOPE FULL IDENTIK)
    ========================================================= */
  private function listing(
    string $table,      // nama tabel database yang akan dilisting
    array $profile,     // konfigurasi tabel dari file table_profiles.php
    array $request,     // data request dari frontend (pagination, search dll)
    string $mode        // mode listing (default / custom)
  ): string {

    /* ======================================================
        1️⃣ AMBIL KONFIGURASI MODE
        ====================================================== */

    // Ambil konfigurasi mode dari profile
    // Jika mode tidak ada gunakan 'default'
    $modeConfig = $profile['modes'][$mode]
      ?? $profile['modes']['default']
      ?? [];



    /* ======================================================
        2️⃣ PAGINATION
        ====================================================== */

    // jumlah data per halaman
    $limit = max(1, (int)($request['rows'] ?? 10));

    // halaman aktif
    $page = max(1, (int)($request['halaman'] ?? 1));

    // keyword pencarian
    $search = isset($request['cari']) && is_string($request['cari'])
      ? trim($request['cari'])
      : '';

    // offset SQL
    $offset = ($page - 1) * $limit;



    /* ======================================================
        3️⃣ RESOLVE USER SCOPE
        ====================================================== */

    // resolveScope() akan menambahkan filter otomatis
    // berdasarkan role user (admin_opd, admin_wilayah dll)
    list($scopeWhere, $scopeParams) =
      $this->resolveScope($table, $profile, $mode);



    /* ======================================================
        4️⃣ RESOLVE SEARCH
        ====================================================== */

    // resolveSearch() membangun query LIKE
    // berdasarkan kolom searchable
    list($searchWhere, $searchParams) =
      $this->resolveSearch($table, $modeConfig, $search);



    /* ======================================================
        5️⃣ INISIALISASI ARRAY WHERE
        ====================================================== */

    // array kondisi WHERE
    $whereParts = [];

    // parameter query
    $params = [];
    $req = $request['req'] ?? null;

    if ($req && $table === 'rekening_kegiatan') {

      $map = [
        'urusan' => 1,
        'bidang' => 2,
        'program' => 3,
        'kegiatan' => 5,
        'sub_kegiatan' => 6
      ];

      if (isset($map[$req])) {
        $whereParts[] = "level = ?";
        $params[] = $req;
        // $params[] = $map[$req];
      }
    }


    /* ======================================================
        6️⃣ APPLY PROFILE WHERE
        ====================================================== */

    // contoh profile:
    // 'where' => [
    //     'kd_wilayah' => 'user',
    //     'peraturan_id' => 'user'
    // ]

    // =======================================
    // AFTER (UPGRADE WHERE ENGINE)
    // =======================================

    if (!empty($modeConfig['where'])) {

      $columns = $this->getTableColumns($table);

      list($whereSql, $whereBind) =
        $this->meta()->buildWhere(
          $modeConfig['where'],
          $columns,
          $table,
          fn($field, $value, $table) =>
          $this->resolver()->resolveField($field, $value, $table, $this->user)
        );

      if ($whereSql) {
        $whereParts[] = $whereSql;
        $params = array_merge($params, $whereBind);
      }
    }



    /* ======================================================
        7️⃣ GABUNGKAN SEMUA WHERE
        ====================================================== */

    // gabungkan scope + search + profile where
    $whereParts = array_merge(
      $scopeWhere,
      $searchWhere,
      $whereParts
    );

    // gabungkan semua parameter query
    $params = array_merge(
      $scopeParams,
      $searchParams,
      $params
    );

    // bangun string WHERE SQL
    $where = !empty($whereParts)
      ? "WHERE " . implode(" AND ", $whereParts)
      : "";

    /* ======================================================
    8️⃣ SELECT FIELD
    ====================================================== */

    // ambil kolom select dari profile
    $select = implode(',', $modeConfig['select'] ?? ['*']);


    /* ======================================================
    8️⃣.1 BUILD JOIN QUERY
    ====================================================== */

    // default join kosong
    $joinSQL = "";

    // cek apakah profile memiliki konfigurasi join
    if (!empty($profile['join'])) {

      // loop semua join
      foreach ($profile['join'] as $join) {

        // nama tabel join
        $joinTable = $join['table'] ?? null;

        // kondisi ON join
        $joinOn = $join['on'] ?? null;

        // jika table dan kondisi ada
        if ($joinTable && $joinOn) {

          // tambahkan LEFT JOIN
          $joinSQL .= " LEFT JOIN `$joinTable` ON $joinOn ";
        }
      }
    }



    /* ======================================================
        9️⃣ PRIMARY KEY
        ====================================================== */

    // ambil primary key tabel
    $primaryKey = $this->getPrimaryKey($table);



    /* ======================================================
        🔟 ORDER BY
        ====================================================== */

    // ambil konfigurasi order_by dari profile
    $orderBy = $modeConfig['order_by'] ?? "`$primaryKey` DESC";

    // ambil semua kolom tabel
    $columns = $this->getTableColumns($table);

    // extract nama kolom dari order_by
    preg_match('/`?([a-zA-Z0-9_]+)`?/i', $orderBy, $match);

    $orderColumn = $match[1] ?? $primaryKey;

    // jika kolom tidak ada di tabel maka fallback
    if (!in_array($orderColumn, $columns)) {

      $orderBy = "`$primaryKey` DESC";
    }

    /* ======================================================
        1️⃣1️⃣ TOTAL DATA
        ====================================================== */

    // hitung total data untuk pagination
    $total = $this->db->query(

      "SELECT COUNT(*) as total
                FROM `$table`
                $joinSQL
                $where",

      $params

    )->fetch()['total'] ?? 0;



    /* ======================================================
        1️⃣2️⃣ AMBIL DATA
        ====================================================== */

    // query data utama
    $rows = $this->db->query(

      "SELECT $select
                FROM `$table`
                $joinSQL
                $where
                ORDER BY $orderBy
                LIMIT $offset, $limit",

      $params

    )->fetchAll();



    /* ======================================================
        1️⃣3️⃣ RESPONSE JSON
        ====================================================== */

    return JsonResponse::success(

      "Data berhasil ditampilkan",

      [
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'primary_key' => $primaryKey
      ],

      $rows
    );
  }
  // =======================================
  // FUNCTION: APPLY WHERE RECURSIVE
  // =======================================
  private function applyWhere($query, $conditions)
  {
    foreach ($conditions as $key => $value) {

      // ===============================
      // CASE 1: LOGIC GROUP (AND / OR)
      // ===============================
      if ($key === 'AND' && is_array($value)) {

        $query->where(function ($q) use ($value) {
          foreach ($value as $cond) {
            $this->applyWhere($q, $cond); // recursive
          }
        });
      } elseif ($key === 'OR' && is_array($value)) {

        $query->where(function ($q) use ($value) {
          foreach ($value as $cond) {
            $q->orWhere(function ($sub) use ($cond) {
              $this->applyWhere($sub, $cond); // recursive OR
            });
          }
        });
      }

      // ===============================
      // CASE 2: OPERATOR CUSTOM
      // ===============================
      elseif (strpos($key, ' ') !== false) {

        list($col, $op) = explode(' ', $key, 2);

        $query->where($col, $op, $value); // support > < LIKE
      }

      // ===============================
      // CASE 3: NORMAL WHERE
      // ===============================
      else {

        $query->where($key, $value); // default AND
      }
    }
  }
  /* =========================================================
GET ALL RAW DATA (UNTUK EXPORT / REPORT)
========================================================= */
  private function getAllRaw(
    string $table,
    array $profile,
    array $request,
    string $mode
  ): array {

    $modeConfig = $profile['modes'][$mode]
      ?? $profile['modes']['default']
      ?? [];

    $select = $modeConfig['select'] ?? ['*'];
    $selectClause = implode(',', $select);

    list($userWhere, $userParams) = $this->applyUserScope($table);

    $where = !empty($userWhere)
      ? 'WHERE ' . implode(' AND ', $userWhere)
      : '';

    $primaryKey = $this->getPrimaryKey($table);

    $query = "
        SELECT $selectClause
        FROM `$table`
        $where
        ORDER BY `$primaryKey` DESC
    ";

    return $this->db->query($query, $userParams)->fetchAll();
  }

  /* =========================================================
EXPORT ENGINE
========================================================= */
  private function export(
    string $table,
    array $profile,
    array $request,
    string $mode
  ): string {

    $this->authorize('view', $table);

    $rows = $this->getAllRaw($table, $profile, $request, $mode);

    // 🔥 Jika kosong tetap success tapi beri pesan
    if (empty($rows)) {
      return JsonResponse::success(
        "Data kosong",
        [
          'total' => 0
        ],
        []
      );
    }

    return JsonResponse::success(
      "Data export berhasil",
      [
        'total' => count($rows)
      ],
      $rows
    );
  }

  /* =========================================================
APPLY USER SCOPE (ROLE AWARE FULL IDENTIK)
========================================================= */
  /* =========================================================
APPLY USER SCOPE (LOGIC TIDAK DIUBAH)
HANYA PERIODE AKTIF DI-CACHE
========================================================= */
  private function applyUserScope(string $table): array
  {
    $role = $this->user['type_user'] ?? 'viewer';

    if ($role === 'super_admin') {
      return [[], []];
    }

    $columns = $this->getTableColumns($table);

    $whereParts = [];
    $params     = [];

    if ($role === 'admin_wilayah') {

      if (in_array('kd_wilayah', $columns)) {
        $whereParts[] = "`kd_wilayah` = ?";
        $params[] = $this->user['kd_wilayah'] ?? null;
      }
    }

    if ($role === 'admin_opd') {

      if (in_array('kd_opd', $columns)) {
        $whereParts[] = "`kd_opd` = ?";
        $params[] = $this->user['kd_opd'] ?? null;
      }

      if (in_array('kd_wilayah', $columns)) {
        $whereParts[] = "`kd_wilayah` = ?";
        $params[] = $this->user['kd_wilayah'] ?? null;
      }

      if (in_array('tahun', $columns) && isset($this->user['tahun'])) {
        $whereParts[] = "`tahun` = ?";
        $params[] = $this->user['tahun'];
      }

      if (in_array('periode_id', $columns)) {

        $periodeAktif = $this->getPeriodeAktif();

        if ($periodeAktif) {
          $whereParts[] = "`periode_id` = ?";
          $params[] = $periodeAktif['id'];
        }
      }
    }

    return [$whereParts, $params];
  }

  /* =========================================================
CACHE OPTIMIZATION SECTION
========================================================= */

  private function getTableColumns(string $table): array
  {
    if (isset(self::$columnCache[$table])) {
      return self::$columnCache[$table];
    }

    /* =========================================================
        AMBIL KOLOM TABEL DARI DATABASE
        ========================================================= */

    $stmt = $this->db->query("
    SELECT COLUMN_NAME AS Field
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = ?
", [$table]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* =========================================================
JIKA KOLOM TIDAK DITEMUKAN
========================================================= */

    if (empty($rows)) {

      throw new Exception(
        "Kolom tabel `$table` tidak ditemukan pada database aktif."
      );
    }

    /* =========================================================
AMBIL FIELD NAME
========================================================= */

    self::$columnCache[$table] = array_column($rows, 'Field');

    return self::$columnCache[$table];
  }

  /* =========================================================
HYBRID VALIDATION ENGINE (FULL IDENTIK)
========================================================= */
  private function validate(array $data, string $table, array $profile, $currentId = null): array
  {
    $errors = [];

    $customRules = $profile['validation'] ?? [];
    $schemaRules = $this->buildRulesFromSchema($table);

    $rules = array_merge($schemaRules, $customRules);

    foreach ($rules as $field => $fieldRules) {

      $value = $data[$field] ?? null;

      foreach ($fieldRules as $rule) {

        if ($rule === 'required' && ($value === null || $value === '')) {
          $errors[$field] = "$field wajib diisi";
        }

        if ($rule === 'numeric' && !empty($value) && !is_numeric($value)) {
          $errors[$field] = "$field harus berupa angka";
        }

        if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $errors[$field] = "$field tidak valid";
        }
        //rule validasi
        if ($rule === 'unique' && ($value !== null && $value !== '')) {

          $primaryKey = $this->getPrimaryKey($table);

          if ($currentId) {
            $exists = $this->db->query(
              "SELECT $primaryKey FROM `$table` 
                        WHERE `$field` = ? 
                        AND `$primaryKey` != ? 
                        LIMIT 1",
              [$value, $currentId]
            )->fetch();
          } else {
            $exists = $this->db->query(
              "SELECT $primaryKey FROM `$table` 
                        WHERE `$field` = ? 
                        LIMIT 1",
              [$value]
            )->fetch();
          }

          if ($exists) {
            $errors[$field] = "$field sudah digunakan";
          }
        }
        if (str_starts_with($rule, 'max:') && !empty($value)) {

          $max = (int)explode(':', $rule)[1];

          if (strlen($value) > $max) {
            $errors[$field] = "$field maksimal $max karakter";
          }
        }
      }
    }

    return $errors;
  }

  /* =========================================================
BUILD RULE DARI SCHEMA DATABASE
========================================================= */
  private function buildRulesFromSchema(string $table): array
  {
    if (isset(self::$schemaCache[$table])) {
      return self::$schemaCache[$table];
    }

    $rules = [];
    $columns = $this->db->query("SHOW COLUMNS FROM `$table`")->fetchAll();

    foreach ($columns as $col) {

      $field = $col['Field'];
      $type  = $col['Type'];
      $null  = $col['Null'];

      $fieldRules = [];

      if ($null === 'NO' && $field !== 'id') {
        $fieldRules[] = 'required';
      }

      if (str_contains($type, 'int') || str_contains($type, 'decimal')) {
        $fieldRules[] = 'numeric';
      }

      if (preg_match('/varchar\((\d+)\)/', $type, $match)) {
        $fieldRules[] = 'max:' . $match[1];
      }

      if (!empty($fieldRules)) {
        $rules[$field] = $fieldRules;
      }
    }

    self::$schemaCache[$table] = $rules;

    return $rules;
  }
  /**
   * ======================================================
   * LOAD DROPDOWN ENGINE (FINAL STABLE ARCHITECTURE)
   * ======================================================
   *
   * fitur:
   * - membaca profile dropdown
   * - membaca relations parent dari profile
   * - filter scope profile
   * - search dropdown
   * - pivot filter ssh/sbu/asb/hspk
   * - limit dropdown
   * - memastikan value edit tetap muncul
   * - filter disable otomatis
   */

  // ======================================================
  // ROUTER DROPDOWN
  // ======================================================

  // ======================================================
  // ROUTER DROPDOWN
  // ======================================================

  private function loadDropdown($profileKey, $parentValue = null, $kdAkun = null)
  {
    if ($parentValue === null) {

      if (isset($_POST['parent_value'])) {
        $parentValue = $_POST['parent_value'];
      }

      /* fallback untuk sistem SPA yang kirim value */ elseif (isset($_POST['parent'])) {
        $parentValue = $_POST['parent'];
      } elseif (isset($_POST['value']) && $_POST['value'] !== '') {
        $parentValue = $_POST['value'];
      }
    }
    // =====================================================
    // HIERARCHY ENGINE UNTUK SIPD
    // =====================================================
    $req = $_POST['req'] ?? null;

    // 🔥 FIX AMAN
    if ($req === null && isset($_POST['filters'])) {
      $decoded = json_decode($_POST['filters'], true);

      if (isset($decoded['level'])) {
        $req = $decoded['level'];
      }
    }
    $filters = $_POST['filters'] ?? null; // ✅ WAJIB // // jangan overwrite
    $req     = $_POST['req'] ?? $req;         // //
    // ======================================================
    // 🔥 TAMBAHAN MODE + ID + SEARCH
    // ======================================================
    $mode = $_POST['mode'] ?? null;
    $id   = $_POST['id'] ?? null;

    // 🔥 NORMALISASI
    if (!$id) {
      $mode = null;
    }
    $search = $_POST['search'] ?? null; // // keyword search
    $limit  = $_POST['limit'] ?? 20; // // default limit
    // ======================================================
    // 🔥 MODE EDIT → AMBIL WINDOW DATA BERDASARKAN ID
    // ======================================================
    if ($mode === 'edit' && !empty($id)) { // // 🔥 hanya edit mode yang boleh masuk

      $primaryKey = $this->getPrimaryKey($profileKey);

      $rows = $this->db->query(
        "SELECT * FROM `$profileKey`
     WHERE `$primaryKey` BETWEEN ? AND ?
     ORDER BY `$primaryKey` ASC",
        [$id - 2, $id + 2]
      )->fetchAll();

      return JsonResponse::success(
        "Dropdown loaded (edit window)",
        [],
        $rows
      );
    }
    if ($filters && $mode !== 'edit') { // // edit tidak boleh bypass
      return $this->loadDropdownGeneric($profileKey, null, $filters);
    }

    if ($parentValue !== null && $parentValue !== '') {
      return $this->loadDropdownHierarchy($parentValue, $req);
    }

    // =====================================================
    // GENERIC DROPDOWN ENGINE
    // =====================================================

    return $this->loadDropdownGeneric($profileKey, $parentValue);
  }

  /* =========================================================
  UTIL: GET PROFILE BY TABLE
  ========================================================= */
  public function getProfileByTable(string $table): array
  {
    foreach ($this->profiles as $profile) {
      if (($profile['table'] ?? '') === $table) {
        return $profile;
      }
    }
    return [];
  }

  /* =========================================================
UTIL: GET PRIMARY KEY
========================================================= */
  private function getPrimaryKey(string $table): string
  {
    $profile = $this->getProfileByTable($table);
    return $profile['primary_key'] ?? 'id';
  }

  /* =========================================================
UTIL: CHECK ACCESS WITH SCOPE
========================================================= */
  private function checkAccess(string $table, $id): bool
  {
    $primaryKey = $this->getPrimaryKey($table);

    list($scopeWhere, $scopeParams) = $this->applyUserScope($table);

    $whereParts = array_merge(["`$primaryKey` = ?"], $scopeWhere);
    $params     = array_merge([$id], $scopeParams);

    $where = "WHERE " . implode(" AND ", $whereParts);

    $row = $this->db->query(
      "SELECT `$primaryKey` FROM `$table` $where LIMIT 1",
      $params
    )->fetch();

    return (bool)$row;
  }
  private function logActivity(
    string $table,
    int|string $recordId,
    string $action,
    $oldData = null,
    $newData = null
  ): void {
    try {

      // =====================================
      // 🔥 SAFE JSON (ANTI CRASH)
      // =====================================
      $oldJson = $this->safeJson($oldData);
      $newJson = $this->safeJson($newData);

      // =====================================
      // 🔥 LIMIT SIZE (ANTI DB ERROR)
      // =====================================
      if ($oldJson && strlen($oldJson) > 10000) {
        $oldJson = substr($oldJson, 0, 10000) . '...[TRUNCATED]';
      }

      if ($newJson && strlen($newJson) > 10000) {
        $newJson = substr($newJson, 0, 10000) . '...[TRUNCATED]';
      }

      // =====================================
      // 🔥 INSERT (PASTI AMAN)
      // =====================================
      $this->db->insert('log_activity', [
        'table_name' => $table,
        'record_id'  => $recordId,
        'action'     => $action,
        'old_data'   => $oldJson,
        'new_data'   => $newJson,
        'username'   => $this->user['username'] ?? 'system',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
      ]);
    } catch (Throwable $e) {

      // =====================================
      // 🔥 HARD FAIL-SAFE (JANGAN GANGGU FLOW UTAMA)
      // =====================================
      error_log("LOG ACTIVITY ERROR: " . $e->getMessage());
    }
  }
  /* =========================================================
UTIL: CEK APAKAH TABEL ADA
========================================================= */
  private function tableExists(string $table): bool
  {
    $result = $this->db->query(
      "SHOW TABLES LIKE ?",
      [$table]
    )->fetch();

    return (bool)$result;
  }
  /* =========================================================
TRANSACTION WRAPPER (FIXED VERSION)
---------------------------------------------------------
Perbaikan:
1. Menggunakan PDO native transaction API
2. Mencegah nested transaction error
3. Lebih aman untuk concurrency
========================================================= */
  private function runTransaction(callable $callback)
  {
    try {

      /* =========================================
        1️⃣ MULAI TRANSACTION PDO
        ========================================= */

      // gunakan PDO beginTransaction
      // lebih aman dibanding START TRANSACTION SQL
      $this->db->begin();

      /* =========================================
        2️⃣ JALANKAN LOGIC CALLBACK
        ========================================= */

      // callback biasanya berisi insert/update/delete
      $result = $callback();

      /* =========================================
        3️⃣ COMMIT TRANSACTION
        ========================================= */

      // jika tidak ada exception
      $this->db->commit();

      // kembalikan hasil callback
      return $result;
    } catch (\Throwable $e) {

      /* =========================================
        4️⃣ ROLLBACK JIKA ERROR
        ========================================= */

      // batalkan semua query dalam transaction
      $this->db->rollback();

      // lempar kembali error agar layer atas tahu
      throw $e;
    }
  }
  /* =========================================================
SAFE INSERT ENGINE
---------------------------------------------------------
Menangkap duplicate key error dari MySQL
SQLSTATE 23000
========================================================= */
  private function insertSafe(string $table, array $data)
  {
    try {

      /* =========================================
        1️⃣ LAKUKAN INSERT NORMAL
        ========================================= */

      $this->db->insert($table, $data);

      /* =========================================
        2️⃣ RETURN LAST INSERT ID
        ========================================= */

      return $this->db->lastInsertId();
    } catch (PDOException $e) {

      /* =========================================
        3️⃣ DETEKSI DUPLICATE KEY
        ========================================= */

      // SQLSTATE 23000 = duplicate constraint
      if ($e->getCode() === '23000') {

        throw new Exception(
          "Data sudah ada (duplicate key constraint)."
        );
      }

      // lempar error lain
      throw $e;
    }
  }

  private function getPeriodeAktif(): ?array
  {
    if ($this->periodeAktifCache !== null) {
      return $this->periodeAktifCache;
    }

    $kd_wilayah = $this->user['kd_wilayah'] ?? null;

    if (!$kd_wilayah) {
      return null;
    }

    $this->periodeAktifCache = $this->db->query("
        SELECT id
        FROM periode_rpjmd
        WHERE kd_wilayah = ?
        AND status_aktif = 1
        LIMIT 1
    ", [$kd_wilayah])->fetch();

    return $this->periodeAktifCache;
  }
  /* =========================================================
NORMALIZE HEADER XLSX → snake_case
========================================================= */
  private function normalizeHeader(?string $header): string
  {
    if ($header === null) {
      return '';
    }

    $header = trim($header);

    if ($header === '') {
      return '';
    }

    return strtolower(
      preg_replace('/[^a-z0-9_]/', '', $header)
    );
  }
  /* =========================================================
VALIDASI IMPORT PERMISSION
========================================================= */
  private function validateImportPermission(string $table): void
  {
    $role = $this->user['type_user'] ?? 'viewer';

    $restricted = [
      'urusan',
      'bidang',
      'program',
      'kegiatan',
      'sub_kegiatan'
    ];

    if ($role === 'admin_opd' && in_array($table, $restricted)) {
      throw new Exception("Admin OPD tidak diperbolehkan import tabel master.");
    }
  }
  // ======================================================
  // VALIDASI DUPLICATE BERDASARKAN RULE PROFILE
  // - 'not_duplicate' => ['kode','kd_wilayah','peraturan_id']
  // ======================================================
  private function validateDuplicate(string $table, array $data): void
  {
    // ambil konfigurasi profile tabel
    $profile = $this->getProfileByTable($table);

    // jika tabel tidak memiliki rule duplicate
    if (empty($profile['not_duplicate'])) {

      // maka tidak dilakukan validasi
      return;
    }

    // ambil daftar field yang tidak boleh duplicate
    $fields = $profile['not_duplicate'];

    // array untuk menyimpan kondisi WHERE
    $whereParts = [];

    // parameter query
    $params = [];

    // loop semua field rule duplicate
    foreach ($fields as $field) {

      // jika field tidak ada pada data
      if (!isset($data[$field])) {

        // lempar error
        throw new Exception(
          "Field {$field} wajib ada untuk validasi duplicate."
        );
      }

      // tambahkan kondisi where
      $whereParts[] = "`$field` = ?";

      // tambahkan parameter
      $params[] = $data[$field];
    }

    // ambil primary key tabel
    $primaryKey = $this->getPrimaryKey($table);

    // jalankan query cek duplicate
    // ==========================================================
    // VALIDASI DUPLICATE DENGAN ROW LOCK
    // ----------------------------------------------------------
    // menggunakan SELECT ... FOR UPDATE agar request paralel
    // tidak dapat melewati validasi duplicate secara bersamaan
    // ==========================================================

    // // jika ada id_row (edit mode), exclude row itu sendiri
    if (!empty($data[$primaryKey])) {

      $whereParts[] = "`$primaryKey` != ?"; // // exclude current row
      $params[] = $data[$primaryKey]; // // ambil id dari request
    }

    // // jalankan query duplicate
    $exists = $this->db->query(
      "SELECT `$primaryKey`
   FROM `$table`
   WHERE " . implode(" AND ", $whereParts) . "
   LIMIT 1
   FOR UPDATE",
      $params
    )->fetch();

    // // hanya error jika benar-benar row lain
    if ($exists) {
      throw new Exception(
        "Duplicate data terdeteksi pada kombinasi: "
          . implode(', ', $fields)
      );
    }
  }
  /* =========================================================
VALIDASI HIERARKI & DEPENDENSI LINTAS TABEL
-----------------------------------------------------
Tujuan:
- Memastikan parent record benar-benar ada
- Mencegah orphan record
- Memastikan scope wilayah + peraturan konsisten
========================================================= */
  private function validateHierarchy(string $table, array $data): void
  {
    /* ======================================================
1️⃣ DEFINISI RULE HIERARKI
------------------------------------------------------
menentukan parent table dan cara validasi relasinya
====================================================== */
    $rules = [

      // Struktur SIPD nasional
      'bidang' => [
        'parent_table' => 'urusan',
        'match' => ['urusan_id' => 'id']
      ],

      'program' => [
        'parent_table' => 'bidang',
        'match' => ['bidang_id' => 'id']
      ],

      'kegiatan' => [
        'parent_table' => 'program',
        'match' => ['program_id' => 'id']
      ],

      'sub_kegiatan' => [
        'parent_table' => 'kegiatan',
        'match' => ['kode_kegiatan' => 'kode']
      ],

      // Renstra
      'renstra_neo' => [
        'parent_table' => 'periode_rpjmd',
        'match' => ['periode_id' => 'id']
      ],

      // Renja
      'renja_neo' => [
        'parent_table' => 'renstra_neo',
        'match_scope' => ['tahun', 'kd_opd', 'kd_wilayah']
      ],

      'renja_p_neo' => [
        'parent_table' => 'renja_neo',
        'match_scope' => ['tahun', 'kd_opd', 'kd_wilayah']
      ],

      // DPA
      'dpa_neo' => [
        'parent_table' => 'renja_neo',
        'match_scope' => ['tahun', 'kd_opd', 'kd_wilayah']
      ],

      'dpppa_neo' => [
        'parent_table' => 'dpa_neo',
        'match_scope' => ['tahun', 'kd_opd', 'kd_wilayah']
      ],
    ];

    /* ======================================================
2️⃣ JIKA TABEL TIDAK PUNYA RULE → SKIP
====================================================== */
    if (!isset($rules[$table])) {
      return;
    }

    $rule   = $rules[$table];
    $parent = $rule['parent_table'];

    /* ======================================================
3️⃣ VALIDASI FOREIGN KEY MATCH
------------------------------------------------------
contoh:
program.bidang_id harus ada di tabel bidang
====================================================== */
    if (isset($rule['match'])) {

      foreach ($rule['match'] as $childField => $parentField) {

        // field child wajib ada
        if (empty($data[$childField])) {
          throw new Exception("Field $childField wajib diisi.");
        }

        // ambil kolom parent table
        $parentColumns = $this->getTableColumns($parent);

        // base query
        $where  = ["`$parentField` = ?"];
        $params = [$data[$childField]];

        /* ==================================================
🔥 Scope wilayah
================================================== */
        if (in_array('kd_wilayah', $parentColumns)) {

          $where[]  = "`kd_wilayah` = ?";
          $params[] = $data['kd_wilayah']
            ?? $this->user['kd_wilayah']
            ?? null;
        }

        /* ==================================================
🔥 Scope peraturan (VERSI BARU)
================================================== */
        if (in_array('peraturan_id', $parentColumns)) {

          $where[]  = "`peraturan_id` = ?";
          $params[] = $data['peraturan_id'] ?? null;
        }

        /* ==================================================
4️⃣ CEK PARENT ADA ATAU TIDAK
================================================== */
        $exists = $this->db->query(
          "SELECT id FROM `$parent`
WHERE " . implode(" AND ", $where) . "
LIMIT 1",
          $params
        )->fetch();

        if (!$exists) {
          throw new Exception(
            "Parent di tabel $parent belum tersedia."
          );
        }
      }
    }

    /* ======================================================
5️⃣ VALIDASI SCOPE MATCH
------------------------------------------------------
digunakan pada relasi non-FK
contoh:
renja harus sesuai tahun + opd + wilayah
====================================================== */
    if (isset($rule['match_scope'])) {

      $where  = [];
      $params = [];

      foreach ($rule['match_scope'] as $field) {

        if (!isset($data[$field])) {
          throw new Exception("Field $field wajib ada.");
        }

        $where[]  = "`$field` = ?";
        $params[] = $data[$field];
      }

      // ambil kolom parent
      $parentColumns = $this->getTableColumns($parent);

      /* ==================================================
🔥 Scope peraturan versi baru
================================================== */
      if (
        in_array('peraturan_id', $parentColumns)
        && isset($data['peraturan_id'])
      ) {

        $where[]  = "`peraturan_id` = ?";
        $params[] = $data['peraturan_id'];
      }

      /* ==================================================
CEK EXIST
================================================== */
      $exists = $this->db->query(
        "SELECT id FROM `$parent`
WHERE " . implode(" AND ", $where) . "
LIMIT 1",
        $params
      )->fetch();

      if (!$exists) {
        throw new Exception(
          "Parent scope di $parent belum tersedia."
        );
      }
    }
  }
  /**
   * ============================================================
   * IMPORT STRICT ENGINE
   * ------------------------------------------------------------
   * Fungsi:
   * Mengimport data Excel ke tabel sistem secara aman.
   *
   * Fitur utama:
   * - Header validation
   * - Relation resolver
   * - Scope injection (wilayah, tahun, peraturan)
   * - Sanitasi data
   * - Duplicate validation
   * - Transaction safe
   * - Error report per baris
   *
   * Tabel yang menggunakan metode ini:
   * SSH
   * SBU
   * ASB
   * HSPK
   * ============================================================
   */
  public function importStrict($tbl, $file, $jmlHeader = 1)
  {

    // ======================================================
    // 1️⃣ VALIDASI PROFILE TABEL
    // ======================================================

    if (!isset($this->profiles[$tbl])) {
      throw new Exception("Profile tabel tidak ditemukan.");
    }

    // ambil konfigurasi tabel dari profile
    $profile = $this->profiles[$tbl];

    // ambil nama tabel database
    $table = $profile['table'];


    // ======================================================
    // 2️⃣ AMBIL KOLOM TABEL DATABASE
    // ======================================================

    $columns = $this->getTableColumns($table);


    // ======================================================
    // 3️⃣ AMBIL SCOPE USER
    // ======================================================

    // wilayah user
    $kd_wilayah = $this->user['kd_wilayah'] ?? null;

    // tahun user
    $tahun = $this->user['tahun'] ?? date('Y');

    if (!$kd_wilayah) {
      throw new Exception("kd_wilayah tidak ditemukan.");
    }


    // ======================================================
    // 4️⃣ RESOLVE PERATURAN
    // ======================================================

    $peraturan_id = null;

    if (in_array('peraturan_id', $columns)) {
      $peraturan_id = $this->resolver()->resolvePeraturanId($table);
    }


    // ======================================================
    // 5️⃣ LOAD FILE EXCEL
    // ======================================================

    // buat reader otomatis
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);

    // baca hanya data (lebih cepat)
    $reader->setReadDataOnly(true);

    // load spreadsheet
    $spreadsheet = $reader->load($file);

    // ambil sheet aktif
    $sheet = $spreadsheet->getActiveSheet();

    // iterator baris Excel
    $rowIterator = $sheet->getRowIterator();


    // ======================================================
    // 6️⃣ BUILD COLUMN MAP
    // ======================================================

    // mapping header Excel → kolom SQL
    $columnMap = $this->buildColumnMap($table);


    // ======================================================
    // 7️⃣ VARIABLE STATISTIK IMPORT
    // ======================================================

    $headers = [];

    $totalRows = 0;
    $successRows = 0;
    $failedRows = 0;

    $errorRows = [];


    // ======================================================
    // 8️⃣ IMPORT DALAM TRANSACTION
    // ======================================================

    return $this->runTransaction(function () use (

      $rowIterator,
      $jmlHeader,
      $columnMap,
      $columns,
      $table,
      $kd_wilayah,
      $tahun,
      $peraturan_id,
      &$headers,
      &$totalRows,
      &$successRows,
      &$failedRows,
      &$errorRows

    ) {

      $rowNumber = 0;

      // ==================================================
      // LOOP SEMUA BARIS EXCEL
      // ==================================================

      foreach ($rowIterator as $row) {

        $rowNumber++;

        // iterator cell
        $cellIterator = $row->getCellIterator();

        // baca semua cell
        $cellIterator->setIterateOnlyExistingCells(false);

        $values = [];

        foreach ($cellIterator as $cell) {
          $values[] = trim((string)$cell->getValue());
        }
        //===============================================
        // HEADER PROCESSING
        // ===============================================

        if ($rowNumber <= $jmlHeader) {

          // header terakhir
          if ($rowNumber === $jmlHeader) {
            foreach ($values as $h) {
              if ($h === '') {
                continue;
              }
              // normalisasi header
              $normalized = $this->importHelper->normalizeForCompare($h);
              // cek apakah cocok dengan kolom tabel
              if (!isset($columnMap[$normalized])) {
                throw new Exception(
                  "Header Excel '{$h}' tidak cocok dengan kolom tabel."
                );
              }
              // simpan mapping header
              $headers[] = $columnMap[$normalized];
            }
          }

          continue;
        }


        // ==================================================
        // BARIS DATA
        // ==================================================

        $totalRows++;

        $data = [];

        foreach ($values as $k => $v) {

          if (!isset($headers[$k])) {
            continue;
          }

          $data[$headers[$k]] = $v;
        }
        try {
          // ==================================================
          // INJECT SCOPE
          // ==================================================
          if (in_array('kd_wilayah', $columns)) {
            $data['kd_wilayah'] = $kd_wilayah;
          }

          if (in_array('tahun', $columns)) {
            $data['tahun'] = $tahun;
          }

          // ======================================================
          // tambahkan peraturan_id otomatis jika ada mapping
          // ======================================================

          if (in_array('peraturan_id', $columns)) {
            $data['peraturan_id'] = $peraturan_id;
          }


          // ==================================================
          // RESOLVE RELATIONS
          // ==================================================

          $profile = $this->profiles[$table] ?? [];

          $relations = $profile['import_relations'] ?? [];

          if (!empty($relations)) {

            $data = $this->resolveImportRelations(
              $data,
              $relations,
              $rowNumber
            );
          }

          // ==================================================
          // AUTO DETECT LEVEL UNTUK rekening_kegiatan
          // ==================================================

          if ($table === 'rekening_kegiatan' && !empty($data['kode'])) {

            $segments = explode('.', $data['kode']);
            $count = count($segments);

            $levelMap = [
              1 => 'urusan',
              2 => 'bidang',
              3 => 'program',
              5 => 'kegiatan',
              6 => 'sub_kegiatan'
            ];

            if (!isset($levelMap[$count])) {
              continue;
            }

            $data['level'] = $levelMap[$count];

            // set parent_kode otomatis
            if ($count > 1) {
              $data['parent_kode'] = substr(
                $data['kode'],
                0,
                strrpos($data['kode'], '.')
              );
            }
          }
          // ==================================================
          // SANITASI DATA
          // ==================================================

          $data = $this->sanitizer()->applySanitization($table, $data);


          // ==================================================
          // AUDIT TRAIL
          // ==================================================

          $data = $this->injectAudit($data, 'insert');

          // ======================================================
          // CEK DUPLICATE DALAM FILE EXCEL
          // ======================================================

          $profile = $this->getProfileByTable($table);

          if (!empty($profile['not_duplicate'])) {

            $keyParts = [];

            foreach ($profile['not_duplicate'] as $field) {

              $keyParts[] = $data[$field] ?? '';
            }

            $duplicateKey = implode('|', $keyParts);

            if (isset($duplicateMemory[$duplicateKey])) {

              throw new Exception(
                "Duplicate antar baris Excel terdeteksi."
              );
            }

            $duplicateMemory[$duplicateKey] = true;
          }

          // ======================================================
          // VALIDASI DUPLICATE DATABASE
          // ======================================================

          $this->validateDuplicate($table, $data);


          // ==================================================
          // INSERT DATABASE
          // ==================================================

          $this->db->insert($table, $data);

          $successRows++;
        } catch (\Throwable $e) {

          $failedRows++;

          $errorRows[] = [
            'row' => $rowNumber,
            'message' => $e->getMessage()
          ];
        }
      }


      // ==================================================
      // GROUP ERROR
      // ==================================================

      $groupedErrors = $this->importHelper->groupImportErrors($errorRows); // //


      // ==================================================
      // RETURN RESULT
      // ==================================================

      return JsonResponse::success(
        "Import selesai",
        [
          'total' => $totalRows,
          'berhasil' => $successRows,
          'gagal' => $failedRows
        ],
        $groupedErrors
      );
    });
  }

  private function groupImportErrors(array $errors): array
  {
    $grouped = [];

    foreach ($errors as $err) {

      $msg = $err['message'];

      if (!isset($grouped[$msg])) {
        $grouped[$msg] = [
          'message' => $msg,
          'rows' => []
        ];
      }

      $grouped[$msg]['rows'][] = $err['row'];
    }

    foreach ($grouped as &$g) {
      $g['rows'] = $this->importHelper->compressRowRanges($g['rows']); // //
    }

    return array_values($grouped);
  }
  /* =========================================================
        IMPORT STRUKTUR NASIONAL (GLOBAL HIRARKI)
        ========================================================= */
  public function importStruktur(string $filePath, int $jmlHeader = 1): string
  {
    return $this->runTransaction(function () use ($filePath, $jmlHeader) {

      $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

      $rows = $spreadsheet
        ->getActiveSheet()
        ->toArray(null, true, true, false);

      if (count($rows) <= $jmlHeader) {
        throw new Exception("File kosong atau header tidak sesuai.");
      }

      $inserted = 0;

      /* =====================================================
        CACHE SEMUA KODE YANG SUDAH ADA DI DATABASE
        ===================================================== */

      $kodeCache = [];

      $existing = $this->db->query("
            SELECT kode
            FROM rekening_kegiatan
        ")->fetchAll();

      foreach ($existing as $r) {
        $kodeCache[$r['kode']] = true;
      }

      /* =====================================================
        MEMORY DUPLICATE EXCEL
        ===================================================== */

      $excelDuplicate = [];

      /* =====================================================
        LOOP DATA EXCEL
        ===================================================== */

      foreach (array_slice($rows, $jmlHeader) as $rowIndex => $row) {

        $excelRow = $rowIndex + $jmlHeader + 1;

        $kode   = trim((string)($row[0] ?? ''));
        $uraian = trim((string)($row[1] ?? ''));

        if ($kode === '' || $uraian === '') {
          continue;
        }

        try {

          /* =====================================================
                CEK DUPLICATE DALAM FILE
                ===================================================== */

          if (isset($excelDuplicate[$kode])) {

            throw new Exception(
              "Duplicate kode {$kode} dalam Excel."
            );
          }

          $excelDuplicate[$kode] = true;

          /* =====================================================
                DETEKSI LEVEL BERDASARKAN SEGMENT
                ===================================================== */

          $segments = explode('.', $kode);
          $count    = count($segments);

          $level = match ($count) {

            1 => 'urusan',
            2 => 'bidang',
            3 => 'program',
            5 => 'kegiatan',
            6 => 'sub_kegiatan',
            default => null
          };

          if (!$level) {
            continue;
          }

          /* =====================================================
                DETEKSI PARENT
                ===================================================== */

          $parent = null;

          if (str_contains($kode, '.')) {

            $parent = substr($kode, 0, strrpos($kode, '.'));

            /* ===============================================
                    VALIDASI PARENT ADA
                    =============================================== */

            if (!isset($kodeCache[$parent])) {

              throw new Exception(
                "Parent {$parent} belum ada untuk kode {$kode}"
              );
            }
          }

          /* =====================================================
                INSERT DATA
                ===================================================== */

          $this->insertIfNotExists(
            'rekening_kegiatan',
            [
              'kode' => $kode,
              'parent_kode' => $parent,
              'level' => $level,
              'uraian' => $uraian
            ]
          );

          /* =====================================================
                UPDATE CACHE
                ===================================================== */

          $kodeCache[$kode] = true;

          $inserted++;
        } catch (\Throwable $e) {

          throw new Exception(
            "Baris Excel {$excelRow} gagal → " . $e->getMessage()
          );
        }
      }

      return JsonResponse::success(
        "Import struktur berhasil",
        [
          'inserted' => $inserted
        ]
      );
    });
  }
  private function ensureParentExists(string $table, array $data): void
  {
    $columns = $this->getTableColumns($table);

    $whereParts = ["`kode` = ?"];
    $params = [$data['kode']];

    if (in_array('kd_wilayah', $columns)) {
      $whereParts[] = "`kd_wilayah` = ?";
      $params[] = $this->user['kd_wilayah'];
    }

    if (in_array('peraturan_id', $columns)) {

      $whereParts[] = "`peraturan_id` = ?";
      $params[] = $this->resolver()->resolvePeraturanId($table); // //
    }

    $primaryKey = $this->getPrimaryKey($table);

    $exists = $this->db->query(
      "SELECT `$primaryKey` FROM `$table`
                WHERE " . implode(" AND ", $whereParts) . "
                LIMIT 1",
      $params
    )->fetch();

    if (!$exists) {
      throw new Exception("Parent $table belum tersedia.");
    }
  }
  private function safeInsert(string $table, array $data, int $excelRow): void
  {
    try {

      $this->insertIfNotExists($table, $data);
    } catch (\Throwable $e) {

      throw new Exception(
        "Tabel {$table} gagal pada baris Excel {$excelRow} → "
          . $e->getMessage()
      );
    }
  }
  private function insertIfNotExists(string $table, array $data): ?int
  {
    $columns = $this->getTableColumns($table);

    // 🔥 Filter hanya kolom yang benar-benar ada
    $filtered = [];

    foreach ($data as $key => $value) {
      if (in_array($key, $columns)) {
        $filtered[$key] = $value;
      }
    }

    if (!isset($filtered['kode'])) {
      return null;
    }

    $whereParts = ["`kode` = ?"];
    $params     = [$filtered['kode']];

    // 🔥 Scope kd_wilayah
    if (in_array('kd_wilayah', $columns)) {
      $filtered['kd_wilayah'] = $this->user['kd_wilayah'] ?? null;
      $whereParts[] = "`kd_wilayah` = ?";
      $params[] = $filtered['kd_wilayah'];
    }

    // 🔥 Scope peraturan
    if (in_array('peraturan_id', $columns)) {

      $filtered['peraturan_id'] = $this->resolver()->resolvePeraturanId($table); // //

      $whereParts[] = "`peraturan_id` = ?";
      $params[] = $filtered['peraturan_id'];
    }

    // 🔥 Cek duplicate
    $exists = $this->db->query(
      "SELECT id FROM `$table`
WHERE " . implode(" AND ", $whereParts) . "
LIMIT 1",
      $params
    )->fetch();

    if ($exists) {
      return $exists['id'];
    }
    /* =====================================================
        ENTERPRISE SANITATION
        ===================================================== */
    $filtered = $this->sanitizer()->applySanitization($table, $filtered);
    $filtered = $this->injectAudit($filtered, 'insert');

    $this->db->insert($table, $filtered);

    return $this->db->lastInsertId();
  }
  /* =========================================================
            VALIDASI IMPORT CONFIG DARI PROFILE
            ========================================================= */
  private function validateImportConfig(string $tableKey): array
  {
    if (!isset($this->profiles[$tableKey]['import'])) {
      throw new Exception("Import belum dikonfigurasi.");
    }

    $config = $this->profiles[$tableKey]['import'];

    if (!($config['enabled'] ?? false)) {
      throw new Exception("Import tidak diizinkan untuk tabel ini.");
    }

    $role = $this->user['type_user'] ?? 'viewer';

    if (!in_array($role, $config['allowed_roles'] ?? [])) {
      throw new Exception("Role tidak diizinkan untuk import.");
    }

    return $config;
  }
  /* =========================================================
        APPLY NORMALISASI BERDASARKAN PROFILE app/Config/table_profiles.php'
        program' => [
            'table' => 'program',
            'primary_key' => 'id',
            'normalize_space' => ['nama']
        ],
        ========================================================= */
  private function applyNormalization(string $table, array $data): array
  {
    $profile = $this->getProfileByTable($table);

    if (empty($profile['normalize_space'])) {
      return $data;
    }

    foreach ($profile['normalize_space'] as $field) {
      if (isset($data[$field]) && is_string($data[$field])) {
        $data[$field] = $this->sanitizer->normalizeSpaces($data[$field]);
      }
    }

    return $data;
  }

  private function resolveScope(
    string $table,
    array $profile,
    string $mode
  ): array {

    $modeConfig = $profile['modes'][$mode] ?? [];
    $columns = $this->getTableColumns($table);

    $where = [];
    $params = [];

    $role = $this->user['type_user'] ?? 'viewer';

    if ($role !== 'super_admin') {

      if ($role === 'admin_wilayah' && in_array('kd_wilayah', $columns)) {
        $where[] = "`kd_wilayah` = ?";
        $params[] = $this->user['kd_wilayah'];
      }

      if ($role === 'admin_opd') {

        foreach (['kd_opd', 'kd_wilayah', 'tahun'] as $field) {

          if (
            in_array($field, $columns)
            && isset($this->user[$field])
            && $this->user[$field] !== null
          ) {

            $where[] = "`$field` = ?";
            $params[] = $this->user[$field];
          }
        }
      }
    }

    if (!empty($modeConfig['scope'])) {

      foreach ($modeConfig['scope'] as $field => $value) {

        if (!in_array($field, $columns)) continue;

        $where[] = "`$field` = ?";
        // //
        if ($value === 'user') {

          if ($field === 'peraturan_id') {
            $params[] = $this->resolver()->resolvePeraturanId($table); // //
          } else {
            $params[] = $this->user[$field] ?? null;
          }
        } else {
          $params[] = $value;
        }
        // //
      }
    }

    return [$where, $params];
  }
  private function resolveSearch(
    string $table,
    array $modeConfig,
    string $search
  ): array {

    if (empty($search) || empty($modeConfig['searchable'])) {
      return [[], []];
    }

    $columns = $modeConfig['searchable'] === ['*']
      ? $this->getTableColumns($table)
      : $modeConfig['searchable'];

    $where = [];
    $params = [];

    foreach ($columns as $col) {
      $where[] = "`$col` LIKE ?";
      $params[] = "%$search%";
    }

    return [
      ["(" . implode(" OR ", $where) . ")"],
      $params
    ];
  }
  private function resolveAutoFields(string $table, array $data): array
  {
    $columns = $this->getTableColumns($table);

    $autoMap = [
      'kd_wilayah',
      'kd_opd',
      'tahun'
    ];

    foreach ($autoMap as $field) {

      if (in_array($field, $columns) && isset($this->user[$field])) {
        $data[$field] = $this->user[$field];
      }
    }

    return $data;
  }


  private function normalizeDateTimeFields(string $table, array $data): array
  {
    $columns = $this->getTableColumns($table);

    foreach ($data as $field => $value) {

      if (!is_string($value) || $value === '') continue;

      // =====================================
      // FIX: paksa normalize untuk field tanggal
      // =====================================
      if (
        $this->isDateColumn($table, $field)
        || in_array($field, ['tanggal', 'tanggal_surat', 'tgl', 'date'])
      ) {
        $data[$field] = $this->normalizeToMySQLDateTime($value);
      }
    }

    return $data;
  }
  private function isDateColumn(string $table, string $field): bool
  {
    $columns = $this->db->query(
      "SHOW COLUMNS FROM `$table` WHERE Field = ?",
      [$field]
    )->fetch();

    if (!$columns) return false;

    // FIX: support datetime + timestamp
    $type = strtolower($columns['Type']);

    // FIX: support semua tipe tanggal
    return str_contains($type, 'date')
      || str_contains($type, 'time')
      || str_contains($type, 'year'); // // FIX
  }
  private function normalizeToMySQLDateTime(?string $value): string
  {
    if (!is_string($value) || $value === '') {
      return $value;
    }

    // =====================================
    // 1. dd/mm/yyyy
    // =====================================
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
      return sprintf('%04d-%02d-%02d 00:00:00', $m[3], $m[2], $m[1]);
    }

    // =====================================
    // 2. ISO date
    // =====================================
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
      return $value . ' 00:00:00';
    }

    // =====================================
    // 3. datetime tanpa detik
    // =====================================
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
      return $value . ':00';
    }

    // =====================================
    // 4. ISO T format
    // =====================================
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
      return str_replace('T', ' ', $value) . ':00';
    }

    // =====================================
    // 5. "March 18, 2026"
    // =====================================
    if (preg_match('/^[A-Za-z]+ \d{1,2}, \d{4}$/', $value)) {
      $time = strtotime($value);
      if ($time !== false) {
        return date('Y-m-d 00:00:00', $time);
      }
    }

    // =====================================
    // 6. "18 Maret 2026 10:30"
    // =====================================
    if (preg_match('/^\d{1,2} [\p{L}]+ \d{4}/u', $value)) {
      $bulan = [
        'januari' => '01',
        'februari' => '02',
        'maret' => '03',
        'april' => '04',
        'mei' => '05',
        'juni' => '06',
        'juli' => '07',
        'agustus' => '08',
        'september' => '09',
        'oktober' => '10',
        'november' => '11',
        'desember' => '12'
      ];

      $parts = explode(' ', strtolower($value));

      if (count($parts) >= 3) {
        $d = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $m = $bulan[$parts[1]] ?? null;
        $y = $parts[2];

        if ($m) {
          $time = $parts[3] ?? '00:00:00';
          if (strlen($time) === 5) $time .= ':00';

          return "$y-$m-$d $time";
        }
      }
    }

    // =====================================
    // 7. fallback universal
    // =====================================
    $time = strtotime($value);
    if ($time !== false) {
      return date('Y-m-d H:i:s', $time);
    }

    return $value;
  }
  // ⚠ Currently unused. Reserved for future date-range validation.
  private function validateDateRange(array $data, string $start, string $end): void
  {
    if (empty($data[$start]) || empty($data[$end])) {
      return;
    }

    $startTime = strtotime($data[$start]);
    $endTime   = strtotime($data[$end]);

    if ($startTime === false || $endTime === false) {
      throw new Exception("Format tanggal tidak valid.");
    }

    if ($startTime >= $endTime) {
      throw new Exception("$start harus lebih kecil dari $end");
    }
  }
  private function validateTimeWindow(string $table): void
  {
    $role = $this->user['type_user'] ?? 'viewer';

    // Super admin bebas
    if ($role === 'super_admin') {
      return;
    }

    $pengaturan = $this->config->getPengaturanAktif();

    if (!$pengaturan) {
      throw new Exception("Pengaturan aktif belum tersedia.");
    }

    $now = date('Y-m-d H:i:s');

    $map = [

      // RENSTRA
      'renstra_neo' => [
        'start' => 'awal_renstra',
        'end'   => 'akhir_renstra',
        'lock'  => 'kunci_renstra'
      ],

      // RENJA
      'renja_neo' => [
        'start' => 'awal_renja',
        'end'   => 'akhir_renja',
        'lock'  => 'kunci_renja'
      ],

      'renja_p_neo' => [
        'start' => 'awal_renja_p',
        'end'   => 'akhir_renja_p',
        'lock'  => 'kunci_renja_p'
      ],

      // DPA
      'dpa_neo' => [
        'start' => 'awal_dpa',
        'end'   => 'akhir_dpa',
        'lock'  => 'kunci_dpa'
      ],

      'dpppa_neo' => [
        'start' => 'awal_dppa',
        'end'   => 'akhir_dppa',
        'lock'  => 'kunci_dppa'
      ],

      // Paket / Realisasi
      'paket_neo' => [
        'lock' => 'kunci_paket'
      ],

      'realisasi_neo' => [
        'lock' => 'kunci_realisasi'
      ]
    ];

    if (!isset($map[$table])) {
      return; // tabel lain bebas
    }

    $config = $map[$table];

    // 🔒 CEK LOCK
    if (!empty($config['lock']) && !empty($pengaturan[$config['lock']])) {
      throw new Exception("Modul sedang dikunci.");
    }

    // ⏳ CEK PERIODE
    if (!empty($config['start']) && !empty($config['end'])) {

      $start = $pengaturan[$config['start']] ?? null;
      $end   = $pengaturan[$config['end']] ?? null;

      if (!$start || !$end) {
        throw new Exception("Periode belum dikonfigurasi.");
      }

      if ($now < $start || $now > $end) {
        throw new Exception("Di luar periode input yang diizinkan.");
      }
    }
  }
  /**
   * ============================================================
   * NORMALIZE STRING UNTUK PERBANDINGAN
   * ------------------------------------------------------------
   * Tujuan:
   * Menyamakan format teks Excel dengan nama kolom database.
   *
   * Semua karakter selain huruf dan angka dihapus.
   *
   * Contoh:
   *
   * "Sumber Dana"     → sumberdana
   * "SUMBER_DANA"     → sumberdana
   * "sumber-dana"     → sumberdana
   * "SumberDana"      → sumberdana
   *
   * ============================================================
   */
  private function normalizeForCompare(?string $value): string
  {
    // jika bukan string atau kosong
    if (!is_string($value) || $value === '') {
      return '';
    }

    // ubah semua huruf menjadi huruf kecil
    $value = strtolower($value);

    // hapus semua karakter selain huruf dan angka
    // spasi, underscore, titik, dll akan dihapus
    return preg_replace('/[^a-z0-9]/', '', $value);
  }
  /**
   * ============================================================
   * VALIDASI HEADER EXCEL
   * ------------------------------------------------------------
   * Memastikan setiap header Excel cocok dengan kolom tabel.
   *
   * Jika tidak cocok → import dihentikan.
   * ============================================================
   */
  private function validateImportHeader(array $headers, array $columnMap): void
  {
    // loop semua header Excel
    foreach ($headers as $header) {

      // normalisasi header
      $normalized = $this->normalizeForCompare($header);

      // jika tidak ada mapping di database
      if (!isset($columnMap[$normalized])) {

        // hentikan import
        throw new Exception(
          "Header Excel '{$header}' tidak cocok dengan kolom tabel."
        );
      }
    }
  }
  // ======================================================
  // BUILD COLUMN MAP BERDASARKAN KOLOM TABEL
  // ======================================================
  private function buildColumnMap(string $table): array
  {
    // ambil semua kolom tabel dari database
    $columns = $this->getTableColumns($table);

    // array mapping hasil
    $map = [];

    foreach ($columns as $col) {

      // normalisasi kolom tabel
      // contoh:
      // sumber_dana → sumberdana
      $normalized = $this->normalizeForCompare($col);

      // mapping normalisasi → nama kolom asli
      $map[$normalized] = $col;
    }

    // return mapping
    return $map;
  }
  /**
   * ============================================================
   * RESOLVE IMPORT RELATIONS
   * ------------------------------------------------------------
   * Fungsi:
   * Mengubah nilai teks dari Excel menjadi foreign key id
   * berdasarkan konfigurasi relasi di table_profiles.
   *
   * Contoh:
   *
   * Excel:
   * satuan = Kg
   *
   * Database:
   * satuan_id = 5
   *
   * Metode ini juga menggunakan cache agar tidak query
   * database setiap baris Excel.
   * ============================================================
   */
  private function resolveImportRelations(
    array $data,
    array $relations,
    int $rowNumber
  ): array {

    // ======================================================
    // LOOP SEMUA RELASI YANG DIKONFIGURASI DI PROFILE
    // ======================================================

    foreach ($relations as $excelField => $cfg) {

      // ==================================================
      // 1️⃣ CEK APAKAH FIELD ADA DI DATA EXCEL
      // ==================================================

      if (!isset($data[$excelField])) {
        continue;
      }

      // ambil nilai dari Excel
      $excelValue = trim($data[$excelField]);

      // jika kosong skip
      if ($excelValue === '') {
        continue;
      }

      // ==================================================
      // 2️⃣ AMBIL KONFIGURASI RELASI
      // ==================================================

      $lookupTable = $cfg['table'];   // tabel referensi
      $lookupField = $cfg['lookup'];  // kolom lookup
      $idField     = $cfg['id'];      // kolom id
      $storeField  = $cfg['store'];   // kolom tujuan
      $scope       = $cfg['scope'] ?? [];

      // ==================================================
      // 3️⃣ NORMALISASI KEY CACHE
      // ==================================================

      // contoh:
      // Kg → kg
      $cacheKey = strtolower($excelValue);


      /* ==================================================
4️⃣ LOAD CACHE RELASI (HANYA SEKALI)
================================================== */

      if (!isset($this->relationCache[$lookupTable])) {

        // query semua data lookup
        $sql = "
SELECT $idField, $lookupField
FROM $lookupTable
WHERE is_deleted = 0
";

        $params = [];

        // ==================================================
        // APPLY SCOPE (contoh: peraturan_id)
        // ==================================================

        foreach ($scope as $s) {

          $sql .= " AND $s = :$s";

          $params[$s] = $data[$s] ?? null;
        }

        // jalankan query
        $stmt = $this->db->query($sql, $params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // siapkan cache
        $this->relationCache[$lookupTable] = [];

        // simpan semua hasil ke cache
        foreach ($rows as $r) {

          $key = strtolower(trim($r[$lookupField]));

          $this->relationCache[$lookupTable][$key] = $r[$idField];
        }
      }


      /* ==================================================
5️⃣ CARI NILAI DI CACHE
================================================== */

      if (!isset($this->relationCache[$lookupTable][$cacheKey])) {

        // jika tidak ditemukan
        throw new Exception(
          "Baris {$rowNumber}: {$excelField} '{$excelValue}' tidak ditemukan."
        );
      }


      /* ==================================================
6️⃣ SET FOREIGN KEY
================================================== */

      // contoh:
      // satuan_id = 5
      $data[$storeField] =
        $this->relationCache[$lookupTable][$cacheKey];


      /* ==================================================
7️⃣ HAPUS FIELD TEXT EXCEL
================================================== */

      // hapus field asli
      unset($data[$excelField]);
    }

    return $data;
  }
  // ======================================================
  // 🔥 INSERT AKUN PIVOT (GENERIC UNTUK SBU/SSH/ASB/HSPK)
  // ======================================================
  private function insertAkunPivot(
    string $tableKey,
    int $masterId,
    string $kdAkunString,
    array $data
  ): void {

    // Mapping master → pivot
    $pivotMap = [
      'sbu'  => ['table' => 'sbu_akun_map',  'fk' => 'sbu_id'],
      'ssh'  => ['table' => 'ssh_akun_map',  'fk' => 'ssh_id'],
      'asb'  => ['table' => 'asb_akun_map',  'fk' => 'asb_id'],
      'hspk' => ['table' => 'hspk_akun_map', 'fk' => 'hspk_id'],
    ];

    if (!isset($pivotMap[$tableKey])) {
      return;
    }

    $pivotTable = $pivotMap[$tableKey]['table'];
    $fkField    = $pivotMap[$tableKey]['fk'];

    // 🔥 Split berdasarkan koma
    $akunList = array_filter(array_map('trim', explode(',', $kdAkunString)));

    foreach ($akunList as $akun) {

      // Insert pivot
      $this->db->insert($pivotTable, [
        $fkField        => $masterId,
        'kd_akun'       => $akun,
        'kd_wilayah'    => $data['kd_wilayah'],
        'peraturan_id'  => $data['peraturan_id']
      ]);
    }
  }
  // ======================================================
  // 🔥 VALIDATE AKUN MAPPING (GENERIC UNTUK SBU/SSH/ASB/HSPK)
  // ------------------------------------------------------
  // Digunakan saat Renja/DPA menggunakan master biaya
  // Agar akun belanja sesuai mapping pivot
  // ======================================================
  private function validateAkunMapping(
    string $table,
    array $data
  ): void {

    // Mapping foreign key → pivot table
    $map = [
      'sbu_id'  => ['pivot' => 'sbu_akun_map',  'fk' => 'sbu_id'],
      'ssh_id'  => ['pivot' => 'ssh_akun_map',  'fk' => 'ssh_id'],
      'asb_id'  => ['pivot' => 'asb_akun_map',  'fk' => 'asb_id'],
      'hspk_id' => ['pivot' => 'hspk_akun_map', 'fk' => 'hspk_id'],
    ];

    foreach ($map as $foreignKey => $config) {

      // Jika tabel ini tidak menggunakan foreign key tersebut → skip
      if (empty($data[$foreignKey])) {
        continue;
      }

      // kd_akun wajib ada untuk validasi
      if (empty($data['kd_akun'])) {
        throw new Exception("kd_akun wajib diisi untuk validasi mapping.");
      }

      $pivotTable = $config['pivot'];
      $fkField    = $config['fk'];

      // ======================================================
      // 🔥 CEK EXIST DI PIVOT
      // ======================================================
      $exists = $this->db->query(
        "SELECT id
    FROM `$pivotTable`
    WHERE `$fkField` = ?
    AND `kd_akun` = ?
    AND `kd_wilayah` = ?
    AND `peraturan_id` = ?
    LIMIT 1",
        [
          $data[$foreignKey],
          $data['kd_akun'],
          $data['kd_wilayah'] ?? $this->user['kd_wilayah'] ?? null,
          $data['peraturan_id'] ?? null
        ]
      )->fetch();

      if (!$exists) {
        throw new Exception(
          "Mapping akun tidak ditemukan untuk {$foreignKey} dengan kd_akun {$data['kd_akun']}."
        );
      }
    }
  }
  /**
   * ============================================================
   * RESOLVE SATUAN TEXT → SATUAN_ID
   * ------------------------------------------------------------
   * Fungsi:
   * Mengubah nilai satuan dari Excel menjadi id dari tabel satuan.
   *
   * Contoh:
   *
   * Excel:
   * satuan = Kg
   *
   * Database:
   * satuan_neo
   * id | item
   * 5  | Kg
   *
   * Hasil:
   * satuan_id = 5
   *
   * Sistem juga menggunakan cache agar query database
   * tidak dilakukan setiap baris Excel.
   * ============================================================
   */
  private function resolveSatuanId(array $data, int $rowNumber): array
  {
    // ======================================================
    // 1️⃣ CEK APAKAH FIELD "satuan" ADA DI DATA EXCEL
    // ======================================================

    // jika Excel tidak memiliki kolom satuan
    // maka tidak perlu diproses
    if (!isset($data['satuan'])) {
      return $data;
    }

    // ======================================================
    // 2️⃣ AMBIL NILAI SATUAN DARI EXCEL
    // ======================================================

    // ambil nilai satuan
    $excelSatuan = trim($data['satuan']);

    // jika kosong maka tidak perlu diproses
    if ($excelSatuan === '') {
      return $data;
    }

    // ======================================================
    // 3️⃣ BUAT KEY UNTUK CACHE
    // ======================================================

    // contoh:
    // Kg → kg
    // M2 → m2
    $cacheKey = strtolower($excelSatuan);


    /* ======================================================
4️⃣ LOAD CACHE SATUAN (HANYA SEKALI)
====================================================== */

    // jika cache satuan masih kosong
    // berarti ini baris pertama import
    if (empty($this->cacheSatuan)) {

      // query semua satuan sesuai peraturan
      $sql = "
SELECT id, item
FROM satuan_neo
WHERE peraturan_id = :peraturan_id
AND is_deleted = 0
";

      // jalankan query
      $stmt = $this->db->query($sql, [

        // gunakan peraturan dari data import
        'peraturan_id' => $data['peraturan_id']
      ]);

      // ambil semua hasil
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // loop semua satuan
      foreach ($rows as $r) {

        // normalisasi nama satuan
        // contoh:
        // "Kg" → "kg"
        $key = strtolower(trim($r['item']));

        // simpan ke cache
        // contoh:
        // cacheSatuan["kg"] = 5
        $this->cacheSatuan[$key] = $r['id'];
      }
    }


    /* ======================================================
5️⃣ CARI SATUAN DI CACHE
====================================================== */

    // jika satuan Excel tidak ditemukan
    if (!isset($this->cacheSatuan[$cacheKey])) {

      // lempar error dengan nomor baris Excel
      throw new Exception(
        "Baris {$rowNumber}: satuan '{$excelSatuan}' tidak ditemukan."
      );
    }


    /* ======================================================
6️⃣ SET FIELD satuan_id
====================================================== */

    // ambil id satuan dari cache
    $data['satuan_id'] = $this->cacheSatuan[$cacheKey];


    /* ======================================================
7️⃣ HAPUS FIELD TEXT SATUAN
====================================================== */

    // hapus field satuan karena database memakai satuan_id
    unset($data['satuan']);


    // kembalikan data yang sudah diperbaiki
    return $data;
  }

  /*
|--------------------------------------------------------------------------
| APPLY WHERE RELASI DI DROPDOWN
|--------------------------------------------------------------------------
*/

  public function getReference($module)
  {

    // ----------------------------------------------------
    // load profile configuration
    // ----------------------------------------------------

    $profiles = require __DIR__ . '/../Config/table_profiles.php'; // load profiles


    // ----------------------------------------------------
    // cek module
    // ----------------------------------------------------

    if (!isset($profiles[$module])) {

      return JsonResponse::error("Module {$module} tidak ditemukan"); // module tidak ada

    }


    // ----------------------------------------------------
    // ambil profile module
    // ----------------------------------------------------

    $profile = $profiles[$module]; // ambil profile


    // ----------------------------------------------------
    // resolve nama tabel
    // ----------------------------------------------------

    $table = $profile['table'] ?? $module; // fallback ke nama module


    // ----------------------------------------------------
    // ambil parameter request
    // ----------------------------------------------------

    $parent      = $_POST['parent'] ?? null; // parent value

    $parentField = $_POST['parent_field'] ?? null; // parent field

    $value       = $_POST['value'] ?? null; // value edit


    // ----------------------------------------------------
    // inisialisasi query dasar
    // ----------------------------------------------------

    $sql = "SELECT * FROM {$table}"; // query dasar

    $params = []; // parameter bind

    $where = []; // kondisi where


    /*
    ----------------------------------------------------
    PRIORITAS 1 : RELATION PROFILE
    ----------------------------------------------------
    */

    if ($parent !== null && !empty($profile['relations'])) {

      foreach ($profile['relations'] as $relation) {

        $localKey = $relation['local_key'] ?? null; // field relasi

        if ($localKey) {

          $where[] = "{$localKey} = :parent"; // filter parent

          $params['parent'] = $parent; // bind parameter

          break;
        }
      }
    }


    /*
    ----------------------------------------------------
    PRIORITAS 2 : parent_field fallback
    ----------------------------------------------------
    */ elseif ($value && $parentField) {

      $where[] = "{$parentField} = :parent"; // fallback parent

      $params['parent'] = $value; // bind parameter

    }


    /*
    ----------------------------------------------------
    APPLY WHERE
    ----------------------------------------------------
    */

    if (!empty($where)) {

      $sql .= " WHERE " . implode(" AND ", $where); // gabungkan where

    }


    /*
    ----------------------------------------------------
    ORDER DATA
    ----------------------------------------------------
    */

    $sql .= " ORDER BY kode"; // urut berdasarkan kode


    // ----------------------------------------------------
    // eksekusi query
    // ----------------------------------------------------

    $rows = $this->db
      ->query($sql, $params) // gunakan engine query
      ->fetchAll(); // ambil hasil


    // ----------------------------------------------------
    // response
    // ----------------------------------------------------

    return JsonResponse::success(
      "Reference loaded",
      [],
      $rows
    );
  }
  private function resolveTahap(string $tbl): ?string
  {
    // normalisasi huruf kecil
    $tbl = strtolower($tbl);

    // hapus suffix modul
    $tbl = preg_replace('/(_neo|_perubahan)$/', '', $tbl);

    // daftar tahap yang valid
    $validTahap = [
      'renja',
      'renja_p',
      'rka',
      'rka_p',
      'dpa',
      'dppa'
    ];

    return in_array($tbl, $validTahap) ? $tbl : null;
  }
  private function applyLookup(array &$data, array $profile)
  {
    if (empty($profile['lookup'])) return;

    foreach ($profile['lookup'] as $target => $cfg) {

      $table = $cfg['table'];

      $valueField = $cfg['value_field'];

      $match = $cfg['match'];

      $where = [];

      foreach ($match as $master => $local) {

        if (!isset($data[$local])) continue;

        $where[$master] = $data[$local];
      }

      if (!$where) continue;

      $row = $this->db
        ->table($table)
        ->select($valueField)
        ->where($where)
        ->get()
        ->getRowArray();

      if ($row) {

        $data[$target] = $row[$valueField];
      }
    }
  }
  /**
   * ============================================================
   * RESOLVE PROFILE KEY
   * ============================================================
   *
   * Menentukan profile mana yang dipakai oleh engine
   * berdasarkan:
   * 1. source (jika dropdown spesifik)
   * 2. tbl
   * 3. table name fallback
   */

  // ======================================================
  // RESOLVE PROFILE KEY
  // ======================================================

  private function resolveProfileKey(?string $tbl, ?string $source = null): ?string
  {

    // --------------------------------------------------
    // PRIORITAS 1 : SOURCE PROFILE
    // --------------------------------------------------

    if ($source !== null && isset($this->profiles[$source])) {

      return $source; // gunakan source sebagai profile

    }


    // --------------------------------------------------
    // PRIORITAS 2 : TBL PROFILE
    // --------------------------------------------------

    if ($tbl !== null && isset($this->profiles[$tbl])) {

      return $tbl; // tbl langsung adalah profile key

    }


    // --------------------------------------------------
    // PRIORITAS 3 : CARI BERDASARKAN NAMA TABEL
    // --------------------------------------------------

    if ($tbl !== null) {

      foreach ($this->profiles as $key => $profile) {

        if (($profile['table'] ?? null) === $tbl) {

          return $key; // temukan profile dari table name

        }
      }
    }


    // --------------------------------------------------
    // PROFILE TIDAK DITEMUKAN
    // --------------------------------------------------

    return null;
  }
  // ======================================================
  // DROPDOWN HIERARCHY SIPD
  // ======================================================

  // ======================================================
  // DROPDOWN HIERARCHY SIPD
  // ======================================================

  // ======================================================
  // DROPDOWN HIERARCHY SIPD
  // ======================================================

  private function loadDropdownHierarchy($parentValue = null, $req = null)
  {
    $table = 'rekening_kegiatan'; // // tabel tetap

    $columns = $this->getTableColumns($table); // // ambil kolom

    $mandatoryWhere = []; // // kondisi query
    $params = []; // // bind param

    // =====================================================
    // 🔥 FIX 1: DEFAULT REQ (ROOT WAJIB ADA LEVEL)
    // =====================================================
    if ($req === null && ($parentValue === null || $parentValue === '')) {
      $req = 'urusan'; // // default root
    }

    // =====================================================
    // 🔥 APPLY SCOPE (TIDAK DIUBAH)
    // =====================================================
    if (method_exists($this, 'resolveScope')) {

      list($scopeWhere, $scopeParams) =
        $this->resolveScope($table, $this->profiles['rekening_kegiatan'], 'default'); // //

      $mandatoryWhere = array_merge($mandatoryWhere, $scopeWhere); // //
      $params = array_merge($params, $scopeParams); // //
    }

    // =====================================================
    // 🔥 ROOT MODE
    // =====================================================
    if ($parentValue === null || $parentValue === '') {

      // =============================================
      // 🔥 EDIT MODE (HARUS ISOLATED)
      // =============================================
      if (!empty($_POST['value'])) {

        $mandatoryWhere = []; // // RESET SEMUA FILTER
        $params = []; // // RESET PARAM

        $mandatoryWhere[] = "`$table`.`kode` = ?"; // // ambil value saja
        $params[] = $_POST['value']; // //

      } else {

        $mandatoryWhere[] =
          "(`$table`.`parent_kode` IS NULL
                OR `$table`.`parent_kode`=''
                OR `$table`.`parent_kode`='0')"; // // root

        if ($req !== null) { // //
          $mandatoryWhere[] = "`$table`.`level` = ?"; // // enforce level
          $params[] = $req; // //
        }
      }
    }

    // =====================================================
    // 🔥 CASCADE MODE
    // =====================================================
    else {

      $mandatoryWhere[] = "`$table`.`parent_kode` = ?"; // // parent filter
      $params[] = $parentValue; // //

      if ($req !== null) { // //
        $mandatoryWhere[] = "`$table`.`level` = ?"; // // enforce level
        $params[] = $req; // //
      }
    }

    // =====================================================
    // BUILD WHERE
    // =====================================================
    $where = '';

    if (!empty($mandatoryWhere)) {
      $where = "WHERE " . implode(" AND ", $mandatoryWhere); // //
    }

    // =====================================================
    // QUERY
    // =====================================================
    $sql = "
        SELECT
            `$table`.`kode` AS value,
            `$table`.`uraian` AS text
        FROM `$table`
        $where
        ORDER BY `$table`.`kode` ASC
    ";

    $rows = $this->db->query($sql, $params)->fetchAll(); // //

    return JsonResponse::success(
      "Dropdown loaded",
      [],
      $rows
    );
  }

  // ======================================================
  // DROPDOWN GENERIC ENGINE
  // ======================================================

  private function loadDropdownGeneric(
    string $profileKey,
    $parentValue = null,
    $kdAkun = null
  ): string {

    // --------------------------------------------------
    // cek profile
    // --------------------------------------------------

    // =====================================================
    // PROFILE RESOLUTION
    // =====================================================

    // cek apakah profile ada
    if (!isset($this->profiles[$profileKey])) {

      // ---------------------------------------------
      // fallback gunakan nama tabel langsung
      // ---------------------------------------------

      $table = $profileKey; // gunakan tbl sebagai nama tabel

      // ambil kolom tabel
      $columns = $this->getTableColumns($table); // deteksi kolom tabel

      // field default
      $valueField = 'id'; // default value
      $labelField = 'nama'; // default label

      // deteksi kolom kode
      if (in_array('kode', $columns)) {

        $valueField = 'kode'; // gunakan kode sebagai value

      }

      // deteksi uraian
      if (in_array('uraian', $columns)) {

        $labelField = 'uraian'; // gunakan uraian sebagai label

      }
    } else {

      // ---------------------------------------------
      // profile normal
      // ---------------------------------------------

      $profile = $this->profiles[$profileKey]; // ambil profile

      $table   = $profile['table']; // tabel dari profile

      $primaryKey = $profile['primary_key'] ?? 'id'; // primary key

      $valueField = $profile['dropdown']['value'] ?? $primaryKey; // value dropdown

      $labelField = $profile['dropdown']['label'] ?? 'nama'; // label dropdown

      $columns = $this->getTableColumns($table); // ambil kolom tabel

    }



    if (!empty($_POST['filters'])) {
      $filters = json_decode($_POST['filters'], true);

      if (is_array($filters)) {
        foreach ($filters as $col => $val) {
          if (in_array($col, $columns)) {
            $mandatoryWhere[] = "`$table`.`$col` = ?";
            $params[] = $val;
          }
        }
      }
    }
    // --------------------------------------------------
    // parameter request
    // --------------------------------------------------
    $cari  = $_POST['search'] ?? null;


    $limit = min((int)($_POST['limit'] ?? 20), 100); // limit dropdown

    $currentValue = $_POST['value'] ?? null; // current edit value


    // --------------------------------------------------
    // kondisi where
    // --------------------------------------------------

    $mandatoryWhere = []; // kondisi wajib

    $params = []; // parameter query


    /*
    -----------------------------------------------------
    APPLY USER SCOPE ENGINE
    -----------------------------------------------------
    */

    if (method_exists($this, 'resolveScope')) {

      list($scopeWhere, $scopeParams) =
        $this->resolveScope($table, $profile, 'dropdown'); // gunakan scope engine

      $mandatoryWhere = array_merge($mandatoryWhere, $scopeWhere);

      $params = array_merge($params, $scopeParams);
    }


    /*
    -----------------------------------------------------
    PROFILE WHERE
    -----------------------------------------------------
    */

    if (!empty($profile['where'])) {

      foreach ($profile['where'] as $col => $val) {

        if (!in_array($col, $columns)) continue;

        $mandatoryWhere[] = "`$table`.`$col` = ?";

        if ($val === 'user') {

          $params[] = $this->user[$col] ?? null;
        } else {

          $params[] = $val;
        }
      }
    }


    /*
    -----------------------------------------------------
    FILTER DISABLE
    -----------------------------------------------------
    */

    if (in_array('disable', $columns)) {

      $mandatoryWhere[] = "`$table`.`disable` = 0";
    }


    /*
    -----------------------------------------------------
    FILTER STATUS
    -----------------------------------------------------
    */

    if (in_array('status', $columns)) {

      $mandatoryWhere[] = "`$table`.`status` = 1";
    }


    /*
    -----------------------------------------------------
    RELATION PARENT
    -----------------------------------------------------
    */

    if ($parentValue !== null && !empty($profile['relations'])) {

      $relation = reset($profile['relations']);

      $localKey = $relation['local_key'] ?? null;

      if ($localKey && in_array($localKey, $columns)) {

        $req = $_POST['req'] ?? null; // //

        if ($req === null && $parentValue === null) { // // root tanpa req
          $req = 'urusan'; // // default root
        }

        $expectedLevel = null; // // init

        if ($req && isset($profile['req_filters'][$req])) { // // ambil dari config
          $expectedLevel = $profile['req_filters'][$req]['where']['level'] ?? null;
        }

        $mandatoryWhere[] = "`$table`.`$localKey` = ?"; // // parent filter
        $params[] = $parentValue; // // bind parent

        if ($expectedLevel !== null) { // // enforce level
          $mandatoryWhere[] = "`$table`.`level` = ?";
          $params[] = $expectedLevel;
        }
      }
    }


    /*
    -----------------------------------------------------
    FILTER AKUN
    -----------------------------------------------------
    */

    if ($kdAkun !== null && in_array('kd_akun', $columns)) {

      $mandatoryWhere[] = "`$table`.`kd_akun` = ?";

      $params[] = $kdAkun;
    }


    /*
    -----------------------------------------------------
    SEARCH
    -----------------------------------------------------
    */

    $optionalWhere = [];

    if ($cari) {

      $optionalWhere[] = "`$table`.`$labelField` LIKE ?";

      $params[] = "%$cari%";
    }


    /*
    -----------------------------------------------------
    CURRENT VALUE (EDIT MODE)
    -----------------------------------------------------
    */

    if ($currentValue !== null && is_numeric($currentValue)) {

      $optionalWhere[] = "`$table`.`$valueField` = ?";

      $params[] = $currentValue;
    }


    if ($mandatoryWhere) {

      $where = "WHERE " . implode(" AND ", $mandatoryWhere);

      if ($optionalWhere) {

        $where .= " AND (" . implode(" OR ", $optionalWhere) . ")";
      }
    } elseif ($optionalWhere) {

      $where = "WHERE (" . implode(" OR ", $optionalWhere) . ")";
    }


    /*
    -----------------------------------------------------
    QUERY DROPDOWN
    -----------------------------------------------------
    */

    $query = "

        SELECT
            `$table`.`$valueField` AS value,
            `$table`.`$labelField` AS text

        FROM `$table`

        $where

        ORDER BY `$table`.`$labelField` ASC

        LIMIT $limit

    ";
    // --------------------------------------------------
    // eksekusi query
    // --------------------------------------------------

    $rows = $this->db
      ->query($query, $params)
      ->fetchAll();


    // --------------------------------------------------
    // response
    // --------------------------------------------------

    return JsonResponse::success(
      "Dropdown loaded",
      [],
      $rows
    );
  }
  private function handleVersioning(
    string $table,
    array $profile,
    array $request,
    array $filtered,
    array $oldData,
    string $primaryKey
  ) {

    if (empty($profile['versioning'])) {
      return null;
    }

    $config = $profile['versioning'];

    if (($config['mode'] ?? '') !== 'insert_on_change') {
      return null;
    }

    $fields = $config['fields'] ?? [];

    if (empty($fields)) {
      return null;
    }

    $changed = false;

    foreach ($fields as $field) {

      if (!isset($filtered[$field])) {
        continue;
      }

      if ($filtered[$field] != ($oldData[$field] ?? null)) {
        $changed = true;
        break;
      }
    }

    if (!$changed) {
      return null;
    }

    // ==================================================
    // 🔥 CEK DUPLIKAT (PAKAI RULE PROFILE)
    // ==================================================

    if (!empty($profile['not_duplicate'])) {

      $whereParts = [];
      $params = [];

      foreach ($profile['not_duplicate'] as $f) {

        $val = $filtered[$f] ?? $oldData[$f] ?? null;

        $whereParts[] = "`$f` = ?";
        $params[] = $val;
      }
      // // tambahkan pengecualian ID aktif
      $whereParts[] = "`$primaryKey` != ?"; // // exclude current row
      $params[] = $oldData[$primaryKey]; // // ambil id existing
      $exists = $this->db->query(
        "SELECT $primaryKey FROM `$table`
       WHERE " . implode(" AND ", $whereParts) . "
       LIMIT 1",
        $params
      )->fetch();

      if ($exists) {
        return JsonResponse::error("Data duplicate (versioning)");
      }
    }

    // ==================================================
    // 🔥 INSERT BARU
    // ==================================================

    $newRequest = array_merge($request, $filtered);

    unset($newRequest['id_row']);
    unset($newRequest['id']);

    return $this->insert($table, $newRequest);
  }
  // path: DynamicTableService.php

  private function handleWriteRelations(
    string $table,
    int $id,
    array $request,
    array $profile
  ): void {

    if (empty($profile['write_relations'])) {
      return;
    }

    foreach ($profile['write_relations'] as $relTable => $rel) {

      $fk = $rel['fk'] ?? null;
      if (!$fk) continue;

      // =====================================
      // 🔥 AUTO DETECT MODE
      // =====================================
      $mode = $this->detectRelationMode($relTable); // // FIX

      // =====================================
      // 🔥 DELETE EXISTING (SYNC MODE DEFAULT)
      // =====================================
      $this->db->delete($relTable, "WHERE `$fk` = ?", [$id]); // // FIX

      // =====================================
      // 🔥 KV MODE
      // =====================================
      if ($mode === 'kv') {

        foreach ($request as $key => $val) {

          if (is_array($val)) continue;
          if (in_array($key, ['action', 'tbl'])) continue;

          $this->db->insert($relTable, [
            $fk => $id,
            'meta_key' => $key,
            'meta_value' => $val
          ]);
        }

        continue;
      }

      // =====================================
      // 🔥 JSON MODE
      // =====================================
      if ($mode === 'json') {

        $columns = $this->getTableColumns($relTable);

        $jsonField = in_array('struktur_json', $columns)
          ? 'struktur_json'
          : 'meta_json';

        // 🔥 FIX CYCLIC
        $data = $request; // //

        unset($data['action'], $data['tbl']); // //

        // ==========================================
        // 🔥 FIX: HAPUS SELF REFERENCE
        // ==========================================
        if (isset($data['struktur_json'])) { // //
          unset($data['struktur_json']); // //
        }

        // ==========================================
        // 🔥 JIKA ADA PAYLOAD KHUSUS
        // ==========================================
        if (isset($request['struktur_json']) && is_array($request['struktur_json'])) { // //
          $data = $request['struktur_json']; // //
        }
        // 🔥 FIX WAJIB DI SINI
        $data = $this->sanitizeStruktur($data);
        $this->db->insert($relTable, [
          $fk => $id,
          $jsonField => json_encode($data)
        ]);

        continue;
      }

      // =====================================
      // 🔥 TABLE MODE (MULTI ROW)
      // =====================================
      $source = $rel['source'] ?? null;

      if (!$source || empty($request[$source])) {
        continue;
      }

      foreach ($request[$source] as $row) {

        $insert = [$fk => $id];

        $columns = $this->getTableColumns($relTable);

        foreach ($columns as $col) {

          if ($col === $fk) continue;

          $insert[$col] = $row[$col] ?? null;
        }

        $this->db->insert($relTable, $insert);
      }
    }
  }
  // path: DynamicTableService.php

  private function deleteRelations(
    string $table,
    int $id,
    array $profile
  ): void {

    if (empty($profile['write_relations'])) return;

    $id = (int)$id;
    if ($id <= 0) return; // FIX HARD

    foreach ($profile['write_relations'] as $relTable => $rel) {

      $fk = $rel['fk'] ?? null;

      if (!$fk || !is_string($fk) || $fk === '') {
        continue; // skip relasi rusak
      }

      $this->db->delete(
        $relTable,
        "WHERE `$fk` = ?",
        [$id]
      );
    }
  }
  // path: DynamicTableService.php

  private function detectRelationMode(string $table): string
  {
    $columns = $this->getTableColumns($table);

    // KV MODE
    if (in_array('meta_key', $columns) && in_array('meta_value', $columns)) {
      return 'kv';
    }

    // JSON MODE
    if (in_array('meta_json', $columns) || in_array('struktur_json', $columns)) {
      return 'json';
    }

    // DEFAULT = TABLE
    return 'table';
  }
  private function normalizeForLog($data, $depth = 0)
  {
    // batas recursion
    if ($depth > 5) {
      return '[MAX_DEPTH]';
    }

    // null / scalar aman
    if (is_null($data) || is_scalar($data)) {
      return $data;
    }

    // object → convert ke array
    if (is_object($data)) {
      $data = (array)$data;
    }

    // array → recursive sanitize
    if (is_array($data)) {
      $clean = [];

      foreach ($data as $key => $value) {

        // skip resource / closure
        if (is_resource($value) || $value instanceof Closure) {
          continue;
        }

        $clean[$key] = $this->normalizeForLog($value, $depth + 1);
      }

      return $clean;
    }

    // fallback
    return (string)$data;
  }
  private function safeJson($data)
  {
    if (empty($data)) return null;

    try {

      $normalized = $this->normalizeForLog($data);

      $json = json_encode(
        $normalized,
        JSON_UNESCAPED_UNICODE |
          JSON_PARTIAL_OUTPUT_ON_ERROR
      );

      return $json !== false ? $json : null;
    } catch (Throwable $e) {
      return null; // HARD SAFE
    }
  }
  // //

  private function insertJson(string $table, array $request): string
  {
    // =====================================
    // 🔥 AMBIL struktur_json
    // =====================================
    if (empty($request['struktur_json'])) {
      return JsonResponse::error("struktur_json wajib ada");
    }

    $json = $request['struktur_json'];

    if (is_string($json)) {
      $json = json_decode($json, true);
    }

    // =====================================
    // 🔥 FIX DOUBLE NESTED (KRITIS)
    // =====================================
    if (isset($json['struktur_json'])) {
      $json = $json['struktur_json'];
    }

    if (!is_array($json)) {
      return JsonResponse::error("struktur_json tidak valid");
    }

    // =====================================
    // 🔥 INJECT KE REQUEST ROOT
    // =====================================
    // supaya semua engine lama tetap jalan
    $request = [
      ...$request,
      ...$json
    ];

    // =====================================
    // 🔥 PANGGIL ENGINE LAMA (TANPA DIUBAH)
    // =====================================
    return $this->insert($table, $request);
  }
  private function updateJson(string $table, array $request): string
  {
    if (empty($request['id_row'])) {
      return JsonResponse::error("id_row wajib ada");
    }

    if (empty($request['struktur_json'])) {
      return JsonResponse::error("struktur_json wajib ada");
    }

    $profile = $this->getProfileByTable($table);

    if (empty($profile['json_update'])) {
      return JsonResponse::error("json_update belum dikonfigurasi");
    }

    $cfg = $profile['json_update'];

    $mode = $cfg['mode'] ?? 'direct';
    $relTable = $cfg['relation_table'];
    $fk = $cfg['fk'];
    $jsonField = $cfg['json_field'];
    $versionFields = $cfg['versioning_fields'] ?? [];

    $id = $request['id_row'];
    $primaryKey = $this->getPrimaryKey($table);

    // ambil data lama
    $old = $this->db->query(
      "SELECT * FROM `$table` WHERE `$primaryKey` = ?",
      [$id]
    )->fetch();

    if (!$old) {
      return JsonResponse::error("Data tidak ditemukan");
    }

    // parse json
    $json = $request['struktur_json'];

    if (is_string($json)) {
      $json = json_decode($json, true);
    }

    if (isset($json['struktur_json'])) {
      $json = $json['struktur_json'];
    }

    if (!is_array($json)) {
      return JsonResponse::error("struktur_json tidak valid");
    }

    $json = $this->sanitizeStruktur($json);

    // =====================================
    // MODE: DIRECT
    // =====================================
    if ($mode === 'direct') {

      $this->db->delete($relTable, "WHERE `$fk` = ?", [$id]);

      $this->db->insert($relTable, [
        $fk => $id,
        $jsonField => json_encode($json)
      ]);

      return JsonResponse::success("JSON berhasil diupdate");
    }

    // =====================================
    // MODE: SMART VERSIONING
    // =====================================
    if ($mode === 'smart_versioning') {

      $isChanged = false;

      foreach ($versionFields as $field) {

        $newVal = $request[$field] ?? $old[$field];

        if ($field === 'tanggal_surat') {
          $newVal = $this->normalizeToMySQLDateTime($newVal);
        }

        if ($newVal != $old[$field]) {
          $isChanged = true;
          break;
        }
      }

      // ===============================
      // TIDAK BERUBAH → UPDATE
      // ===============================
      if (!$isChanged) {

        $this->db->delete($relTable, "WHERE `$fk` = ?", [$id]);

        $this->db->insert($relTable, [
          $fk => $id,
          $jsonField => json_encode($json)
        ]);

        return JsonResponse::success("JSON berhasil diupdate");
      }

      // ===============================
      // BERUBAH → INSERT BARU
      // ===============================
      $newData = $old;

      unset($newData[$primaryKey]);

      foreach ($versionFields as $field) {
        if (isset($request[$field])) {
          $newData[$field] = $request[$field];
        }
      }

      $newData = $this->injectAudit($newData, 'insert');

      try {
        $this->validateDuplicate($table, $newData);
      } catch (\Throwable $e) {
        return JsonResponse::error($e->getMessage());
      }

      $newId = $this->insertSafe($table, $newData);

      $this->db->insert($relTable, [
        $fk => $newId,
        $jsonField => json_encode($json)
      ]);

      return JsonResponse::success("Versi baru berhasil dibuat", [
        'insert_id' => $newId
      ]);
    }

    // =====================================
    // MODE: VERSIONING ONLY
    // =====================================
    if ($mode === 'versioning_only') {

      $newData = $old;
      unset($newData[$primaryKey]);

      $newData = $this->injectAudit($newData, 'insert');

      $newId = $this->insertSafe($table, $newData);

      $this->db->insert($relTable, [
        $fk => $newId,
        $jsonField => json_encode($json)
      ]);

      return JsonResponse::success("Versi baru dibuat", [
        'insert_id' => $newId
      ]);
    }

    return JsonResponse::error("Mode json_update tidak dikenali");
  }
  private function sanitizeStruktur(array $struktur): array
  {
    array_walk_recursive($struktur, function (&$value, $key) {

      // // hanya filter field text
      if ($key === 'text' && is_string($value)) {

        // // blok string PHP function
        if (str_contains($value, 'function') || str_contains($value, 'public function')) {
          $value = ''; // // kosongkan karena terdeteksi code injection
        }

        // // bersihkan tag HTML berbahaya
        $value = strip_tags($value);

        // // trim whitespace
        $value = trim($value);
      }
    });

    return $struktur;
  }
}