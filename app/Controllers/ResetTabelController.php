<?php
require_once __DIR__.'/../Core/Controller.php';
class ResetTabelController extends Controller
{
  public function index()
  {
    return $this->view('reset_tabel/index');
  }
  public function execute()
  {
    // 🔥 lakukan reset tabel di sini
    $db = DB::getInstance();
    $db->query("SET FOREIGN_KEY_CHECKS=0");
    $db->query("TRUNCATE TABLE nama_tabel");
    $db->query("SET FOREIGN_KEY_CHECKS=1");

    return json_encode([
      'success' => true,
      'message' => 'Tabel berhasil direset'
    ]);
  }
}
