<?php

$assert=static function(bool $ok,string $message):void{
  if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}
  echo "PASS: $message\n";
};

require_once __DIR__.'/../app/Services/DynamicTable/DynamicSanitizer.php';

$profileService=new class {
  public function getProfileByTable(string $table):array
  {
    return ['sanitize'=>['konten'=>['html'=>true]]];
  }
};
$sanitizer=new App\Services\DynamicTable\DynamicSanitizer($profileService);
$payload='<p style="text-align:center" onclick="x()"><strong>Judul</strong></p>'
  .'<ul><li>Butir</li></ul><table><tr><td>Kolom</td></tr></table>'
  .'<img src="javascript:alert(1)" onerror="alert(2)"><script>alert(3)</script>';
$clean=$sanitizer->applySanitization('halaman_berita',['konten'=>$payload])['konten'];

$assert(str_contains($clean,'<strong>Judul</strong>'),'format teks berita dipertahankan');
$assert(str_contains($clean,'<ul><li>Butir</li></ul>'),'bullet/list berita dipertahankan');
$assert(str_contains($clean,'<table><tr><td>Kolom</td></tr></table>'),'tabel berita dipertahankan');
$assert(str_contains($clean,'text-align:center'),'style aman berita dipertahankan');
$assert(!str_contains($clean,'script')&&!str_contains($clean,'onclick')&&!str_contains($clean,'onerror'),'script dan event berbahaya dibuang');
$assert(!str_contains($clean,'javascript:'),'URL berbahaya dibuang');

$profile=file_get_contents(__DIR__.'/../app/Config/table_profiles.php');
$news=file_get_contents(__DIR__.'/../public/assets/js/modules/halaman_berita.js');
$editor=file_get_contents(__DIR__.'/../public/assets/js/ui/rich-document-editor.js');
$assert(str_contains($profile,"'konten' => ['html' => true]"),'sanitasi HTML hanya diaktifkan untuk konten berita');
$assert(str_contains($profile,"'keterangan',"),'keterangan dipilih kembali pada daftar berita');
$assert(str_contains($news,'news-editor-sidebar'),'mode khusus sidebar berita tersedia');
$assert(!str_contains($news,'$(".sidebarkanan .flyout-footer").show()'),'footer Submit tidak ditampilkan saat animasi penutupan');
$assert(str_contains($news,'onHidden:()=>this.restoreSidebar()'),'footer dipulihkan sesudah sidebar benar-benar tertutup');
$assert(str_contains($editor,'max-height:none!important'),'menu dropdown berita tidak dipotong tinggi panel');

echo "PHASE 38 NEWS EDITOR PERSISTENCE TESTS COMPLETE\n";
