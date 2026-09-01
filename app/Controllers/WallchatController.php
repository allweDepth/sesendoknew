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
    $this->beginJson();if (!Auth::check()){$this->jsonResult(false,'Sesi login tidak valid',401);return;}

    try {

    $model = new WallchatModel();

    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
      $this->jsonResult(false,'Isi posting tidak boleh kosong',422);
      return;
    }

    $attachment=$this->messageAttachment('wall');
    $model->store([
      'user_id' => $_SESSION['user']['id'],
      'content' => $content,
      'type' => 'status'
      ,'username' => $_SESSION['user']['username'],
      'theme'=>$_POST['theme']??'default',
      ...$attachment
    ]);

    $this->jsonResult(true,'Posting berhasil dipublikasikan');
    } catch(Throwable $e){$this->jsonResult(false,$e->getMessage(),400);}
  }
  public function update()
  {
    if(!Auth::check())exit;$content=trim((string)($_POST['content']??''));if($content===''){echo json_encode(['success'=>false,'message'=>'Isi tidak boleh kosong']);return;}$ok=(new WallchatModel())->updateOwned((int)($_POST['id']??0),(int)$_SESSION['user']['id'],$content,$_POST['theme']??null);echo json_encode(['success'=>$ok,'message'=>$ok?'Posting berhasil diperbarui':'Tidak berhak mengubah posting']);
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
    $this->beginJson();if (!Auth::check()){$this->jsonResult(false,'Sesi login tidak valid',401);return;}

    try {

    $model = new WallchatModel();

    $receiver=(int)($_POST['receiver_id']??0);$content=trim($_POST['content']??'');
    if(!$receiver||$receiver===(int)$_SESSION['user']['id']||$content===''){$this->jsonResult(false,'Penerima atau pesan tidak valid',422);return;}
    $attachment=$this->messageAttachment('private');
    $model->store([
      'user_id' => $_SESSION['user']['id'],
      'receiver_id' => $receiver,
      'content' => $content,
      'type' => 'private',
      'username' => $_SESSION['user']['username'],
      'is_ephemeral' => !empty($_POST['is_ephemeral']),
      ...$attachment
    ]);

    $this->jsonResult(true,'Pesan pribadi berhasil dikirim');
    } catch(Throwable $e){$this->jsonResult(false,$e->getMessage(),400);}
  }
  public function readPrivate()
  {
    if(!Auth::check())exit;
    $ok=(new WallchatModel())->markRead((int)($_POST['id']??0),(int)$_SESSION['user']['id']);
    echo json_encode(['success'=>$ok,'message'=>$ok?'Pesan ditandai telah dibaca':'Pesan tidak ditemukan']);
  }
  public function deletePrivate()
  {
    if(!Auth::check())exit;
    $ok=(new WallchatModel())->deletePrivate((int)($_POST['id']??0),(int)$_SESSION['user']['id']);
    echo json_encode(['success'=>$ok,'message'=>$ok?'Pesan dihapus dari kotak Anda':'Tidak berhak menghapus pesan']);
  }
  public function privateFile()
  {
    if(!Auth::check())exit;
    $row=(new WallchatModel())->privateFile((int)($_GET['id']??0),(int)$_SESSION['user']['id']);
    if(!$row||empty($row['attachment_path'])){http_response_code(404);exit('Lampiran tidak ditemukan');}
    $root=realpath(dirname(__DIR__,2));$file=realpath($root.'/'.$row['attachment_path']);$allowed=realpath($root.'/storage/uploads/messages');
    if(!$file||!$allowed||!str_starts_with($file,$allowed.DIRECTORY_SEPARATOR)){http_response_code(403);exit('Akses ditolak');}
    header('Content-Type: '.$row['attachment_mime']);header('Content-Length: '.filesize($file));header('Content-Disposition: attachment; filename="'.rawurlencode($row['attachment_name']).'"');readfile($file);exit;
  }

  private function messageAttachment(string $channel): array
  {
    $file=$_FILES['file']??null;if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return [];
    if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new RuntimeException('Lampiran gagal diunggah');
    if((int)$file['size']>3*1024*1024)throw new InvalidArgumentException('Lampiran pesan maksimal 3 MB');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','video/mp4'=>'mp4','video/webm'=>'webm','application/pdf'=>'pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlsx'];
    if(!isset($allowed[$mime]))throw new InvalidArgumentException('Lampiran harus JPG, PNG, WebP, MP4, WebM, PDF, DOCX, atau XLSX');
    $baseRelative='storage/uploads/messages';$base=dirname(__DIR__,2).'/'.$baseRelative;if(!is_dir($base)&&!mkdir($base,0733,true))throw new RuntimeException('Folder pesan tidak dapat dibuat');
    $relative=$baseRelative.'/'.$channel.'/'.date('Y/m');$dir=dirname(__DIR__,2).'/'.$relative;
    if(!is_dir($dir)&&!mkdir($dir,0733,true)){$relative=$baseRelative;$dir=$base;}
    if(!is_writable($dir))throw new RuntimeException('Folder pesan belum memiliki izin tulis untuk web server');
    $name=bin2hex(random_bytes(16)).'.'.$allowed[$mime];if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Lampiran gagal disimpan');
    return ['attachment_name'=>basename((string)$file['name']),'attachment_path'=>$relative.'/'.$name,'attachment_mime'=>$mime,'attachment_size'=>(int)$file['size']];
  }
  private function beginJson():void{ob_start();header('Content-Type: application/json; charset=UTF-8');}
  private function jsonResult(bool $success,string $message,int $status=200):void{if(ob_get_level()>0)ob_clean();http_response_code($status);echo json_encode(['success'=>$success,'message'=>$message,'data'=>[],'errors'=>[]],JSON_UNESCAPED_UNICODE);if(ob_get_level()>0)ob_end_flush();}
  public function mediaFile()
  {
    if(!Auth::check())exit;$id=(int)($_GET['id']??0);$row=(new WallchatModel())->privateFile($id,(int)$_SESSION['user']['id']);if(!$row)$row=DB::getInstance()->query("SELECT * FROM wallchat WHERE id=? AND type IN ('status','comment') AND is_deleted=0",[$id])->fetch();if(!$row||empty($row['attachment_path'])){http_response_code(404);exit('Media tidak ditemukan');}$root=realpath(dirname(__DIR__,2));$file=realpath($root.'/'.$row['attachment_path']);$allowed=realpath($root.'/storage/uploads/messages');if(!$file||!$allowed||!str_starts_with($file,$allowed.DIRECTORY_SEPARATOR)){http_response_code(403);exit('Akses ditolak');}header('Content-Type: '.$row['attachment_mime']);header('Content-Length: '.filesize($file));header('Content-Disposition: inline; filename="'.rawurlencode($row['attachment_name']).'"');readfile($file);exit;
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
