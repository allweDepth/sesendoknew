<?php
/** Stable OPD hierarchy for the ASN list; no employee data is rewritten. */
final class AsnTableOrderService
{
    public static function sql(): string
    {
        // Group 1–3 are the established OPD leadership classifications.
        // Position text distinguishes supervisors and functional levels within ASN.
        return "CASE
            WHEN kelompok=1 THEN 1
            WHEN kelompok=2 THEN 2
            WHEN kelompok=3 THEN 3
            WHEN kelompok=5 THEN 20
            WHEN LOWER(TRIM(jabatan)) REGEXP '^(kepala[[:space:]]+upt|ka[.]?[[:space:]]*upt)' THEN 4
            WHEN LOWER(TRIM(jabatan)) REGEXP '^((kasub[a-z]*|kasi)([[:space:]]|$)|kepala[[:space:]]+(sub|seksi)([[:space:]]|$))' THEN 5
            WHEN LOWER(jabatan) LIKE '%ahli utama%' THEN 6
            WHEN LOWER(jabatan) LIKE '%ahli madya%' THEN 7
            WHEN LOWER(jabatan) LIKE '%ahli muda%' THEN 8
            WHEN LOWER(jabatan) LIKE '%ahli pertama%' THEN 9
            ELSE 10 END ASC,
            CAST(golongan AS UNSIGNED) DESC, LOWER(TRIM(ruang)) DESC,
            nama ASC, id ASC";
    }
}
