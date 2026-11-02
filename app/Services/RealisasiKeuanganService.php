<?php

require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/ContractAccessService.php';

final class RealisasiKeuanganService
{
  private DB $db;
  private array $user;

  private const TYPES = ['PROGRES', 'LS', 'UANG_MUKA_KONTRAK', 'UP', 'TU', 'GU'];
  private const REVIEW_ROLES = ['super_admin', 'admin_wilayah', 'admin_opd', 'kepala_opd', 'pa_kpa', 'bendahara'];

  public function __construct(array $user)
  {
    $this->db = DB::getInstance();
    $this->user = Auth::scopedUser() ?: $user;
  }

  private function scope(): array
  {
    $region = trim((string)($this->user['kd_wilayah'] ?? ''));
    $opd = trim((string)($this->user['kd_opd'] ?? ''));
    $year = (int)($this->user['tahun'] ?? 0);
    if ($region === '' || $year < 2000 || $year > 2200) {
      throw new RuntimeException('Scope wilayah dan tahun pengguna tidak lengkap.');
    }
    return [$region, $opd === '' ? '0' : $opd, $year];
  }

  private static function validDate(string $value): bool
  {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
  }

  public static function labels(): array
  {
    return [
      'PROGRES' => 'Progres / BAST / tagihan belum dibayar',
      'LS' => 'Pembayaran langsung (LS)',
      'UANG_MUKA_KONTRAK' => 'Uang muka kontrak kepada penyedia',
      'UP' => 'Belanja melalui UP (SPJ)',
      'TU' => 'Belanja melalui TU (SPJ)',
      'GU' => 'SP2D GU / penggantian UP',
    ];
  }

