<?php

/**
 * Idempotent data migration: replace the abbreviated SSKK/SSUK masters with
 * paragraph data derived from the supplied reference documents.
 */
chdir(dirname(__DIR__, 2));
require_once 'app/Core/DB.php';
require_once 'app/Services/ProcurementReferenceTermsService.php';

$db = DB::getInstance();
$templates = [
    'SURAT_PERJANJIAN' => ProcurementReferenceTermsService::rows('SURAT_PERJANJIAN'),
    'SSKK' => ProcurementReferenceTermsService::rows('SSKK'),
    'SSUK' => ProcurementReferenceTermsService::rows('SSUK', ['jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI']),
];

$db->begin();
try {
    foreach ($templates as $code => $rows) {
        $master = $db->query(
            'SELECT id FROM master_dokumen_pengadaan_neo WHERE kode=? AND aktif=1 AND is_deleted=0 ORDER BY id DESC LIMIT 1',
            [$code]
        )->fetch();
        if (!$master) continue;
        $layout = $code === 'SSKK' ? 'sskk_three_columns' : ($code === 'SSUK' ? 'numbered_clauses' : 'contract_articles');
        $db->query(
            'UPDATE master_dokumen_pengadaan_bagian_neo SET isi_template=?,format_data=?,petunjuk_edit=?,tgl_update=NOW(),username_update=? WHERE master_id=? AND kode_bagian=? AND is_deleted=0',
            [
                json_encode($rows, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                json_encode(['editor'=>'paragraphs','layout'=>$layout,'html_input'=>false,'reference_complete'=>true], JSON_UNESCAPED_SLASHES),
                'Sunting per paragraf dengan toolbar Tata Naskah. Nomor, indentasi, dan baris baru berasal dari dokumen acuan; HTML tidak digunakan.',
                'migration_phase60',
                (int)$master['id'],
                'ISI',
            ]
        );
        $db->query(
            "UPDATE master_dokumen_pengadaan_neo SET versi='2026.3',tgl_update=NOW(),username_update='migration_phase60' WHERE id=?",
            [(int)$master['id']]
        );
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}

echo "Master SSKK dan SSUK telah diperbarui dari dokumen acuan.\n";
