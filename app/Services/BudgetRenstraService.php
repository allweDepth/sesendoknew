<?php

final class BudgetRenstraService
{
    public static function options(DB $db, array $scope, string $program, string $activity, string $sub): array
    {
        [$region,$opd,$year] = $scope;
        $period = $db->query(
            'SELECT r.id,p.periode_mulai FROM renstra_neo r
             JOIN periode_rpjmd p ON p.id=r.periode_id AND p.kd_wilayah=r.kd_wilayah
             WHERE r.kd_wilayah=? AND r.kd_opd=? AND ? BETWEEN p.periode_mulai AND p.periode_selesai
             AND p.status_aktif=1 AND r.is_deleted=0 AND COALESCE(r.disable,0)=0
             ORDER BY p.periode_mulai DESC,r.id DESC LIMIT 1', [$region,$opd,$year]
        )->fetch();
        $offset = $period ? $year - (int)$period['periode_mulai'] + 1 : 0;
        if ($offset < 1 || $offset > 5) return ['program'=>[], 'sub'=>[]];
        $target = "target_t$offset";
        $chain = 'FROM misi_renstra_neo m
            JOIN tujuan_renstra_neo t ON t.misi_id=m.id AND t.is_deleted=0 AND COALESCE(t.disable,0)=0
            JOIN sasaran_renstra_neo s ON s.tujuan_id=t.id AND s.is_deleted=0 AND COALESCE(s.disable,0)=0
            JOIN program_renstra_neo p ON p.sasaran_id=s.id AND p.is_deleted=0 AND COALESCE(p.disable,0)=0';
        $filter = 'WHERE m.renstra_id=? AND p.kode_program=? AND m.is_deleted=0 AND COALESCE(m.disable,0)=0
            AND i.is_deleted=0 AND COALESCE(i.disable,0)=0';
        return [
            'program'=>$db->query(
                "SELECT i.id renstra_id,i.nama_indikator tolok_ukur,COALESCE(i.satuan,'') satuan,i.$target target
                 $chain JOIN indikator_program_renstra_neo i ON i.program_id=p.id $filter ORDER BY i.id",
                [$period['id'],$program]
            )->fetchAll(),
            'sub'=>$db->query(
                "SELECT i.id renstra_id,i.indikator_keluaran tolok_ukur,COALESCE(i.satuan,'') satuan,i.$target target
                 $chain JOIN kegiatan_renstra_neo k ON k.program_id=p.id AND k.is_deleted=0 AND COALESCE(k.disable,0)=0
                 JOIN sub_kegiatan_renstra_neo i ON i.kegiatan_renstra_id=k.id
                 JOIN rekening_kegiatan rk ON rk.id=i.master_sub_kegiatan_id
                 $filter AND k.kode_kegiatan=? AND rk.kode=? AND TRIM(i.indikator_keluaran)<>'' ORDER BY i.id",
                [$period['id'],$program,$activity,$sub]
            )->fetchAll(),
        ];
    }

    public static function resolve(array $rows, array $options, bool $matchLegacy): array
    {
        $byId = array_column($options,null,'renstra_id');
        foreach ($rows as &$row) {
            $reference = $byId[$row['renstra_id'] ?? 0] ?? null;
            if (!$reference && $matchLegacy && !empty($row['tolok_ukur'])) {
                $matches = array_values(array_filter($options, static fn($option) =>
                    self::label($option['tolok_ukur'], $option['satuan']) === self::label($row['tolok_ukur'], $option['satuan'])
                    && (empty($row['satuan']) || strcasecmp(trim($row['satuan']), trim($option['satuan'])) === 0)
                ));
                if (count($matches) === 1) $reference = $matches[0];
            }
            if ($reference) {
                $row = array_replace($reference, ['target'=>$row['target'] ?? '']);
            } else {
                unset($row['renstra_id']);
            }
        }
        return $rows;
    }

    private static function label(string $text, string $unit): string
    {
        $text = preg_replace('/^\s*\d+\.\s*/u','',trim($text));
        if ($unit !== '') $text = preg_replace('/\s*\(\s*'.preg_quote(trim($unit),'/').'\s*\)$/iu','',$text);
        return mb_strtolower(preg_replace('/\s+/u',' ',trim($text)));
    }
}