  public function save(string $uuid, int $contractId, array $input, float $amount): array
  {
    if (!preg_match('/^[a-f0-9-]{36}$/iD', $uuid)) {
      throw new InvalidArgumentException('ID transaksi realisasi tidak valid.');
    }

    $type = strtoupper(trim((string)($input['jenis_transaksi'] ?? 'PROGRES')));
    if (!in_array($type, self::TYPES, true)) {
      throw new InvalidArgumentException('Pilih jenis transaksi keuangan yang tersedia.');
    }
    [, , $year] = $this->scope();

    $data = [
      'jenis_transaksi' => $type,
      'nomor_bukti' => trim((string)($input['nomor_bukti'] ?? '')),
      'nomor_sp2d' => trim((string)($input['nomor_sp2d'] ?? '')),
      'tanggal_sp2d' => trim((string)($input['tanggal_sp2d'] ?? '')),
      'nomor_sp2d_uang_muka' => trim((string)($input['nomor_sp2d_uang_muka'] ?? '')),
      'nomor_sp2d_sumber' => trim((string)($input['nomor_sp2d_sumber'] ?? '')),
      'nomor_spj' => trim((string)($input['nomor_spj'] ?? '')),
      'tanggal_spj' => trim((string)($input['tanggal_spj'] ?? '')),
      'tanggal_pengesahan_spj' => trim((string)($input['tanggal_pengesahan_spj'] ?? '')),
      'nomor_bast' => trim((string)($input['nomor_bast'] ?? '')),
      'tanggal_bast' => trim((string)($input['tanggal_bast'] ?? '')),
      'nomor_up_tu' => trim((string)($input['nomor_up_tu'] ?? '')),
      'nilai_potongan_uang_muka' => $input['nilai_potongan_uang_muka'] ?? 0,
    ];

    if ($type !== 'PROGRES') {
      if ($data['nomor_bukti'] === '') {
        throw new InvalidArgumentException('Nomor bukti transaksi wajib diisi untuk transaksi keuangan.');
      }
      if (!is_numeric($data['nilai_potongan_uang_muka']) || (float)$data['nilai_potongan_uang_muka'] < 0 || (float)$data['nilai_potongan_uang_muka'] > $amount) {
        throw new InvalidArgumentException('Potongan uang muka harus bernilai nol atau lebih dan tidak melebihi nilai transaksi.');
      }
      $data['nilai_potongan_uang_muka'] = (float)$data['nilai_potongan_uang_muka'];
    } else {
      $data['nilai_potongan_uang_muka'] = 0;
    }

    if (in_array($type, ['LS', 'UANG_MUKA_KONTRAK', 'GU'], true)) {
      if ($data['nomor_sp2d'] === '' || !self::validDate($data['tanggal_sp2d'])) {
        throw new InvalidArgumentException('Nomor dan tanggal SP2D wajib untuk transaksi LS, uang muka kontrak, dan GU.');
      }
      if ((int)substr($data['tanggal_sp2d'], 0, 4) !== $year) {
        throw new InvalidArgumentException('Tanggal SP2D transaksi harus berada pada tahun laporan yang dipilih.');
      }
    }

    if (in_array($type, ['UP', 'TU', 'GU'], true)) {
      if ($data['nomor_spj'] === '' || !self::validDate($data['tanggal_spj']) || !self::validDate($data['tanggal_pengesahan_spj'])) {
        throw new InvalidArgumentException('Nomor SPJ, tanggal SPJ, dan tanggal pengesahan SPJ wajib untuk transaksi UP/TU/GU.');
      }
      if ((int)substr($data['tanggal_pengesahan_spj'], 0, 4) !== $year) {
        throw new InvalidArgumentException('Tanggal pengesahan SPJ harus berada pada tahun laporan yang dipilih.');
      }
    }

    if (in_array($type, ['UP', 'TU'], true) && ($data['nomor_sp2d_sumber'] === '' || $data['nomor_up_tu'] === '')) {
      throw new InvalidArgumentException('Transaksi UP/TU wajib ditautkan ke nomor SP2D sumber dan nomor register UP/TU.');
    }
    if ($type === 'LS' && (float)$data['nilai_potongan_uang_muka'] > 0 && $data['nomor_sp2d_uang_muka'] === '') {
      throw new InvalidArgumentException('Cantumkan nomor SP2D uang muka kontrak yang dipotong agar transaksi dapat ditelusuri.');
    }

    if ($type !== 'PROGRES') {
      $attachment = $this->db->query(
        'SELECT id FROM realisasi_dokumen_neo WHERE transaksi_uuid=? AND kontrak_id=? AND is_deleted=0 LIMIT 1',
        [$uuid, $contractId]
      )->fetch();
      if (!$attachment) {
        throw new InvalidArgumentException('Lampirkan sekurang-kurangnya satu bukti transaksi keuangan.');
      }
    }
    foreach (['tanggal_sp2d', 'tanggal_spj', 'tanggal_pengesahan_spj', 'tanggal_bast'] as $dateField) {
      if ($data[$dateField] === '') $data[$dateField] = null;
    }

    [$region, $scopeOpd, $year] = $this->scope();
    $contract = $this->db->query(
      'SELECT kd_wilayah,kd_opd,tahun FROM kontrak_neo WHERE id=? AND is_deleted=0 LIMIT 1',
      [$contractId]
    )->fetch();
    if (!$contract || (string)$contract['kd_wilayah'] !== $region || (int)$contract['tahun'] !== $year
      || ($scopeOpd !== '0' && (string)$contract['kd_opd'] !== $scopeOpd)) {
      throw new RuntimeException('Kontrak transaksi tidak berada dalam scope pengguna.');
    }
    $opd = (string)$contract['kd_opd'];
    $existing = $this->db->query(
      'SELECT id FROM laporan_keuangan_transaksi_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND transaksi_uuid=? LIMIT 1',
      [$region, $opd, $year, $uuid]
    )->fetch();

    $values = $data + [
      'kd_wilayah' => $region,
      'kd_opd' => $opd,
      'tahun' => $year,
      'transaksi_uuid' => $uuid,
      'kontrak_id' => $contractId,
      'status_verifikasi' => 'MENUNGGU_VERIFIKASI',
      'username_input' => (string)($this->user['username'] ?? 'system'),
      'username_verifikasi' => null,
      'tgl_verifikasi' => null,
    ];
    if ($existing) {
      $this->db->update('laporan_keuangan_transaksi_neo', $values, 'WHERE id=?', [(int)$existing['id']]);
    } else {
      $this->db->insert('laporan_keuangan_transaksi_neo', $values);
    }

    return ['jenis_transaksi' => $type, 'status_verifikasi' => 'MENUNGGU_VERIFIKASI'];
  }

  public function detail(string $uuid): array
  {
    [$region, $opd, $year] = $this->scope();
    $params = [$region, $year, $uuid];
    $where = '';
    if ($opd !== '0') {
      $where = ' AND kd_opd=?';
      $params[] = $opd;
    }
    $row = $this->db->query(
      'SELECT jenis_transaksi,nomor_bukti,nomor_sp2d,tanggal_sp2d,nomor_sp2d_uang_muka,nomor_sp2d_sumber,nomor_spj,tanggal_spj,tanggal_pengesahan_spj,nomor_bast,tanggal_bast,nomor_up_tu,nilai_potongan_uang_muka,status_verifikasi,username_verifikasi,tgl_verifikasi FROM laporan_keuangan_transaksi_neo WHERE kd_wilayah=? AND tahun=? AND transaksi_uuid=?' . $where . ' LIMIT 1',
      $params
    )->fetch();
    return $row ?: [];
  }

