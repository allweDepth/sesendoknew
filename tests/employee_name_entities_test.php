<?php
require_once __DIR__.'/../app/Services/ContractOfficialService.php';
require_once __DIR__.'/../app/Services/DynamicTable/DynamicSanitizer.php';
function check($a,$b){if($a!==$b)throw new RuntimeException(var_export([$a,$b],true));}
foreach(["Serius pahu&#39;u","Serius pahu&amp;#39;u","SERIUS PAHU’U","Serius Pahu'u"] as$name){$expected=str_contains($name,'’')?'Serius Pahu’u':"Serius Pahu'u";check(TataNaskahFormatService::personName($name),$expected);check(ContractOfficialService::employeeName(['nama'=>$name,'gelar'=>'ST']),$expected.', ST');}
check(ContractOfficialService::employeeName(['nama'=>'SYAMSUNAR','gelar'=>'SP.,M.M']),'Syamsunar, SP.,M.M');
$s=new App\Services\DynamicTable\DynamicSanitizer(new class{public function getProfileByTable($t){return [];}});
foreach(['db_asn_pemda_neo'=>'nama','pejabat_tahunan_neo'=>'nama_pegawai']as$table=>$field){$v=$s->applySanitization($table,[$field=>"Serius Pahu&amp;#39;u",'nip'=>'198311112014101003']);check($v[$field],"Serius Pahu'u");check($v['nip'],'198311112014101003');check($s->applySanitization($table,[$field=>'&lt;b&gt;Serius&lt;/b&gt; Pahu&#39;u'])[$field],"Serius Pahu'u");}
echo "Nama ASN/kontrak/Tata Naskah: apostrof biasa, Unicode, entitas berulang, gelar dan sanitasi tag lulus.\n";
