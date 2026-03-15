<?php
require_once __DIR__ . '/../Core/Auth.php';

class WallchatController extends Controller
{
  public function index()
  {
    if (!Auth::check()) {
      header("Location: /");
      exit;
    }

    $model = new WallchatModel();

    $feeds = $model->getFeeds(); // ambil feed

    $this->view('wallchat/index', [
      'feeds' => $feeds
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

    $model->store([
      'user_id' => $_SESSION['user_id'], // user login
      'content' => $_POST['content'],
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
      'user_id' => $_SESSION['user_id'],
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
      'user_id' => $_SESSION['user_id'],
      'receiver_id' => $_POST['receiver_id'],
      'content' => $_POST['content'],
      'type' => 'private'
    ]);

    echo json_encode(['success' => true]);
  }
}