  public function verify(string $uuid): array
  {
    $role = (string)($this->user['type_user'] ?? '');
    if (!in_array($role, self::REVIEW_ROLES, true)) {
      throw new RuntimeException('Role ini tidak memiliki kewenangan verifikasi transaksi keuangan.');
    }
    [$region, $opd, $year] = $this->scope();
    $params = [$region, $year, $uuid];
    $scopeOpd = '';
    if ($opd !== '0') {
      $scopeOpd = ' AND t.kd_opd=?';
      $params[] = $opd;
    }
    [$accessSql, $accessParams] = ContractAccessService::condition($this->user, 'k');
    array_push($params, ...$accessParams);
    $row = $this->db->query(
      'SELECT t.id,t.kontrak_id,t.jenis_transaksi,t.nomor_bukti,t.nomor_sp2d,t.tanggal_sp2d,t.nomor_sp2d_uang_muka,t.nomor_sp2d_sumber,t.nomor_spj,t.tanggal_spj,t.tanggal_pengesahan_spj,t.nomor_up_tu,t.nilai_potongan_uang_muka,t.username_input,t.status_verifikasi FROM laporan_keuangan_transaksi_neo t JOIN kontrak_neo k ON k.id=t.kontrak_id WHERE t.kd_wilayah=? AND t.tahun=? AND t.transaksi_uuid=?' . $scopeOpd . ' AND k.is_deleted=0' . $accessSql . ' LIMIT 1',
      $params
    )->fetch();
    if (!$row) throw new RuntimeException('Data transaksi tidak ditemukan dalam scope pengguna.');
    if ($row['status_verifikasi'] === 'TERVERIFIKASI') throw new RuntimeException('Transaksi ini sudah diverifikasi.');
    if ((string)$row['username_input'] === (string)($this->user['username'] ?? '')) {
      throw new RuntimeException('Verifikasi harus dilakukan pengguna lain, bukan penginput transaksi.');
    }
    $attachment = $this->db->query(
      'SELECT id FROM realisasi_dokumen_neo WHERE transaksi_uuid=? AND kontrak_id=? AND is_deleted=0 LIMIT 1',
      [$uuid, (int)$row['kontrak_id']]
    )->fetch();
    if (!$attachment) throw new RuntimeException('Transaksi belum memiliki dokumen sumber yang dapat diverifikasi.');
    $type = (string)$row['jenis_transaksi'];
    if ($type !== 'PROGRES' && trim((string)$row['nomor_bukti']) === '') {
      throw new RuntimeException('Nomor bukti transaksi wajib tersedia sebelum verifikasi.');
    }
    if (in_array($type, ['LS', 'UANG_MUKA_KONTRAK', 'GU'], true)
      && (trim((string)$row['nomor_sp2d']) === '' || !self::validDate((string)$row['tanggal_sp2d']))) {
      throw new RuntimeException('Nomor dan tanggal SP2D belum lengkap atau tidak valid.');
    }
    if (in_array($type, ['LS', 'UANG_MUKA_KONTRAK', 'GU'], true)
      && (int)substr((string)$row['tanggal_sp2d'], 0, 4) !== $year) {
      throw new RuntimeException('Tanggal SP2D tidak sesuai tahun laporan transaksi.');
    }
    if (in_array($type, ['UP', 'TU', 'GU'], true)
      && (trim((string)$row['nomor_spj']) === '' || !self::validDate((string)$row['tanggal_spj']) || !self::validDate((string)$row['tanggal_pengesahan_spj']))) {
      throw new RuntimeException('Nomor SPJ, tanggal SPJ, atau tanggal pengesahan belum lengkap atau tidak valid.');
    }
    if (in_array($type, ['UP', 'TU', 'GU'], true)
      && (int)substr((string)$row['tanggal_pengesahan_spj'], 0, 4) !== $year) {
      throw new RuntimeException('Tanggal pengesahan SPJ tidak sesuai tahun laporan transaksi.');
    }
    if (in_array($type, ['UP', 'TU'], true)
      && (trim((string)$row['nomor_sp2d_sumber']) === '' || trim((string)$row['nomor_up_tu']) === '')) {
      throw new RuntimeException('Transaksi UP/TU belum ditautkan ke register dan SP2D sumber.');
    }
    if ($type === 'LS' && (float)$row['nilai_potongan_uang_muka'] > 0 && trim((string)$row['nomor_sp2d_uang_muka']) === '') {
      throw new RuntimeException('Nomor SP2D uang muka yang dipotong belum dicatat.');
    }
    $this->db->update('laporan_keuangan_transaksi_neo', [
      'status_verifikasi' => 'TERVERIFIKASI',
      'username_verifikasi' => (string)($this->user['username'] ?? 'system'),
      'tgl_verifikasi' => date('Y-m-d H:i:s'),
    ], 'WHERE id=?', [(int)$row['id']]);
    return ['transaksi_uuid' => $uuid, 'jenis_transaksi' => $row['jenis_transaksi'], 'status_verifikasi' => 'TERVERIFIKASI'];
  }

