#!/usr/bin/env php
<?php
/** Run once on a local Mac after copying the checkout to a new device. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$target=dirname(__DIR__).'/config/database.local.php';
if (is_file($target)) { fwrite(STDERR,"Konfigurasi sudah ada; tidak ditimpa.\n"); exit(1); }
$socket=getenv('SESENDOK_SETUP_SOCKET')?:'/tmp/mysql.sock';
$database=getenv('SESENDOK_SETUP_DATABASE')?:'sesendoknew_db';
if (!preg_match('/^[a-zA-Z0-9_]+$/',$database)) throw new RuntimeException('Nama database tidak valid');
$pdo=new PDO('mysql:unix_socket='.$socket.';dbname='.$database,getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
]);
// Authentication requires this table on devices with an older database copy.
$migration=dirname(__DIR__).'/database/migrations/20261004_phase61_auth_rate_limits.sql';
$pdo->exec(file_get_contents($migration));
$username='sesendok_local_'.bin2hex(random_bytes(4));
$password=bin2hex(random_bytes(24));
$account=$pdo->quote($username)."@'localhost'";
$createdAccount=false;
$createdFile=false;
try {
  $pdo->exec('CREATE USER '.$account.' IDENTIFIED BY '.$pdo->quote($password));
  $createdAccount=true;
  // No global permissions, GRANT OPTION, account administration or schema deletion.
  $pdo->exec('GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON `'.$database.'`.* TO '.$account);
  $config=['host'=>'127.0.0.1','dbname'=>$database,'username'=>$username,'password'=>$password,'socket'=>$socket];
  $handle=fopen($target,'x');
  if(!$handle)throw new RuntimeException('Konfigurasi tidak dapat dibuat');
  $createdFile=true;
  chmod($target,0600);
  fwrite($handle,"<?php\n// Kredensial privat perangkat ini, diabaikan Git.\nreturn ".var_export($config,true).";\n");
  fclose($handle);
  if(PHP_OS_FAMILY==='Darwin'){
    exec('/bin/chmod +a '.escapeshellarg('_www allow read').' '.escapeshellarg($target),$output,$status);
    if($status!==0)throw new RuntimeException('Izin baca proses web tidak dapat diterapkan');
  }
  echo "Konfigurasi lokal berhasil dibuat; password tidak ditampilkan.\n";
} catch(Throwable $error) {
  // Roll back the new account and its private file only; never touch existing users.
  if($createdAccount)$pdo->exec('DROP USER IF EXISTS '.$account);
  if($createdFile&&is_file($target))unlink($target);
  fwrite(STDERR,"Penyiapan gagal; akun baru dibatalkan.\n");
  exit(1);
}
