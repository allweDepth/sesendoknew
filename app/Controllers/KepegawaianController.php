<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class KepegawaianController extends Controller
{
    private $allowedTables = [
        'asn',
        'pppk',
        'riwayat_jabatan',
        'riwayat_pangkat',
        'cuti',
        'sk_pegawai',
        'pejabat_tahunan',
        'absensi',
        'penugasan_subkegiatan',
        'dokumen_pegawai'
    ];

    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
            exit;
        }

        $tbl = $_GET['tbl'] ?? 'asn';

        if (!in_array($tbl, $this->allowedTables)) {
            $tbl = 'asn';
        }

        $this->view('referensi/index', [
            'tbl' => $tbl
        ], 'app');
    }

    private function structureUser(): array
    {
        if (!Auth::check()) throw new RuntimeException('Sesi login tidak valid');
        $user = Auth::scopedUser();
        if (empty($user['kd_wilayah']) || empty($user['kd_opd']) || $user['kd_opd'] === '0') {
            throw new RuntimeException('Pilih satu OPD untuk membuka struktur organisasi');
        }
        return $user;
    }

    public function structure(): void
    {
        try { $user = $this->structureUser(); }
        catch (Throwable $e) { http_response_code(403); echo htmlspecialchars($e->getMessage()); return; }
        $this->view('kepegawaian/struktur', ['canManage'=>in_array($user['type_user'] ?? '', ['kepala_opd','pa_kpa'], true)], 'app');
    }

    public function structureData(): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try {
            $u=$this->structureUser(); $db=DB::getInstance();
            $rows=$db->query('SELECT s.*,CONCAT_WS(" ",a.gelar_depan,a.nama,a.gelar) nama_pegawai,a.nip FROM struktur_jabatan_opd_neo s LEFT JOIN db_asn_pemda_neo a ON a.id=s.pegawai_id AND a.is_deleted=0 WHERE s.kd_wilayah=? AND s.kd_opd=? AND s.tahun=? AND s.is_deleted=0 ORDER BY s.urutan,s.id',[$u['kd_wilayah'],$u['kd_opd'],$u['tahun']])->fetchAll();
            $employees=$db->query('SELECT id,CONCAT_WS(" ",gelar_depan,nama,gelar) nama,nip,jabatan FROM db_asn_pemda_neo WHERE kd_wilayah=? AND kd_opd=? AND is_deleted=0 AND disable=0 AND COALESCE(aktif,1)=1 ORDER BY nama',[$u['kd_wilayah'],$u['kd_opd']])->fetchAll();
            echo json_encode(['success'=>true,'data'=>['rows'=>$rows,'employees'=>$employees]],JSON_UNESCAPED_UNICODE);
        } catch(Throwable $e) { http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
    }

    public function structureSave(): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try {
            $u=$this->structureUser();
            if(!in_array($u['type_user']??'', ['kepala_opd','pa_kpa'], true)) throw new RuntimeException('Struktur jabatan hanya dapat diatur Kepala OPD/PA/KPA');
            if(empty($_SESSION['csrf_token'])||($_SERVER['HTTP_X_CSRF_TOKEN']??'')!==$_SESSION['csrf_token']) throw new RuntimeException('CSRF validation gagal');
            $id=(int)($_POST['id']??0); $employeeId=(int)($_POST['pegawai_id']??0); $name=trim((string)($_POST['nama_jabatan']??''));
            if(!$employeeId||$name==='') throw new InvalidArgumentException('Jabatan dan pejabat ASN wajib dipilih');
            $db=DB::getInstance();
            if(!$db->query('SELECT id FROM db_asn_pemda_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0 AND disable=0 LIMIT 1',[$employeeId,$u['kd_wilayah'],$u['kd_opd']])->fetch()) throw new RuntimeException('ASN tidak aktif atau bukan bagian dari OPD ini');
            $parent=!empty($_POST['parent_id'])?(int)$_POST['parent_id']:null;
            if($parent && !$db->query('SELECT id FROM struktur_jabatan_opd_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',[$parent,$u['kd_wilayah'],$u['kd_opd'],$u['tahun']])->fetch()) throw new RuntimeException('Atasan langsung tidak valid');
            if($parent===$id&&$id) throw new InvalidArgumentException('Jabatan tidak dapat menjadi atasan dirinya sendiri');
            $data=['pegawai_id'=>$employeeId,'parent_id'=>$parent,'nama_jabatan'=>$name,'kelompok_jabatan'=>trim((string)($_POST['kelompok_jabatan']??'')),'eselon'=>trim((string)($_POST['eselon']??'')),'urutan'=>max(1,(int)($_POST['urutan']??1)),'keterangan'=>trim((string)($_POST['keterangan']??''))];
            if($id){$data+=['tgl_update'=>date('Y-m-d H:i:s'),'username_update'=>$u['username']??'system'];$db->update('struktur_jabatan_opd_neo',$data,'WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',[$id,$u['kd_wilayah'],$u['kd_opd'],$u['tahun']]);}
            else {$data+=['kd_wilayah'=>$u['kd_wilayah'],'kd_opd'=>$u['kd_opd'],'tahun'=>$u['tahun'],'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$u['username']??'system','is_deleted'=>0];$id=$db->insert('struktur_jabatan_opd_neo',$data);}
            echo json_encode(['success'=>true,'message'=>'Struktur jabatan berhasil disimpan','data'=>['id'=>$id]],JSON_UNESCAPED_UNICODE);
        } catch(Throwable $e) { http_response_code(400); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
    }

    public function structureDelete(): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try {$u=$this->structureUser();if(!in_array($u['type_user']??'', ['kepala_opd','pa_kpa'],true))throw new RuntimeException('Hanya Kepala OPD/PA/KPA yang dapat menghapus jabatan');if(empty($_SESSION['csrf_token'])||($_SERVER['HTTP_X_CSRF_TOKEN']??'')!==$_SESSION['csrf_token'])throw new RuntimeException('CSRF validation gagal');$id=(int)($_POST['id']??0);$db=DB::getInstance();$db->begin();try{$db->query('UPDATE struktur_jabatan_opd_neo SET parent_id=NULL,tgl_update=NOW(),username_update=? WHERE parent_id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',[$u['username']??'system',$id,$u['kd_wilayah'],$u['kd_opd'],$u['tahun']]);$db->query('UPDATE struktur_jabatan_opd_neo SET is_deleted=1,tgl_update=NOW(),username_update=? WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=?',[$u['username']??'system',$id,$u['kd_wilayah'],$u['kd_opd'],$u['tahun']]);$db->commit();}catch(Throwable$e){$db->rollback();throw$e;}echo json_encode(['success'=>true,'message'=>'Jabatan dihapus']);}catch(Throwable$e){http_response_code(400);echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}
    }
}
