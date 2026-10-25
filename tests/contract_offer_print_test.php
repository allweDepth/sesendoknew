<?php
require_once __DIR__.'/../app/Services/ContractOfferPrintService.php';
$db=DB::getInstance();
$c=$db->query("SELECT k.* FROM kontrak_neo k JOIN rekanan_neo v ON v.id=k.rekanan_id AND v.is_deleted=0 WHERE k.is_deleted=0 AND EXISTS(SELECT 1 FROM rab_paket_neo r WHERE r.kontrak_id=k.id AND r.is_deleted=0 AND r.jumlah_penawaran>0) ORDER BY k.id DESC LIMIT 1")->fetch();
if(!$c)throw new RuntimeException('A contract with an offer RAB is required');
$u=['type_user'=>'admin_opd','username'=>'OFFER_PRINT_TEST','kd_wilayah'=>$c['kd_wilayah'],'kd_opd'=>$c['kd_opd'],'tahun'=>$c['tahun']];
$oldSession=$_SESSION??[];$_SESSION['user']=$u;$s=new ContractOfferPrintService($u);$db->begin();
try {
    $s->save((int)$c['id'],['nomor_surat'=>'QA-SPSE','tanggal_surat'=>'2026-10-26','masa_berlaku'=>20]);
    ContractPrintSettingsService::save((int)$c['id'],['line_spacing'=>1.5],'QA');
    if($s->get((int)$c['id'])['nomor_surat']!=='QA-SPSE')throw new RuntimeException('Paragraph settings erased offer settings');
    foreach(['bundle','letter','rab'] as $kind)if(!str_starts_with($s->pdf((int)$c['id'],$kind),'%PDF-'))throw new RuntimeException('Invalid native PDF');
    foreach([['tanggal_surat'=>'2026-02-30'],['masa_berlaku'=>0],['nomor_surat'=>'']] as $bad){
        $failed=false;try{$s->save((int)$c['id'],array_replace(['nomor_surat'=>'QA','tanggal_surat'=>'2026-10-26','masa_berlaku'=>20],$bad));}catch(FieldValidationException $e){$failed=(bool)$e->errors;}
        if(!$failed)throw new RuntimeException('Invalid field accepted');
    }
    $_SESSION['user']=array_replace($u,['kd_opd'=>'UNRELATED']);$failed=false;
    try{(new ContractOfferPrintService($_SESSION['user']))->get((int)$c['id']);}catch(RuntimeException $e){$failed=true;}
    if(!$failed)throw new RuntimeException('Cross-OPD data exposed');
    echo "PASS: Native letter/RAB PDFs, settings preservation, field errors and OPD boundary\n";
}finally{$db->rollback();$_SESSION=$oldSession;}
