<?php
require_once __DIR__ . '/../Models/WallchatModel.php';

class WallchatController extends Controller
{
  private $model;

  public function __construct()
  {
    $this->model = new WallchatModel();
  }

  /* ===============================
     HALAMAN UTAMA
  =============================== */
  public function index()
  {
    $data['feeds'] = $this->model->getFeeds();

    $data['users'] = DB::getInstance()
      ->select('user_sesendok_biila', 'id, username');

    $this->view('wallchat/index', $data);
  }

  /* ===============================
     SIMPAN POST BARU
  =============================== */
  public function store()
  {
    $this->model->store([
      'user_id' => $_SESSION['user_id'] ?? 1,
      'content' => $_POST['content'] ?? '',
      'type'    => $_POST['type'] ?? 'status'
    ]);

    echo json_encode([
      'status' => true,
      'message' => 'Posting berhasil'
    ]);
  }

  /* ===============================
     SIMPAN KOMENTAR
  =============================== */
  public function comment()
  {
    $this->model->store([
      'user_id'   => $_SESSION['user_id'] ?? 1,
      'content'   => $_POST['content'] ?? '',
      'parent_id' => $_POST['parent_id'] ?? null,
      'type'      => 'komentar'
    ]);

    echo json_encode([
      'status' => true,
      'message' => 'Komentar berhasil'
    ]);
  }

  /* ===============================
     KIRIM PESAN PRIBADI
  =============================== */
  public function sendPrivate()
  {
    $this->model->store([
      'user_id'     => $_SESSION['user_id'] ?? 1,
      'receiver_id' => $_POST['receiver_id'] ?? null,
      'content'     => $_POST['content'] ?? '',
      'type'        => 'pesan_pribadi'
    ]);

    echo json_encode([
      'status' => true,
      'message' => 'Pesan berhasil dikirim'
    ]);
  }

  /* ===============================
     HAPUS (SOFT DELETE)
  =============================== */
  public function delete()
  {
    $this->model->delete($_POST['id']);

    echo json_encode([
      'status' => true,
      'message' => 'Data berhasil dihapus'
    ]);
  }
}