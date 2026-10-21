<?php
require_once __DIR__.'/../app/Core/Controller.php';
require_once __DIR__.'/../app/Controllers/DashboardController.php';
if (!function_exists('app_url')) {function app_url($path){return '/sesendoknew'.$path;}}
function checkDashboard($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS: $message\n";}
$user=['kd_wilayah'=>'76.01','tahun'=>2026,'id'=>0,'type_user'=>'admin_opd'];
$year=2026;$title='Perencanaan';$max=1;$cards=[['tone'=>'blue','icon'=>'tasks','label'=>'Data','value'=>1,'hint'=>'']];
foreach(['perencanaan','penganggaran'] as $module){
    $dashboardStatus=['unread'=>2,'schedules'=>['rka'=>['enabled'=>false,'label'=>'RKA','status'=>'Di luar jadwal']]];
    ob_start();require __DIR__.'/../app/Views/dashboard/index.php';$html=ob_get_clean();
    checkDashboard(str_contains($html,'Disabled')&&str_contains($html,'Status Input OPD'),"$module menampilkan status input");
    checkDashboard(str_contains($html,'ui sticky dashboard-messenger-notice')&&!str_contains($html,'messenger-card"><'),"$module memakai pemberitahuan sticky tanpa panel Messenger besar");
    checkDashboard(str_contains($html,'2 pesan belum dibaca.'),"$module menampilkan jumlah pesan belum dibaca");
}
if(in_array('--database',$argv,true)){
    $controller=new DashboardController();$method=(new ReflectionClass($controller))->getMethod('statusData');
    $status=$method->invoke($controller,'perencanaan',$user);
    checkDashboard(isset($status['schedules']['renja'])&&!isset($status['schedules']['rka']),'Dashboard perencanaan membaca tahap sesuai modul');
    $status=$method->invoke($controller,'penganggaran',$user);
    checkDashboard(isset($status['schedules']['rka'],$status['schedules']['dpa'])&&is_int($status['unread']),'Dashboard penganggaran membaca jadwal server dan jumlah pesan');
}
