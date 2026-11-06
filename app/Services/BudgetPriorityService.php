<?php

require_once __DIR__.'/../Core/DB.php';

final class BudgetPriorityService
{
    public const MANAGERS = ['super_admin','admin_wilayah','tapd'];

    public static function rows(string $region, int $year): array
    {
        return DB::getInstance()->query(
            'SELECT id,kode,uraian,aktif FROM prioritas_pembangunan_nasional_neo
             WHERE kd_wilayah=? AND tahun=? ORDER BY kode,id', [$region,$year]
        )->fetchAll();
    }

    public static function save(array $user, array $input): void
    {
        if (!in_array($user['type_user'] ?? '', self::MANAGERS, true)) {
            throw new RuntimeException('Referensi hanya dapat diubah oleh TAPD atau administrator wilayah.');
        }
        $region = (string)($user['kd_wilayah'] ?? '');
        $year = (int)($user['tahun'] ?? 0);
        if (!$region || !$year) throw new InvalidArgumentException('Wilayah dan tahun aktif wajib tersedia.');
        $id = $input['id'] ?? '';
        if ($id !== '' && !filter_var($id,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])) {
            throw new InvalidArgumentException('ID prioritas tidak valid.');
        }
        $code = is_string($input['kode'] ?? null) ? trim($input['kode']) : '';
        $name = is_string($input['uraian'] ?? null) ? trim($input['uraian']) : '';
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9.\/_-]{0,29}$/D',$code) || $name === '' || mb_strlen($name)>500) {
            throw new InvalidArgumentException('Isi kode prioritas yang valid dan uraian maksimal 500 karakter.');
        }
        $db = DB::getInstance();
        $data = ['kode'=>$code,'uraian'=>$name,'aktif'=>($input['aktif'] ?? '') === '1' ? 1 : 0,'username_update'=>$user['username'] ?? ''];
        if ($id !== '') {
            if (!$db->query('SELECT 1 FROM prioritas_pembangunan_nasional_neo WHERE id=? AND kd_wilayah=? AND tahun=?',[$id,$region,$year])->fetchColumn()) {
                throw new RuntimeException('Prioritas tidak ditemukan dalam wilayah dan tahun aktif.');
            }
            $db->update('prioritas_pembangunan_nasional_neo',$data,'WHERE id=? AND kd_wilayah=? AND tahun=?',[$id,$region,$year]);
        } else {
            $db->insert('prioritas_pembangunan_nasional_neo',$data+['kd_wilayah'=>$region,'tahun'=>$year]);
        }
    }
}
