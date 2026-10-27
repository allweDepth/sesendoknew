<?php
/** Align stored forms/cache with the native non-SK renderer. No document body is modified. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../../app/Core/DB.php';
require_once __DIR__.'/../../app/Services/TataNaskahFormatService.php';
$db=DB::getInstance();$db->begin();$updated=[];
try{
    foreach($db->query('SELECT * FROM ref_jenis_naskah ORDER BY id FOR UPDATE')->fetchAll() as $type){
        if(TataNaskahFormatService::isDecision($type))continue;
        $schema=TataNaskahFormatService::schema((int)$type['id']);if(!$schema)continue;
        $json=json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        if(json_decode($type['schema_json']??'[]',true)===$schema)continue;
        $version=(int)$type['schema_version']+1;
        $db->update('ref_jenis_naskah',['schema_json'=>$json,'schema_version'=>$version],'WHERE id=?',[$type['id']]);
        $db->query('DELETE FROM cache_schema_naskah WHERE jenis_id=?',[$type['id']]);
        $db->insert('cache_schema_naskah',['jenis_id'=>$type['id'],'schema_version'=>$version,'schema_json'=>$json,'tgl_insert'=>date('Y-m-d H:i:s')]);
        $updated[]=(int)$type['id'];
    }
    $db->commit();echo json_encode(['updated_types'=>$updated,'sk_unchanged'=>true]),PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
