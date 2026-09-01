<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

class ProfilController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
            exit;
        }

        $this->view('profil/index', [], 'app');
    }

    private function json(callable $callback): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try { if (!Auth::check()) throw new RuntimeException('Sesi login tidak valid'); echo JsonResponse::success('Berhasil', [], $callback()); }
        catch (Throwable $e) { echo JsonResponse::error($e->getMessage(), 400); }
    }

    public function periods(): void
    {
        $this->json(function () {
            $u=$_SESSION['user'];$db=DB::getInstance();$regional=in_array($u['type_user']??'', ['super_admin','admin_wilayah','tapd'], true);
            $sql='SELECT DISTINCT p.id,p.periode_mulai,p.periode_selesai,p.keterangan FROM periode_rpjmd p';$params=[];
            if(!$regional){$sql.=' INNER JOIN renstra_neo r ON r.periode_id=p.id AND r.kd_wilayah=? AND r.kd_opd=?';$params=[$u['kd_wilayah'],$u['kd_opd']];}
            $sql.=' WHERE COALESCE(p.status_aktif,1)=1 ORDER BY p.periode_mulai DESC';return ['scope'=>$regional?'RPJMD':'RENSTRA','periods'=>$db->query($sql,$params)->fetchAll(),'selected_year'=>(int)$u['tahun']];
        });
    }

    public function selectPeriod(): void
    {
        $this->json(function () {
            $id=(int)($_POST['periode_id']??0);$year=(int)($_POST['tahun']??0);$u=$_SESSION['user'];$db=DB::getInstance();
            $p=$db->query('SELECT id,periode_mulai,periode_selesai FROM periode_rpjmd WHERE id=? AND COALESCE(status_aktif,1)=1',[$id])->fetch();
            if(!$p||$year<(int)$p['periode_mulai']||$year>(int)$p['periode_selesai'])throw new InvalidArgumentException('Tahun harus berada dalam rentang periode aktif');
            $_SESSION['user']['tahun']=$year;$_SESSION['user']['periode_id']=$id;$db->update('user_sesendok_biila',['tahun'=>$year],'WHERE id=?',[(int)$u['id']]);return ['tahun'=>$year,'periode_id'=>$id];
        });
    }

    public function uploadPhoto(): void
    {
        $this->json(function () {
            $file=$_FILES['photo']??[];if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Pilih gambar profil');
            if((int)$file['size']>3*1024*1024)throw new InvalidArgumentException('Foto profil maksimal 3 MB');$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;if(!$ext)throw new InvalidArgumentException('Foto harus JPG, PNG, atau WebP');
            $u=$_SESSION['user'];$relative='storage/uploads/'.preg_replace('/[^A-Za-z0-9._-]/','_',($u['kd_wilayah']??'wilayah').'-'.($u['kd_opd']??'daerah')).'/profil';$dir=dirname(__DIR__,2).'/'.$relative;if(!is_dir($dir)&&!mkdir($dir,0770,true))throw new RuntimeException('Folder foto profil tidak dapat dibuat');$name='user-'.(int)$u['id'].'-'.bin2hex(random_bytes(6)).'.'.$ext;if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Foto gagal disimpan');$path=$relative.'/'.$name;DB::getInstance()->update('user_sesendok_biila',['photo'=>$path],'WHERE id=?',[(int)$u['id']]);$_SESSION['user']['photo']=$path;return ['photo'=>$path,'url'=>app_url('/'.$path)];
        });
    }
}
