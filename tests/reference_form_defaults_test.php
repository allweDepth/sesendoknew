<?php
require_once __DIR__.'/../app/Services/DynamicTableService.php';
$_SESSION['user']=['type_user'=>'tapd','username'=>'REFERENCE_FORM_TEST','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026];
$s=new DynamicTableService();$normalize=new ReflectionMethod($s,'normalizeReferenceComponents');$rules=new ReflectionMethod($s,'buildRulesFromSchema');
foreach(['akun_neo','aset_neo','sumber_dana_neo']as$table){$r=$normalize->invoke($s,$table,['kode'=>'1.2.03.04.005.0006']);$first=$table==='sumber_dana_neo'?'sumber_dana':'akun';if($r[$first]!==1||$r['kelompok']!==2||$r['jenis_akun']!==3||$r['sub_rincian_objek']!==6)throw new RuntimeException('Reference components do not match code');$r=$normalize->invoke($s,$table,['kode'=>'1']);if($r['sub_rincian_objek']!==0)throw new RuntimeException('Short code has invalid absent components');}
$failed=false;try{$normalize->invoke($s,'akun_neo',['kode'=>'ABC']);}catch(FieldValidationException$e){$failed=isset($e->errors['kode']);}if(!$failed)throw new RuntimeException('Invalid code accepted');
$r=$rules->invoke($s,'pohon_kinerja_neo');if(in_array('required',$r['sumber_ref']??[],true))throw new RuntimeException('Database default treated as missing required input');
$r=$rules->invoke($s,'akun_neo');if(!in_array('required',$r['peraturan']??[],true))throw new RuntimeException('Required regulation lost validation');
echo "PASS: Derived reference components, invalid-code field errors and schema-default validation\n";
