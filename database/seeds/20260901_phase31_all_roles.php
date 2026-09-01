<?php
require_once __DIR__.'/../../app/Core/DB.php';

$db=DB::getInstance();
$template=$db->query('SELECT * FROM user_sesendok_biila WHERE disable=0 ORDER BY id LIMIT 1')->fetch();
if(!$template)throw new RuntimeException('Template pengguna tidak tersedia');
$scope=$db->query("SELECT u.kd_wilayah,u.kd_opd,u.tahun FROM user_sesendok_biila u JOIN dpa_neo d ON d.kd_wilayah=u.kd_wilayah AND d.kd_opd=u.kd_opd AND d.tahun=u.tahun AND d.is_deleted=0 WHERE u.kd_opd IS NOT NULL AND u.kd_opd<>'' AND u.kd_opd<>'0' AND u.disable=0 GROUP BY u.kd_wilayah,u.kd_opd,u.tahun ORDER BY u.tahun DESC,COUNT(d.id) DESC LIMIT 1")->fetch()
    ?:['kd_wilayah'=>$template['kd_wilayah'],'kd_opd'=>$template['kd_opd'],'tahun'=>$template['tahun']];
$regional=['super_admin','admin_wilayah','tapd'];
$roles=['super_admin','admin_wilayah','tapd','admin_opd','kepala_opd','pa_kpa','ppk','pptk','ppk_skpd','bendahara','pejabat_pengadaan','staf_opd','viewer'];

foreach($roles as $i=>$role){
    $username='demo_'.$role;
    $scopeData=['type_user'=>$role,'kd_wilayah'=>$scope['kd_wilayah'],'kd_opd'=>in_array($role,$regional,true)?'0':$scope['kd_opd'],'tahun'=>$scope['tahun'],'disable'=>0,'disable_login'=>0];
    $existing=$db->query('SELECT id FROM user_sesendok_biila WHERE username=?',[$username])->fetch();
    if($existing){$db->update('user_sesendok_biila',$scopeData,'WHERE id=?',[$existing['id']]);continue;}
    $row=$template;unset($row['id']);
    $row=array_merge($row,$scopeData,[
        'username'=>$username,'email'=>$username.'@sesendok.local','password'=>password_hash('DemoRole!2026',PASSWORD_DEFAULT),
        'nama'=>'DEMO '.strtoupper(str_replace('_',' ',$role)),'nip'=>'D'.date('ymd').str_pad((string)$i,4,'0',STR_PAD_LEFT),
        'tgl_daftar'=>date('Y-m-d H:i:s')
    ]);
    $db->insert('user_sesendok_biila',$row);
}
echo "PHASE 31 ROLE USERS SEEDED\n";
