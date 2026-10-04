<?php

/** Public database errors contain no driver diagnostics or schema names. */
final class DatabaseError extends RuntimeException
{
  public function __construct(string $message, public readonly int $httpStatus, PDOException $previous)
  {
    parent::__construct($message, 0, $previous);
  }

  public static function from(PDOException $error, bool $connection = false): self
  {
    // Do not log query parameters: they can contain passwords and personal data.
    error_log('[Database] ' . $error->getMessage());
    if ($connection) {
      return new self('Layanan data sementara tidak tersedia. Silakan coba lagi nanti.', 503, $error);
    }
    $driverCode = (int)($error->errorInfo[1] ?? 0);
    $message = match ($driverCode) {
      1062 => 'Data dengan kode atau nomor yang sama sudah tersedia.',
      1451 => 'Data masih digunakan oleh data lain dan tidak dapat dihapus.',
      1452 => 'Referensi data yang dipilih tidak tersedia.',
      1406 => 'Salah satu isian melebihi panjang maksimum yang diizinkan.',
      1292, 1366 => 'Format tanggal, angka, atau nilai isian tidak sesuai.',
      default => 'Data belum dapat diproses. Silakan coba lagi atau hubungi administrator.',
    };
    $status = in_array($driverCode, [1062, 1451, 1452, 1406, 1292, 1366], true) ? 422 : 500;
    // Only explicitly reviewed application validation messages may pass through.
    if ((string)$error->getCode() === '45000' && $driverCode === 1644) {
      $validation = (string)($error->errorInfo[2] ?? '');
      if (in_array($validation, self::VALIDATION_MESSAGES, true)) {
        $message = $validation;
        $status = 422;
      }
    }
    return new self($message, $status, $error);
  }

  private const VALIDATION_MESSAGES = [
    'Akumulasi kontrak disetujui melebihi pagu uraian DPA/DPPA',
    'Anggaran DPA tidak boleh lebih kecil dari nilai kontrak dan uraian tidak dapat dihapus',
    'Anggaran DPPA tidak boleh lebih kecil dari nilai kontrak dan uraian tidak dapat dihapus',
    'Gunakan DPPA final untuk sub kegiatan ini',
    'Kontrak hanya dapat memakai DPA/DPPA yang disetujui dan dikunci',
    'Kontrak realisasi tidak valid',
    'Kontrak tidak valid atau nilainya melebihi DPA/DPPA',
    'Nilai DPA tidak boleh lebih kecil dari nilai kontrak',
    'Nilai DPPA tidak boleh lebih kecil dari nilai kontrak',
    'Nilai kontrak melebihi anggaran DPA/DPPA',
    'Nilai kontrak uraian melebihi pagu DPA/DPPA tersedia',
    'Nilai kontrak uraian tidak valid atau melebihi pagu tersedia',
    'PPK dan PPTK wajib dihubungkan ke sub kegiatan',
    'Pagu DPA tidak boleh lebih kecil dari akumulasi kontrak yang disetujui',
    'Pagu DPPA tidak boleh lebih kecil dari akumulasi kontrak yang disetujui',
    'Penyedia referensi tidak valid',
    'Persetujuan ditolak: akumulasi kontrak uraian melebihi pagu DPA/DPPA',
    'Progress fisik harus 0 sampai 100',
    'Realisasi melebihi nilai kontrak',
    'Realisasi melebihi nilai kontrak atau kontrak tidak valid',
    'Rincian kontrak hanya dapat memakai DPA/DPPA yang disetujui dan dikunci',
    'Sumber kontrak harus DPA/DPPA yang disetujui',
    'Tanggal berakhir jabatan tidak boleh sebelum tanggal mulai',
    'Uraian DPA sudah berkontrak dan tidak dapat dihapus',
    'Uraian DPA sudah terhubung kontrak dan tidak dapat dihapus',
    'Uraian DPA/DPPA tidak ditemukan atau belum disetujui',
    'Uraian DPPA sudah berkontrak dan tidak dapat dihapus',
    'Uraian DPPA sudah terhubung kontrak dan tidak dapat dihapus',
  ];
}
