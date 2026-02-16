<?php

/**
 * ============================================================
 * ANGARAN PARSER SERVICE
 * ============================================================
 *
 * Tugas:
 * ------------------------------------------------------------
 * - Mengurai kode bertingkat (kd_sub_keg atau kd_akun)
 * - Menghasilkan seluruh prefix level
 *
 * Class ini:
 * - TIDAK akses database
 * - TIDAK akses session
 * - Hanya manipulasi string
 *
 * Contoh:
 * Input  : 1.3.02.2.01.0093
 * Output :
 * [
 *   1,
 *   1.3,
 *   1.3.02,
 *   1.3.02.2,
 *   1.3.02.2.01,
 *   1.3.02.2.01.0093
 * ]
 *
 * Bisa dipakai untuk semua jenis kode hierarki.
 *
 * ============================================================
 */

class AnggaranParserService
{
  /**
   * Bangun seluruh level prefix dari kode.
   *
   * @param string $kode
   * @return array
   */
  public function buildHierarchy(string $kode): array
  {
    $parts = explode('.', $kode);
    $levels = [];

    for ($i = 1; $i <= count($parts); $i++) {
      $levels[] = implode('.', array_slice($parts, 0, $i));
    }

    return $levels;
  }
}
