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
    $messages = $model->getPrivateMessages((int)$_SESSION['user']['id']);

    $this->view('wallchat/index', [
      'feeds' => $feeds,
      'users' => $users,
      'messages' => $messages
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
      ,'username' => $_SESSION['user']['username']
    ]);

    echo json_encode(['success' => true]);
  }
  // komentar feed
  public function comment()
  {
    if (!Auth::check()) exit;

    $model = new WallchatModel();

    $content=trim($_POST['content']??'');$feedId=(int)($_POST['feed_id']??0);
    $parent=$model->find($feedId);
    if($content===''||!$parent||$parent['type']!=='status'){echo json_encode(['success'=>false,'message'=>'Status atau komentar tidak valid']);return;}
    $model->store([
      'user_id' => $_SESSION['user']['id'],
      'content' => $content,
      'parent_id' => $feedId,
      'type' => 'comment',
      'username' => $_SESSION['user']['username']
    ]);

    echo json_encode(['success' => true]);
  }
  // kirim pesan pribadi
  public function privateMessage()
  {
    if (!Auth::check()) exit;

    $model = new WallchatModel();

    $receiver=(int)($_POST['receiver_id']??0);$content=trim($_POST['content']??'');
    if(!$receiver||$receiver===(int)$_SESSION['user']['id']||$content===''){echo json_encode(['success'=>false,'message'=>'Penerima atau pesan tidak valid']);return;}
    $model->store([
      'user_id' => $_SESSION['user']['id'],
      'receiver_id' => $receiver,
      'content' => $content,
      'type' => 'private',
      'username' => $_SESSION['user']['username']
    ]);

    echo json_encode(['success' => true]);
  }
  public function delete()
  {
    if (!Auth::check()) exit;

    $model = new WallchatModel();

    $id = (int)($_POST['id']??0);
    $row=$model->find($id);
    if(!$row||((int)$row['user_id']!==(int)$_SESSION['user']['id']&&($_SESSION['user']['type_user']??'')!=='super_admin')){http_response_code(403);echo json_encode(['success'=>false,'message'=>'Tidak berhak menghapus pesan']);return;}
    $model->delete($id,(int)$row['user_id']);
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
