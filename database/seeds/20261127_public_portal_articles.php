<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../../app/Core/DB.php';

$db = DB::getInstance();
$actor = 'SEED_PUBLIC_PORTAL_20261127';
$region = '76.01';
$department = '1.03.0.00.0.00.01.0000';
$articles = require __DIR__ . '/../../resources/portal/articles.php';
$previousCovers = [
    'infrastruktur-pasangkayu' => '/assets/img/portal/bundaran-smart-hero.webp',
    'ruang-publik-pasangkayu' => '/assets/img/portal/bundaran-smart.webp',
    'profil-dinas-pupr-pasangkayu' => '/assets/img/portal/kantor-bupati.webp',
    'kantor-bupati-dan-dinas-pupr-pasangkayu' => '/assets/img/portal/kantor-dinas-pupr.webp',
    'kepala-dinas-pupr-pasangkayu' => '/assets/img/portal/tim-teknis.webp',
    'panduan-lapor-infrastruktur' => '/assets/img/portal/laporan-drainase.webp',
    'pimpinan-daerah-pasangkayu' => '/assets/img/portal/foto-pimpinan-daerah-pasangkayu.webp',
];

if ((int)$db->query("SELECT GET_LOCK('sesendok_public_portal_articles', 10)")->fetchColumn() !== 1) {
    throw new RuntimeException('Impor artikel lain masih berjalan.');
}

try {
    $db->begin();
    $inserted = 0;
    $illustrated = 0;
    foreach (array_reverse($articles, true) as $slug => $entry) {
        [$type, $category, $title, $body] = $entry;
        $existing = $db->query(
            'SELECT id FROM halaman_berita WHERE kd_wilayah=? AND kd_opd=? AND slug=? LIMIT 1',
            [$region, $department, $slug]
        )->fetchColumn();
        if ($existing !== false) {
            $artwork = $entry[4] ?? null;
            if ($artwork !== null) {
                // Refresh untouched seed copies while preserving edits made through Kelola Konten.
                $update = $db->query(
                    "UPDATE halaman_berita SET gambar=?, judul=?, keterangan=?, konten=?, tgl_update=tgl_update
                     WHERE id=? AND username_insert=? AND username_update IS NULL AND tgl_update IS NULL
                       AND is_deleted=0
                       AND (gambar IS NULL OR gambar IN ('','/assets/img/portal/infrastruktur.webp','/assets/img/portal/pekerjaan-umum.webp',?)
                            OR (slug='profil-dinas-pupr-pasangkayu' AND gambar='/assets/img/portal/kantor-dinas-pupr.webp'))",
                    [$artwork, $title, $category, $body, $existing, $actor, $previousCovers[$slug] ?? null]
                );
                $illustrated += $update->rowCount();
            }
            continue;
        }

        $db->insert('halaman_berita', [
            'kd_wilayah' => $region,
            'kd_opd' => $department,
            'judul' => $title,
            'slug' => $slug,
            'jenis_halaman' => $type,
            'aktif' => 1,
            'html_aktif' => 1,
            'konten' => $body,
            'gambar' => $entry[4] ?? null,
            'keterangan' => $category,
            'username_insert' => $actor,
            'is_deleted' => 0,
        ]);
        $inserted++;
    }
    // Retire untouched placeholder articles while preserving editorial changes.
    $archive = $db->query(
        "UPDATE halaman_berita SET aktif=0
         WHERE username_insert=? AND kd_wilayah=? AND kd_opd=?
           AND aktif=1 AND is_deleted=0 AND tgl_update IS NULL AND konten LIKE ?",
        ['SEED_PUBLIC_PORTAL_20260906', $region, $department, '%Konten contoh ini dapat diperbarui melalui menu Berita%']
    );
    $db->commit();
    echo "Artikel ditambahkan: {$inserted}. Sampul ditambahkan atau diperbarui: {$illustrated}. Contoh lama dinonaktifkan: {$archive->rowCount()}.\n";
} catch (Throwable $error) {
    $db->rollback();
    throw $error;
} finally {
    $db->query("SELECT RELEASE_LOCK('sesendok_public_portal_articles')");
}
