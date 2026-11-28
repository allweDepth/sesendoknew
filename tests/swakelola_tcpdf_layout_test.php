<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/Services/SwakelolaDocumentService.php';
$dir=__DIR__.'/../outputs/swakelola-tcpdf-20261128/regression';@mkdir($dir,0775,true);
$f=['font'=>'Arial','size'=>11,'line_height'=>1.15,'before'=>0,'after'=>8,'left'=>0,'right'=>0,'first'=>0];
$p=fn($text,$extra=[])=>['type'=>'paragraph','text'=>$text,'align'=>'left','word_format'=>array_replace($f,$extra)];
$cell=fn($text)=>['colspan'=>1,'blocks'=>[$p($text)]];
$table=['type'=>'table','column_widths'=>[15,55,30],'rows'=>[[$cell('KOLOM_A'),$cell('KOLOM_B'),$cell('KOLOM_C')],[$cell('Isi A'),$cell('Isi B'),$cell('Isi C')]],'borderless'=>false];
$number=$p('1.1 AWAL_BUTIR '.str_repeat('teks lanjutan yang harus sejajar dengan awal isi butir ',5),['left'=>40,'first'=>-20,'prefix'=>'1.1 ']);
$sign=['type'=>'table','borderless'=>true,'column_widths'=>[50,50],
 'rows'=>[[
  ['colspan'=>1,'blocks'=>[$p('Pejabat Pembuat Komitmen'),$p(''),$p('NAMA_PPK')]],
  ['colspan'=>1,'blocks'=>[$p('Pelaksana Swakelola'),$p(''),$p('NAMA_PELAKSANA')]],
 ]]];
$blocks=[$p('JUDUL_UJI',['size'=>14,'after'=>12]),$number,$p('1.2 BUTIR_BERIKUTNYA',['left'=>40,'first'=>-20,'prefix'=>'1.2 ']),$p('• AWAL_BULLET '.str_repeat('butir bullet memiliki baris lanjutan yang tetap sejajar ',3),['left'=>40,'first'=>-20,'prefix'=>'• ']),$table,$p('PARAGRAF_TAMBAHAN_BEBAS'),$p('Demikian disampaikan untuk dilaksanakan.'),$sign];
$d=['kode_dokumen'=>'SW_TEST','kontrak_id'=>0,'judul'=>'Uji paragraf TCPDF','sections'=>[['kode_bagian'=>'ISI','isi'=>json_encode($blocks)]]];
$setup=['paper'=>'F4','orientation'=>'P','margin_left'=>20,'margin_right'=>15,'margin_top'=>15,'margin_bottom'=>15,'header_enabled'=>false,'footer_enabled'=>false];
putenv('SWAKELOLA_WORD_PYTHON=/not-used');putenv('SWAKELOLA_WORD_SOFFICE=/not-used');
file_put_contents($dir.'/manual-before.pdf',SwakelolaDocumentService::render($d,[],[], $setup));
$blocks[4]['column_widths']=[30,25,45];$d['sections'][0]['isi']=json_encode($blocks);file_put_contents($dir.'/manual-after.pdf',SwakelolaDocumentService::render($d,[],[],$setup));
$setup['paper']='A5';file_put_contents($dir.'/paper-a5.pdf',SwakelolaDocumentService::render($d,[],[],$setup));
$flow=[];for($i=1;$i<=80;$i++)$flow[]=$p(sprintf('FLOW_%03d ', $i).str_repeat('Kalimat harus mengalir sebagai paragraf terpisah tanpa saling menimpa. ',3));
$flow[]=$p('Demikian berita acara ini disampaikan.');$flow[]=$sign;
$d['sections'][0]['isi']=json_encode([['type'=>'table','borderless'=>false,'column_widths'=>[100],'rows'=>[[['colspan'=>1,'blocks'=>$flow]]]]]);
$setup['paper']='F4';file_put_contents($dir.'/flowing-frame.pdf',SwakelolaDocumentService::render($d,[],[],$setup));
$many=[];for($i=1;$i<=60;$i++)$many[]=[ $cell(sprintf('ROW_%03d',$i)), $cell('X'), $cell('Y') ];
$inside=['type'=>'table','column_widths'=>[30,35,35],'rows'=>$many,'borderless'=>false];
$d['sections'][0]['isi']=json_encode([['type'=>'table','borderless'=>false,'column_widths'=>[100],'rows'=>[[['colspan'=>1,'blocks'=>[$inside,$p('SETELAH_TABEL'),$sign]]]]]]);
file_put_contents($dir.'/nested-frame.pdf',SwakelolaDocumentService::render($d,[],[],$setup));
foreach(['I','II','IV']as$type){$code='SW_'.$type.'_01_07';$custom=SwakelolaTcpdfLayoutService::decorate($code,[$table]);if($custom[0]['column_widths']!==[15,55,30])throw new RuntimeException('Lebar manual ditimpa format Word.');}
echo "TCPDF fixtures written; no Word runtime; manual widths preserved.\n";

if(in_array('--database',$argv,true)){
 require_once __DIR__.'/../app/Core/DB.php';$db=DB::getInstance();$part=$db->query('SELECT p.id FROM dokumen_pengadaan_bagian_neo p JOIN dokumen_pengadaan_neo d ON d.id=p.dokumen_id WHERE d.kontrak_id=30 AND d.is_deleted=0 AND p.is_deleted=0 LIMIT 1')->fetch();
 if(!$part)throw new RuntimeException('Bagian uji tidak ditemukan');
 $blocks[4]['rows'][0][0]['word_cell']=['valign'=>'center','borders'=>['bottom'=>['style'=>'single','size'=>0.5,'color'=>'000000']]];
 $db->begin();try{SwakelolaDocumentService::storeBlocks($db,'dokumen_bagian_id',(int)$part['id'],$blocks);$loaded=SwakelolaDocumentService::loadBlocks($db,'dokumen_bagian_id',(int)$part['id']);if($loaded!=$blocks)throw new RuntimeException('Format atau lebar berubah dalam database.');}finally{$db->rollback();}
 echo "PASS: database preserves custom widths, paragraph formats and cell formats; transaction rolled back.\n";
}

$sectionBody=[];for($i=1;$i<=45;$i++)$sectionBody[]=$p(sprintf('SEC_FLOW_%03d ', $i).str_repeat('Uraian tetap di kolom kanan setelah pergantian halaman. ',3));
$d['sections'][0]['isi']=json_encode([['type'=>'table','layout'=>'kak_sections','column_widths'=>[28,72],'borderless'=>true,'rows'=>[[['colspan'=>1,'blocks'=>[$p('LABEL_KAK')]],['colspan'=>1,'blocks'=>$sectionBody]]]]]);
file_put_contents($dir.'/kak-columns.pdf',SwakelolaDocumentService::render($d,[],[],$setup));
$closing=[];for($i=0;$i<24;$i++)$closing[]=$p('PENGANTAR '.str_repeat('Isi pengantar harus tetap terbaca dan terpisah. ',2));
$closing[]=$p('Dengan demikian CLOSING_GROUP para pihak menyepakati seluruh ketentuan pekerjaan dan menandatangani dokumen ini sesuai peraturan yang berlaku.');for($i=0;$i<12;$i++)$closing[]=$p('');$closing[]=$sign;
$d['sections'][0]['isi']=json_encode($closing);file_put_contents($dir.'/closing-signature.pdf',SwakelolaDocumentService::render($d,[],[],$setup));
