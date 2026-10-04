<?php
require_once __DIR__ . '/../app/Controllers/MapsController.php';
require_once __DIR__ . '/../app/Core/RequestGuard.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Services/FileService.php';

$assert = static function (bool $ok, string $message): void {
  if (!$ok) throw new RuntimeException('FAIL: ' . $message);
  echo "PASS: $message\n";
};
$log = tempnam(sys_get_temp_dir(), 'sesendok-security-');
$oldLog = ini_get('error_log');
ini_set('error_log', $log);
class FailingSecurityPDO extends PDO
{
  public function __construct() {}
  public function prepare(string $query, array $options = []): PDOStatement|false
  {
    $error = new PDOException("SQLSTATE[42S02]: Table 'private_db.secret_table' doesn't exist");
    $error->errorInfo = ['42S02', 1146, "Table 'private_db.secret_table' doesn't exist"];
    throw $error;
  }
}
$singleton = new ReflectionProperty(DB::class, 'instance');
$oldInstance = $singleton->getValue();
try {
  $db = (new ReflectionClass(DB::class))->newInstanceWithoutConstructor();
  (new ReflectionProperty(DB::class, 'pdo'))->setValue($db, new FailingSecurityPDO());
  $singleton->setValue(null, $db);
  $_SESSION = ['user' => ['id' => 1, 'type_user' => 'admin_opd', 'kd_wilayah' => 'W1', 'kd_opd' => 'O1'], 'last_activity' => time()];
  ob_start();
  (new MapsController())->layers();
  $output = ob_get_clean();
  $response = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
  $assert(http_response_code() === 500 && $response['success'] === false, 'Maps mengembalikan kegagalan server dengan JSON valid');
  $assert(!preg_match('/SQLSTATE|private_db|secret_table|SELECT|SQL Error/i', $output), 'detail SQL dan nama tabel tidak bocor ke respons');
  $assert(str_contains(file_get_contents($log), 'private_db.secret_table'), 'diagnostik tersimpan di log server');
  $connection = DatabaseError::from(new PDOException('Access denied for private_account'), true);
  $assert($connection->httpStatus === 503 && !str_contains($connection->getMessage(), 'private_account'), 'kegagalan koneksi aman');
  $signal = new PDOException('SQLSTATE[45000] diagnostic', 45000);
  $signal->errorInfo = ['45000', 1644, 'Pagu DPA tidak boleh lebih kecil dari akumulasi kontrak yang disetujui'];
  $assert(DatabaseError::from($signal)->getMessage() === $signal->errorInfo[2], 'validasi pagu yang aman tetap terbaca');
  $signal->errorInfo[2] = 'secret_table private_db';
  $assert(!str_contains(DatabaseError::from($signal)->getMessage(), 'secret_table'), 'SIGNAL yang belum disetujui juga disamarkan');
  foreach (['/maps/delete', '/login/proses', '/reset_tabel/restore', '/tata_naskah/generateNomor', '/scope/select'] as $path) {
    $assert(RequestGuard::requiresPost(Router::route($path)), "$path wajib POST");
  }
  $assert(!RequestGuard::requiresPost(Router::route('/maps/api/layers')), 'daftar layer tetap tersedia melalui GET');
  try { FileService::getPath('file.pdf', '../../config'); $assert(false, 'traversal ditolak'); }
  catch (InvalidArgumentException $e) { $assert(true, 'traversal modul file ditolak'); }
  try { FileService::delete('../../config/database.php', 'test'); $assert(false, 'traversal ditolak'); }
  catch (InvalidArgumentException $e) { $assert(true, 'traversal penghapusan file ditolak'); }
} finally {
  $singleton->setValue(null, $oldInstance);
  ini_set('error_log', $oldLog);
  unlink($log);
}