  public function pendingRows(): array
  {
    return $this->transactionRows(true);
  }

  public function transactionRows(bool $pendingOnly = false): array
  {
    [$region, $opd, $year] = $this->scope();
    $params = [$region, $year];
    $whereOpd = '';
    if ($opd !== '0') {
      $whereOpd = ' AND dr.kd_opd=?';
      $params[] = $opd;
    }
    [$accessSql, $accessParams] = ContractAccessService::condition($this->user, 'k');
    $whereOpd .= $accessSql;
    array_push($params, ...$accessParams);
    $pendingFilter = $pendingOnly
      ? " HAVING MAX(t.status_verifikasi) IS NULL OR MAX(t.status_verifikasi)='MENUNGGU_VERIFIKASI'"
      : '';
    return $this->db->query(
      "      SELECT MIN(dr.id) realization_id,dr.transaksi_uuid,dr.kontrak_id,MAX(k.nomor_kontrak) nomor_kontrak,MAX(dr.tanggal) tanggal,MAX(dr.kd_sub_keg) kd_sub_keg,MAX(dr.kd_akun) kd_akun,SUM(dr.jumlah) jumlah,SUM(dr.nilai_lra) nilai_lra,MAX(t.jenis_transaksi) jenis_transaksi,MAX(t.status_verifikasi) status_verifikasi,MAX(t.username_input) username_input,MAX(t.nomor_bukti) nomor_bukti,MAX(t.nomor_sp2d) nomor_sp2d,MAX(t.nomor_sp2d_uang_muka) nomor_sp2d_uang_muka,MAX(t.tanggal_sp2d) tanggal_sp2d,MAX(t.nomor_sp2d_sumber) nomor_sp2d_sumber,MAX(t.nomor_spj) nomor_spj,MAX(t.tanggal_pengesahan_spj) tanggal_pengesahan_spj,MAX(t.nomor_up_tu) nomor_up_tu,MAX(f.file_ids) file_ids,COALESCE(MAX(f.jumlah_dokumen),0) jumlah_dokumen
       FROM daftar_realisasi_neo dr
       JOIN kontrak_neo k ON k.id=dr.kontrak_id AND k.is_deleted=0
       LEFT JOIN laporan_keuangan_transaksi_neo t ON CONVERT(t.transaksi_uuid USING utf8mb4) COLLATE utf8mb4_general_ci=CONVERT(dr.transaksi_uuid USING utf8mb4) COLLATE utf8mb4_general_ci AND CONVERT(t.kd_wilayah USING utf8mb4) COLLATE utf8mb4_general_ci=CONVERT(dr.kd_wilayah USING utf8mb4) COLLATE utf8mb4_general_ci AND CONVERT(t.kd_opd USING utf8mb4) COLLATE utf8mb4_general_ci=CONVERT(dr.kd_opd USING utf8mb4) COLLATE utf8mb4_general_ci AND t.tahun=dr.tahun
       LEFT JOIN (
         SELECT transaksi_uuid,kontrak_id,GROUP_CONCAT(id ORDER BY id SEPARATOR ',') file_ids,COUNT(*) jumlah_dokumen
         FROM realisasi_dokumen_neo WHERE is_deleted=0 GROUP BY transaksi_uuid,kontrak_id
       ) f ON CONVERT(f.transaksi_uuid USING utf8mb4) COLLATE utf8mb4_general_ci=CONVERT(dr.transaksi_uuid USING utf8mb4) COLLATE utf8mb4_general_ci AND f.kontrak_id=dr.kontrak_id
       WHERE dr.kd_wilayah=? AND dr.tahun=? AND dr.is_deleted=0$whereOpd
       GROUP BY COALESCE(dr.transaksi_uuid,CONCAT('legacy-',dr.id)),dr.kontrak_id
       $pendingFilter
       ORDER BY MAX(dr.tanggal) DESC",
      $params
    )->fetchAll();
  }

  public static function lraEligible(array $row, ?int $year = null): bool
  {
    if (($row['status_verifikasi'] ?? '') !== 'TERVERIFIKASI') return false;
    return match ((string)($row['jenis_transaksi'] ?? '')) {
      'LS', 'UANG_MUKA_KONTRAK' => !empty($row['nomor_sp2d'])
        && self::validDate((string)($row['tanggal_sp2d'] ?? ''))
        && ($year === null || (int)substr((string)$row['tanggal_sp2d'], 0, 4) === $year),
      'UP', 'TU' => !empty($row['nomor_spj'])
        && self::validDate((string)($row['tanggal_pengesahan_spj'] ?? ''))
        && ($year === null || (int)substr((string)$row['tanggal_pengesahan_spj'], 0, 4) === $year)
        && !empty($row['nomor_sp2d_sumber'])
        && !empty($row['nomor_up_tu']),
      default => false,
    };
  }
}
