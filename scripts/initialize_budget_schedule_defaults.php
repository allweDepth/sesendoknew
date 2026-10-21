<?php
// Fill only missing schedule pairs in the latest regional configuration for a year.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__.'/../app/Services/BudgetScheduleService.php';
if (count($argv)!==3 || !preg_match('/^\d{4}$/',$argv[2])) {fwrite(STDERR,"Usage: php scripts/initialize_budget_schedule_defaults.php REGION YEAR\n");exit(1);}
$user=['type_user'=>'admin_wilayah','kd_wilayah'=>$argv[1],'tahun'=>(int)$argv[2],'username'=>'budget_schedule_defaults'];
BudgetScheduleService::applyDefaults(DB::getInstance(),$user);
echo "Default jadwal kosong diterapkan untuk {$argv[1]} / {$argv[2]}; rentang dan kunci yang ada dipertahankan.\n";
