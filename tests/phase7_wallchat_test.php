<?php
require_once __DIR__.'/../app/Core/DB.php';require_once __DIR__.'/../app/Models/WallchatModel.php';
$db=DB::getInstance();$model=new WallchatModel();$assert=static function(bool $ok,string $m):void{if(!$ok){fwrite(STDERR,"FAIL: $m\n");exit(1);}echo "PASS: $m\n";};
$users=$db->query('SELECT id,username FROM user_sesendok_biila ORDER BY id LIMIT 2')->fetchAll();$assert(count($users)===2,'dua user pengujian tersedia');
$db->query("DELETE FROM wallchat WHERE content LIKE 'TRACE_TEST Phase 7%'");
$model->store(['user_id'=>$users[0]['id'],'username'=>$users[0]['username'],'content'=>'TRACE_TEST Phase 7 status','type'=>'status']);$status=(int)$db->lastInsertId();
$model->store(['user_id'=>$users[1]['id'],'username'=>$users[1]['username'],'parent_id'=>$status,'content'=>'TRACE_TEST Phase 7 comment','type'=>'comment']);$comment=(int)$db->lastInsertId();
$model->store(['user_id'=>$users[0]['id'],'username'=>$users[0]['username'],'receiver_id'=>$users[1]['id'],'content'=>'TRACE_TEST Phase 7 private','type'=>'private']);$private=(int)$db->lastInsertId();
$feeds=$model->getFeeds();$ids=array_column($feeds,'id');$assert(in_array($status,$ids),'status tampil di feed');$assert(!in_array($private,$ids),'pesan pribadi tidak bocor ke feed publik');
$found=array_values(array_filter($feeds,fn($f)=>(int)$f['id']===$status))[0];$assert(count($found['comments'])===1&&(int)$found['comments'][0]['id']===$comment,'komentar terhubung ke status');
$messages=$model->getPrivateMessages((int)$users[1]['id']);$assert(in_array($private,array_column($messages,'id')),'penerima dapat membaca pesan pribadi');
$model->delete($status,(int)$users[1]['id']);$assert((int)$db->query('SELECT is_deleted FROM wallchat WHERE id=?',[$status])->fetch()['is_deleted']===0,'user lain tidak dapat menghapus status');
$model->delete($status,(int)$users[0]['id']);$assert((int)$db->query('SELECT is_deleted FROM wallchat WHERE id=?',[$status])->fetch()['is_deleted']===1,'pemilik dapat menghapus status');
$routes=file_get_contents(__DIR__.'/../routes/web.php');foreach(['/wallchat/feed','/wallchat/store','/wallchat/comment','/wallchat/private','/wallchat/delete'] as $route)$assert(str_contains($routes,"'$route'"),"route $route tersedia");
echo "PHASE 7 TESTS COMPLETE\n";
