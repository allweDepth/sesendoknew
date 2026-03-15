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
  public function feed()
  {
    $model = new WallchatModel();

    $feeds = $model->getFeeds();

    require APP_PATH . '/Views/wallchat/feed_partial.php';
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
}