<?php
// Local secrets stay outside Git. Production may provide environment variables.
$localFile = __DIR__ . '/database.local.php';
$config = is_file($localFile) ? require $localFile : [
    'host' => '127.0.0.1', 'dbname' => 'sesendoknew_db',
    'username' => 'sesendok_app', 'password' => '', 'socket' => '',
];
foreach (['host' => 'SESENDOK_DB_HOST', 'dbname' => 'SESENDOK_DB_NAME',
    'username' => 'SESENDOK_DB_USER', 'password' => 'SESENDOK_DB_PASSWORD',
    'socket' => 'SESENDOK_DB_SOCKET'] as $key => $variable) {
    $value = getenv($variable);
    if ($value !== false) $config[$key] = $value;
}
return $config;
