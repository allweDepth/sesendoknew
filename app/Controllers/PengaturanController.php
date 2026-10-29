<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/../Services/JsonResponse.php';
require_once __DIR__.'/../Services/BudgetScheduleService.php';

require_once __DIR__.'/../Services/BudgetApprovalService.php';

class PengaturanController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
            exit;
        }

        $this->view('pengaturan/index', [], 'app');
    }
    public function fragment()
    {
        $this->view('pengaturan/form', ['kop'=>$this->letterhead()]);
    }
    private function letterhead():array
    {
        $u=Auth::scopedUser();$row=DB::getInstance()->query('SELECT * FROM kop_surat_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 ORDER BY aktif DESC,id DESC LIMIT 1',[$u['kd_wilayah'],$u['kd_opd'],$u['tahun']])->fetch()?:[];
        return array_merge(['nama_pemerintah'=>$u['nama_pemda']??$u['nama_org']??'PEMERINTAH DAERAH','nama_opd'=>$u['nama_opd']??$u['nama_org']??'PERANGKAT DAERAH','alamat'=>$u['alamat']??''],$row);
    }
    public function current()
    {
        header('Content-Type: application/json;charset=UTF-8');
        if(!Auth::check()){echo json_encode(['success'=>false,'message'=>'Unauthorized']);return;}
        require_once __DIR__.'/../Core/DB.php';require_once __DIR__.'/../Services/JsonResponse.php';
        $user=Auth::scopedUser();$row=DB::getInstance()->query('SELECT * FROM pengaturan_neo WHERE kd_wilayah=? AND tahun=? AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$user['kd_wilayah'],$user['tahun']])->fetch();
        if($row&&($user['kd_opd']??'0')!=='0'){$page=DB::getInstance()->query('SELECT * FROM page_setup_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 LIMIT 1',[$user['kd_wilayah'],$user['kd_opd'],$user['tahun']])->fetch();if($page){unset($page['id'],$page['kd_wilayah'],$page['kd_opd'],$page['tahun']);$row=array_merge($row,$page);}}
        $row=$row?:['tahun'=>$user['tahun']];
        $row['_schedule_defaults']=BudgetScheduleDefaults::generate((int)$user['tahun'],$user['kd_wilayah'],json_decode($row['jadwal_acuan_json']??'{}',true)?:[]);
        echo JsonResponse::success('Jadwal tahun aktif', [], $row);
    }
    public function approvals():void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try{if(!Auth::check())throw new RuntimeException('Unauthorized');$u=Auth::scopedUser();$db=DB::getInstance();$opd=BudgetScheduleService::canManage($u)?$db->query('SELECT kode,uraian FROM organisasi_neo WHERE kd_wilayah=? AND is_deleted=0 AND kode<>"0" ORDER BY uraian',[$u['kd_wilayah']])->fetchAll():[];echo JsonResponse::success('Riwayat pengesahan',[],['rows'=>BudgetApprovalService::rows($u),'opd'=>$opd,'can_manage'=>BudgetScheduleService::canManage($u)]);}catch(Throwable$e){echo JsonResponse::error($e->getMessage(),400);}
    }
    public function saveApproval():void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try{if(!Auth::check())throw new RuntimeException('Unauthorized');if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||empty($_SESSION['csrf_token'])||($_SERVER['HTTP_X_CSRF_TOKEN']??'')!==$_SESSION['csrf_token'])throw new RuntimeException('Permintaan/CSRF tidak valid');echo JsonResponse::success('Pengesahan tersimpan',[],BudgetApprovalService::save(Auth::scopedUser(),$_POST));}catch(Throwable$e){echo JsonResponse::error($e->getMessage(),400);}
    }
    public function deleteApproval():void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try{if(!Auth::check())throw new RuntimeException('Unauthorized');if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||empty($_SESSION['csrf_token'])||($_SERVER['HTTP_X_CSRF_TOKEN']??'')!==$_SESSION['csrf_token'])throw new RuntimeException('Permintaan/CSRF tidak valid');BudgetApprovalService::delete(Auth::scopedUser(),(int)($_POST['id']??0));echo JsonResponse::success('Draft pengesahan dihapus');}catch(Throwable$e){echo JsonResponse::error($e->getMessage(),400);}
    }
    public function schedules(): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try {
            if (!Auth::check()) throw new RuntimeException('Unauthorized');
            $user=Auth::scopedUser();
            if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
            echo JsonResponse::success('Status jadwal input', [], BudgetScheduleService::statuses(DB::getInstance(), $user));
        } catch (Throwable $e) { echo JsonResponse::error($e->getMessage(), 400); }
    }

    public function saveSchedules(): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try {
            if (!Auth::check()) throw new RuntimeException('Unauthorized');
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new RuntimeException('Method tidak diizinkan');
            if (empty($_SESSION['csrf_token']) || ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '') !== $_SESSION['csrf_token']) throw new RuntimeException('CSRF validation gagal');
            BudgetScheduleService::save(DB::getInstance(), Auth::scopedUser(), $_POST);
            echo JsonResponse::success('Jadwal input seluruh OPD wilayah berhasil disimpan');
        } catch (Throwable $e) { echo JsonResponse::error($e->getMessage(), 400); }
    }

    public function defaultSchedules(): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try {
            if (!Auth::check()) throw new RuntimeException('Unauthorized');
            if (($_SERVER['REQUEST_METHOD']??'')!=='POST') throw new RuntimeException('Method tidak diizinkan');
            if (empty($_SESSION['csrf_token']) || ($_SERVER['HTTP_X_CSRF_TOKEN']??'')!==$_SESSION['csrf_token']) throw new RuntimeException('CSRF validation gagal');
            BudgetScheduleService::applyDefaults(DB::getInstance(),Auth::scopedUser(),is_array($_POST['anchors']??null)?$_POST['anchors']:[]);
            echo JsonResponse::success('Default jadwal diterapkan; rentang khusus TAPD dan status kunci tetap dipakai.');
        } catch (Throwable $e) {echo JsonResponse::error($e->getMessage(),400);}
    }

    public function savePageSetup():void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try{if(!Auth::check())throw new RuntimeException('Unauthorized');if(empty($_SESSION['csrf_token'])||($_SERVER['HTTP_X_CSRF_TOKEN']??'')!==$_SESSION['csrf_token'])throw new RuntimeException('CSRF validation gagal');$u=Auth::scopedUser();$role=$u['type_user']??'viewer';if(in_array($role,['viewer','tapd'],true))throw new RuntimeException('Role hanya dapat melihat Page Setup');$opd=(string)($u['kd_opd']??'0');if($opd===''||$opd==='0')throw new RuntimeException('Pilih satu OPD sebelum menyimpan Page Setup');
            $allowed=['ukuran_kertas','orientasi_kertas','font_pdf','ukuran_font_pdf','lebar_kertas_mm','tinggi_kertas_mm','margin_atas_mm','margin_kanan_mm','margin_bawah_mm','margin_kiri_mm','margin_header_mm','margin_footer_mm','header_pdf_aktif','footer_pdf_aktif','tinggi_header_mm','tinggi_footer_mm','header_pdf_json','footer_pdf_json','tinggi_tanda_tangan_mm','posisi_tanda_tangan','alignment_tanda_tangan','teks_tanda_tangan'];$data=[];foreach($allowed as $f)if(array_key_exists($f,$_POST))$data[$f]=$_POST[$f];
            foreach(['lebar_kertas_mm','tinggi_kertas_mm'] as $field)if(array_key_exists($field,$data)&&trim((string)$data[$field])==='')$data[$field]=null;
            $errors=[];
            foreach(['ukuran_font_pdf','lebar_kertas_mm','tinggi_kertas_mm','margin_atas_mm','margin_kanan_mm','margin_bawah_mm','margin_kiri_mm','margin_header_mm','margin_footer_mm','tinggi_header_mm','tinggi_footer_mm','tinggi_tanda_tangan_mm'] as $field){
                if(!array_key_exists($field,$data)||$data[$field]===null)continue;
                $value=str_replace(',','.',trim((string)$data[$field]));
                if(!is_numeric($value)||!is_finite((float)$value)||(float)$value<0)$errors[$field]='Isi angka yang valid dan tidak negatif.';
                else $data[$field]=$value;
            }
            foreach(['header_pdf_json','footer_pdf_json'] as $field)if(isset($data[$field])&&$data[$field]!==''){
                $section=json_decode($data[$field],true);
                if(!is_array($section))$errors[$field]='Konfigurasi header/footer tidak valid. Muat ulang halaman lalu coba kembali.';
            }
            if($errors){echo JsonResponse::error('Page Setup belum disimpan. Periksa isian yang ditandai.',422,$errors);return;}
            $data['header_pdf_aktif']=!empty($data['header_pdf_aktif'])?1:0;$data['footer_pdf_aktif']=!empty($data['footer_pdf_aktif'])?1:0;if(!in_array($data['orientasi_kertas']??'AUTO',['AUTO','P','L'],true))throw new InvalidArgumentException('Orientasi tidak valid');if(!in_array($data['posisi_tanda_tangan']??'kanan',['kiri','tengah','kanan','dua_kolom'],true))throw new InvalidArgumentException('Posisi tanda tangan tidak valid');if(!in_array($data['alignment_tanda_tangan']??'C',['L','C','R','J'],true))throw new InvalidArgumentException('Alignment tanda tangan tidak valid');
            $columns=array_keys($data);$sqlColumns=implode(',',array_map(fn($x)=>"`$x`",$columns));$marks=implode(',',array_fill(0,count($columns),'?'));$updates=implode(',',array_map(fn($x)=>"`$x`=VALUES(`$x`)",$columns));DB::getInstance()->query("INSERT INTO page_setup_opd_neo(kd_wilayah,kd_opd,tahun,$sqlColumns,username_insert,is_deleted) VALUES(?,?,?,$marks,?,0) ON DUPLICATE KEY UPDATE $updates,tgl_update=NOW(),username_update=VALUES(username_insert),is_deleted=0",[$u['kd_wilayah'],$opd,$u['tahun'],...array_values($data),$u['username']??'system']);echo JsonResponse::success('Page Setup OPD tersimpan');
        }catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);}
    }
    public function identity():void
    {header('Content-Type: application/json;charset=UTF-8');try{if(!Auth::check())throw new RuntimeException('Unauthorized');$u=Auth::scopedUser();$row=DB::getInstance()->query('SELECT kode,uraian,logo,peta FROM wilayah_neo WHERE kode=? AND is_deleted=0 LIMIT 1',[$u['kd_wilayah']])->fetch();echo JsonResponse::success('Identitas wilayah',[],$row?:[]);}catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);}}
    public function uploadIdentityImage():void
    {header('Content-Type: application/json;charset=UTF-8');try{if(!Auth::check())throw new RuntimeException('Unauthorized');$u=Auth::scopedUser();if(!in_array($u['type_user']??'', ['super_admin','admin_wilayah'],true))throw new RuntimeException('Hanya administrator wilayah yang dapat mengubah gambar daerah');$field=(string)($_POST['field']??'');if(!in_array($field,['logo','peta'],true))throw new InvalidArgumentException('Jenis gambar tidak valid');$file=$_FILES['image']??[];if(empty($file['tmp_name'])||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('File gambar belum dipilih');if(($file['size']??0)>3*1024*1024)throw new RuntimeException('Ukuran gambar maksimal 3 MB');$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$extensions=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];if(!isset($extensions[$mime]))throw new RuntimeException('Format gambar harus PNG, JPG, atau WebP');$dir=dirname(__DIR__,2).'/public/uploads/wilayah';if(!is_dir($dir)&&!mkdir($dir,0775,true))throw new RuntimeException('Folder upload tidak dapat dibuat');$name=preg_replace('/[^A-Za-z0-9_.-]/','_',($u['kd_wilayah']??'wilayah').'_'.$field).'_'.bin2hex(random_bytes(6)).'.'.$extensions[$mime];if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Gagal menyimpan gambar');$path='uploads/wilayah/'.$name;DB::getInstance()->update('wilayah_neo',[$field=>$path,'tgl_update'=>date('Y-m-d H:i:s'),'username_update'=>$u['username']??'system'],'WHERE kode=? AND is_deleted=0',[$u['kd_wilayah']]);echo JsonResponse::success('Gambar wilayah berhasil diperbarui',[],[$field=>$path]);}catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);}}

    public function paguLimits()
    {
        header('Content-Type: application/json;charset=UTF-8');
        if (!Auth::check()) { echo JsonResponse::error('Unauthorized', 401); return; }
        require_once __DIR__.'/../Core/DB.php';require_once __DIR__.'/../Services/JsonResponse.php';
        $user = $_SESSION['user'];
        $usageSql = implode(' UNION ALL ', array_map(
            static fn($item) => "SELECT CONVERT(kd_wilayah USING utf8mb4) COLLATE utf8mb4_unicode_ci kd_wilayah,CONVERT(kd_opd USING utf8mb4) COLLATE utf8mb4_unicode_ci kd_opd,tahun,_utf8mb4'{$item[0]}' COLLATE utf8mb4_unicode_ci dokumen,COALESCE(SUM({$item[2]}),0) terpakai FROM `{$item[1]}` WHERE is_deleted=0 GROUP BY kd_wilayah,kd_opd,tahun",
            [['rkpd','rkpd_neo','pagu'],['rkpd_p','rkpd_p_neo','pagu'],['renja','renja_neo','jumlah'],['rka','rka_neo','jumlah'],['dpa','dpa_neo','jumlah'],['renja_p','renja_p_neo','jumlah'],['rka_p','rka_p_neo','jumlah'],['dppa','dppa_neo','jumlah']]
        ));
        $rows = DB::getInstance()->query(
            "SELECT b.id,b.kd_opd,COALESCE(o.uraian,b.kd_opd) nama_opd,b.dokumen,b.pagu_maksimal,COALESCE(u.terpakai,0) terpakai,GREATEST(b.pagu_maksimal-COALESCE(u.terpakai,0),0) sisa,b.keterangan FROM batas_pagu_opd_neo b LEFT JOIN organisasi_neo o ON o.kode=b.kd_opd AND o.kd_wilayah=b.kd_wilayah AND o.is_deleted=0 LEFT JOIN ($usageSql) u ON u.kd_wilayah=b.kd_wilayah AND u.kd_opd=b.kd_opd AND u.tahun=b.tahun AND u.dokumen=b.dokumen WHERE b.kd_wilayah=? AND b.tahun=? AND b.is_deleted=0 ORDER BY nama_opd,b.dokumen",
            [$user['kd_wilayah'], $user['tahun']]
        )->fetchAll();
        $opd = DB::getInstance()->query(
            "SELECT kode,uraian FROM organisasi_neo WHERE kd_wilayah=? AND is_deleted=0 AND kode IS NOT NULL AND kode<>'' AND kode<>'0' ORDER BY uraian",
            [$user['kd_wilayah']]
        )->fetchAll();
        echo JsonResponse::success('Batas pagu OPD', [], ['rows'=>$rows,'opd'=>$opd]);
    }

    public function savePaguLimit()
    {
        header('Content-Type: application/json;charset=UTF-8');
        if (!Auth::check()) { echo JsonResponse::error('Unauthorized', 401); return; }
        require_once __DIR__.'/../Core/DB.php';require_once __DIR__.'/../Services/JsonResponse.php';
        try {
            $user = $_SESSION['user'];
            if (($user['type_user'] ?? '') !== 'admin_wilayah') throw new RuntimeException('Hanya admin wilayah yang dapat menetapkan batas pagu OPD.');
            $opd = trim((string)($_POST['kd_opd'] ?? ''));
            $document = trim((string)($_POST['dokumen'] ?? ''));
            $maximum = filter_var($_POST['pagu_maksimal'] ?? null, FILTER_VALIDATE_FLOAT);
            $allowed = ['rkpd','rkpd_p','renja','rka','dpa','renja_p','rka_p','dppa'];
            if ($opd === '' || !in_array($document, $allowed, true) || $maximum === false || $maximum < 0) throw new InvalidArgumentException('OPD, dokumen, dan pagu maksimal wajib valid.');
            $db = DB::getInstance();
            if (!$db->query('SELECT id FROM organisasi_neo WHERE kode=? AND kd_wilayah=? AND is_deleted=0 LIMIT 1',[$opd,$user['kd_wilayah']])->fetch()) throw new RuntimeException('OPD tidak ditemukan pada wilayah pengguna.');
            $tables = ['rkpd'=>'rkpd_neo','rkpd_p'=>'rkpd_p_neo','renja'=>'renja_neo','rka'=>'rka_neo','dpa'=>'dpa_neo','renja_p'=>'renja_p_neo','rka_p'=>'rka_p_neo','dppa'=>'dppa_neo'];
            $amountField=in_array($document,['rkpd','rkpd_p'],true)?'pagu':'jumlah';
            $used = (float)($db->query("SELECT COALESCE(SUM(`$amountField`),0) total FROM `{$tables[$document]}` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0",[$user['kd_wilayah'],$opd,$user['tahun']])->fetch()['total'] ?? 0);
            if ($maximum + 0.01 < $used) throw new RuntimeException('Pagu maksimal tidak boleh lebih kecil dari pagu yang sudah terinput Rp '.number_format($used,0,',','.').'.');
            $db->query(
                'INSERT INTO batas_pagu_opd_neo(kd_wilayah,kd_opd,tahun,dokumen,pagu_maksimal,keterangan,username_insert,is_deleted) VALUES(?,?,?,?,?,?,?,0) ON DUPLICATE KEY UPDATE pagu_maksimal=VALUES(pagu_maksimal),keterangan=VALUES(keterangan),tgl_update=NOW(),username_update=VALUES(username_insert),is_deleted=0',
                [$user['kd_wilayah'],$opd,$user['tahun'],$document,$maximum,trim((string)($_POST['keterangan']??'')),$user['username']??'system']
            );
            echo JsonResponse::success('Batas pagu OPD berhasil disimpan.');
        } catch (Throwable $e) { echo JsonResponse::error($e->getMessage(), 400); }
    }
}
