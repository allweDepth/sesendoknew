<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Models/WallchatModel.php';
$db=DB::getInstance();$model=new WallchatModel();
$ok=static function($v,$m){if(!$v)throw new RuntimeException('FAIL: '.$m);echo "PASS: $m\n";};
$users=$db->query('SELECT id,username FROM user_sesendok_biila ORDER BY id LIMIT 2')->fetchAll();$ok(count($users)===2,'dua pengguna tersedia');
$id=$model->store(['user_id'=>$users[0]['id'],'receiver_id'=>$users[1]['id'],'username'=>$users[0]['username'],'type'=>'private','content'=>'TRACE_PHASE11 rahasia','is_ephemeral'=>1]);
$row=$db->query('SELECT * FROM wallchat WHERE id=?',[$id])->fetch();
$ok(($row['content']??'')===''&&!empty($row['content_ciphertext'])&&!str_contains($row['content_ciphertext'],'rahasia'),'plaintext tidak tersimpan di database');
$messages=$model->getPrivateMessages((int)$users[1]['id']);$found=array_values(array_filter($messages,fn($x)=>(int)$x['id']===(int)$id));$ok(($found[0]['content']??'')==='TRACE_PHASE11 rahasia','penerima berwenang dapat mendekripsi');
$ok($model->markRead((int)$id,(int)$users[1]['id']),'penerima dapat menandai dibaca');
$visible=$model->getPrivateMessages((int)$users[1]['id']);$ok(!in_array((int)$id,array_map('intval',array_column($visible,'id')),true),'pesan sementara hilang dari penerima setelah dibaca');
$ok($model->deletePrivate((int)$id,(int)$users[0]['id']),'pengirim dapat menghapus salinan');
$gone=$db->query('SELECT is_deleted,content_ciphertext FROM wallchat WHERE id=?',[$id])->fetch();$ok((int)$gone['is_deleted']===1&&$gone['content_ciphertext']===null,'ciphertext dimusnahkan setelah kedua pihak menghapus');
$routes=file_get_contents(__DIR__.'/../routes/web.php');foreach(['/wallchat/private/read','/wallchat/private/delete','/wallchat/private/file'] as $route)$ok(str_contains($routes,"'$route'"),"route $route tersedia");
$router=file_get_contents(__DIR__.'/../app/Core/Router.php');$ok(!str_contains($router,"str_contains(\$uri, '/store')"),'router tidak memblokir route resmi berdasarkan nama');
echo "PHASE 11 TESTS COMPLETE\n";
