#!/usr/bin/env php
<?php
/** Runtime distributions only: no Node, Gulp, development packages or CDN at page load. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root=dirname(__DIR__);
$args=array_slice($argv,1);
foreach($args as$arg)if(!in_array($arg,['--update','--check','--assets-only','--help'],true)){fwrite(STDERR,"Opsi tidak dikenal: $arg\n");exit(1);}
if(in_array('--help',$args,true)){echo "php bin/dependencies.php [--update | --check] [--assets-only]\nTanpa opsi: pasang versi terkunci, gunakan cache, salin hanya berkas yang berubah.\n--update: versi stabil kompatibel terbaru; simpan lock baru.\n--check: verifikasi aset dan paket PHP; tanpa jaringan atau perubahan.\n";exit;}
$update=in_array('--update',$args,true);$check=in_array('--check',$args,true);$assetsOnly=in_array('--assets-only',$args,true);
if($update&&$check){fwrite(STDERR,"Pilih --update atau --check.\n");exit(1);}
$cache="$root/storage/dependencies";$lockFile="$root/config/assets.lock.json";$vendor="$root/public/assets/vendor";
function jsonRead(string $path):array{return json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);}
function atomicFile(string $path,string $data):void{
    if(is_file($path)&&hash_file('sha256',$path)===hash('sha256',$data))return;
    if(!is_dir(dirname($path))&&!mkdir(dirname($path),0775,true)&&!is_dir(dirname($path)))throw new RuntimeException("Tidak dapat membuat folder: $path");
    $tmp=tempnam(dirname($path),'.dependency-');
    try{if(file_put_contents($tmp,$data)===false)throw new RuntimeException("Gagal menulis: $path");chmod($tmp,0644);if(!rename($tmp,$path))throw new RuntimeException("Gagal mengganti: $path");}finally{if(is_file($tmp))unlink($tmp);}
}
function download(string $url):string{
    if(!str_starts_with($url,'https://'))throw new RuntimeException('Sumber harus HTTPS');
    $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FAILONERROR=>true,CURLOPT_CONNECTTIMEOUT=>20,CURLOPT_TIMEOUT=>180,CURLOPT_USERAGENT=>'seSendok-dependencies/1',CURLOPT_FOLLOWLOCATION=>true,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS]);
    $data=curl_exec($ch);if($data===false)throw new RuntimeException("Unduh gagal $url: ".curl_error($ch));return$data;
}
function runCommand(array $command,string $root,array $extraEnv=[]):void{
    $p=proc_open($command,[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes,$root,array_merge(getenv(),$extraEnv));
    if(!is_resource($p)||proc_close($p)!==0)throw new RuntimeException('Perintah gagal: '.implode(' ',$command));
}
function composerCommand(string $cache,string $root):array{
    $installed=trim((string)shell_exec('command -v composer 2>/dev/null'));
    if($installed!=='')return[$installed];
    $phar="$cache/tools/composer.phar";
    if(!is_file($phar)){
        $installer=download('https://getcomposer.org/installer');$sig=trim(download('https://composer.github.io/installer.sig'));
        if(!hash_equals($sig,hash('sha384',$installer)))throw new RuntimeException('Checksum installer Composer tidak sesuai');
        atomicFile("$cache/tools/installer.php",$installer);
        runCommand([PHP_BINARY,"$cache/tools/installer.php",'--quiet','--install-dir='.$cache.'/tools','--filename=composer.phar'],$root);
        unlink("$cache/tools/installer.php");
    }
    return[PHP_BINARY,$phar];
}
function archiveFile(string $root,string $path,string $archive):void{
    $source="$root/$path";if(!file_exists($source))return;
    $target="$archive/$path";if(file_exists($target))throw new RuntimeException("Arsip sudah ada: $target");
    if(!is_dir(dirname($target)))mkdir(dirname($target),0775,true);
    if(!rename($source,$target))throw new RuntimeException("Tidak dapat mengarsipkan $path");
}
try{
    $manifest=jsonRead("$root/config/dependencies.json");$old=is_file($lockFile)?jsonRead($lockFile):[];
    if($check){
        if(!$old)throw new RuntimeException('Lock aset belum dibuat');
        if(array_keys($old)!==array_keys($manifest))throw new RuntimeException('Daftar paket tidak sama dengan lock');
        $files=0;foreach($old as$name=>$package){
            if($package['version']!==$manifest[$name]['version']||($package['spec_hash']??'')!==hash('sha256',json_encode($manifest[$name])))throw new RuntimeException("Manifest/lock tidak sesuai: $name");
            foreach($package['files']as$path=>$hash){$file="$vendor/$path";if(!is_file($file)||!hash_equals($hash,hash_file('sha256',$file)))throw new RuntimeException("Aset hilang/berubah: $path");$files++;}
        }
        if(!$assetsOnly){$installed=jsonRead("$root/vendor/composer/installed.json");$versions=array_column($installed['packages']??$installed,'version','name');foreach(jsonRead("$root/composer.lock")['packages']as$p)if(($versions[$p['name']]??null)!==$p['version'])throw new RuntimeException('Versi PHP belum sesuai: '.$p['name']);}
        echo "OK: ".count($old)." pustaka browser, $files berkas terverifikasi; tanpa unduhan.\n";exit;
    }
    if(!is_dir($cache))mkdir($cache,0775,true);
    $mutex=fopen("$cache/install.lock",'c');if(!flock($mutex,LOCK_EX|LOCK_NB))throw new RuntimeException('Pemasangan dependensi lain masih berjalan');
    $next=[];$staged=[];
    foreach($manifest as$name=>&$spec){
        $known=$old[$name]??null;$meta=null;
        if($update){
            $all=json_decode(download('https://registry.npmjs.org/'.rawurlencode($name)),true,512,JSON_THROW_ON_ERROR);
            $versions=array_filter(array_keys($all['versions']),fn($v)=>preg_match('/^'.(int)$spec['major'].'\.\d+\.\d+$/',$v));
            if(!$versions)throw new RuntimeException("Versi kompatibel tidak ditemukan: $name");
            usort($versions,'version_compare');$spec['version']=end($versions);$meta=$all['versions'][$spec['version']];
        }
        if($known&&$known['version']===$spec['version']&&($known['spec_hash']??'')===hash('sha256',json_encode($spec))){
            $valid=true;foreach($known['files']as$path=>$hash)if(!is_file("$vendor/$path")||hash_file('sha256',"$vendor/$path")!==$hash){$valid=false;break;}
            if($valid){$next[$name]=$known;echo "$name {$spec['version']}: sudah sesuai, tidak diunduh\n";continue;}
        }
        $meta??=$known&&$known['version']===$spec['version']
            ?['dist'=>['tarball'=>$known['tarball'],'integrity'=>$known['integrity']]]
            :json_decode(download('https://registry.npmjs.org/'.rawurlencode($name).'/'.$spec['version']),true,512,JSON_THROW_ON_ERROR);
        $dist=$meta['dist'];$archive="$cache/npm/$name-{$spec['version']}.tgz";
        if(!is_file($archive))atomicFile($archive,download($dist['tarball']));
        [$algorithm,$digest]=explode('-',$dist['integrity'],2);
        if(!in_array($algorithm,['sha512','sha256'],true)||!hash_equals($digest,base64_encode(hash_file($algorithm,$archive,true))))throw new RuntimeException("Integritas paket gagal: $name");
        $tar=new PharData($archive);$files=$spec['files'];$directory=$spec['directory']??$name;
        if(!empty($spec['cssAssets'])){
            $css=$tar['package/semantic.min.css']->getContent();preg_match_all('/url\([\'"]?([^\)\'"\s]+)[\'"]?\)/',$css,$matches);
            foreach(array_unique($matches[1])as$path)if(str_starts_with($path,'themes/')){$path=preg_split('/[?#]/',$path)[0];$files[$path]=$path;}
        }
        $package=['version'=>$spec['version'],'spec_hash'=>hash('sha256',json_encode($spec)),'tarball'=>$dist['tarball'],'integrity'=>$dist['integrity'],'files'=>[]];
        foreach($files as$source=>$target){
            if(str_contains($source,'..')||str_contains($target,'..'))throw new RuntimeException('Lokasi aset tidak valid');
            $entry='package/'.$source;if(!isset($tar[$entry]))throw new RuntimeException("Berkas distribusi tidak ada: $name/$source");
            $content=$tar[$entry]->getContent();$path="$directory/$target";$staged[$path]=$content;$package['files'][$path]=hash('sha256',$content);
        }
        $next[$name]=$package;echo "$name {$spec['version']}: ".count($files)." berkas runtime dipersiapkan\n";
    }unset($spec);
    if(!$assetsOnly){
        $composer=composerCommand($cache,$root);$command=array_merge($composer,[$update?'update':'install','--no-dev','--prefer-dist','--no-interaction','--no-progress']);
        if($update)$command[]='--with-all-dependencies';
        runCommand($command,$root,['COMPOSER_CACHE_DIR'=>"$cache/composer"]);
    }
    foreach($staged as$path=>$content)atomicFile("$vendor/$path",$content);
    $json=fn($v)=>json_encode($v,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
    atomicFile($lockFile,$json($next));if($update)atomicFile("$root/config/dependencies.json",$json($manifest));
    atomicFile("$vendor/versions.json",$json(array_map(fn($p)=>$p['version'],$next)));
    // Known obsolete copies only; keep a recoverable private copy, never touch application code.
    $archive="$cache/archive/".date('Ymd-His');
    foreach(['public/assets/js/jquery.min.js','public/assets/js/fomantic.min.js','public/assets/css/fomantic.min.css','public/assets/css/themes','public/assets/vendor/leaflet/proj4.js','public/assets/vendor/leaflet/proj4-LICENSE','public/assets/vendor/quill']as$path)archiveFile($root,$path,$archive);
    if(is_file("$root/package.json")&&array_keys(jsonRead("$root/package.json")['dependencies']??[])===['crypto-js']){
        foreach(['package.json','package-lock.json','node_modules/crypto-js','node_modules/.package-lock.json']as$path)archiveFile($root,$path,$archive);
    }
    if(is_dir("$root/node_modules")&&!array_diff(scandir("$root/node_modules"),['.','..','.DS_Store'])){
        archiveFile($root,'node_modules/.DS_Store',$archive);rmdir("$root/node_modules");
    }
    echo "Selesai: ".count($next)." pustaka browser, ".count($staged)." berkas disinkronkan. Arsip lama: storage/dependencies/archive.\n";
}catch(Throwable $e){fwrite(STDERR,"Gagal: ".$e->getMessage()."\n");exit(1);}
