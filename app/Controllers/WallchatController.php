<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/WallchatModel.php'; // FIX: load model

class WallchatController extends Controller
{

  public function index()
  {
    if (!Auth::check()) {
      header('Location: ' . app_url('/'));
      exit;
    }

    $model = new WallchatModel();

    $feeds = $model->getFeeds();

    // FIX: ambil user melalui model
    $users = $this->getUsers(); // FIX: panggil method controller

    $this->view('wallchat/index', [
      'feeds' => $feeds,
      'users' => $users
    ], 'app');
  }

  // endpoint reload feed
  // endpoint reload feed
  public function feed()
  {

    $model = new WallchatModel();

    $feeds = $model->getFeeds();

    // gunakan sistem view bawaan controller
    $this->view('wallchat/feed_partial', [
      'feeds' => $feeds
    ]);
  }

  // endpoint post status
  public function store()
  {
    if (!Auth::check()) exit;

    $model = new WallchatModel();

    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
      echo json_encode(['success' => false]);
      return;
    }

    $model->store([
      'user_id' => $_SESSION['user']['id'],
      'content' => $content,
      'type' => 'status'
    ]);

    echo json_encode(['success' => true]);
  }
  // komentar feed
  public function comment()
  {
    if (!Auth::check()) exit;

    $model = new WallchatModel();

    $model->store([
      'user_id' => $_SESSION['user']['id'],
      'content' => $_POST['content'],
      'parent_id' => $_POST['feed_id'],
      'type' => 'comment'
    ]);

    echo json_encode(['success' => true]);
  }
  // kirim pesan pribadi
  public function privateMessage()
  {
    if (!Auth::check()) exit;

    $model = new WallchatModel();

    $model->store([
      'user_id' => $_SESSION['user']['id'],
      'receiver_id' => $_POST['receiver_id'],
      'content' => $_POST['content'],
      'type' => 'private'
    ]);

    echo json_encode(['success' => true]);
  }
  public function delete()
  {
    if (!Auth::check()) exit;

    $model = new WallchatModel();

    $id = $_POST['id'];

    $model->delete($id);

    echo json_encode(['success' => true]);
  }
  // =====================================================
  // ambil daftar user untuk dropdown private message
  // =====================================================
  private function getUsers()
  {
    $sql = "
        SELECT id, nama
        FROM user_sesendok_biila
        ORDER BY nama
    ";

    $db = DB::getInstance();

    return $db->query($sql)->fetchAll();
  }
}
