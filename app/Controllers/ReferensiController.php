<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class ReferensiController extends Controller
{
    private $allowedTables = [
        'urusan',
        'bidang',
        'program',
        'kegiatan',
        'sub_kegiatan',
        'rekanan',
        'satuan',
        'mapping',
        'aset',
        'akun',
        'sumber_dana',
        'organisasi',
        'peraturan',
        'wilayah',
        'rpjmd_kabupaten',
        'usulan_pembangunan',
        'evaluasi_renja',
        'iku_opd','pohon_kinerja','perjanjian_kinerja','perjanjian_kinerja_detail',
        'pengukuran_kinerja','evaluasi_renstra','renja_kinerja'
    ];


    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
            exit;
        }

        $tbl = $_GET['tbl'] ?? 'program';

        // 🔥 Jika tidak diizinkan, fallback ke default
        if (!in_array($tbl, $this->allowedTables)) {
            $tbl = 'program'; // fallback aman
        }

        $this->view('referensi/index', [
            'tbl'  => $tbl
        ], 'app');
    }



    public function store()
    {
        $this->legacyMutationDisabled();
    }
    public function load()
    {
        require_once __DIR__ . '/../Services/DynamicTableService.php';

        $service = new DynamicTableService();
        echo json_encode($service->handle($_POST));
    }
    public function update()
    {
        $this->legacyMutationDisabled();
    }

    public function delete()
    {
        $this->legacyMutationDisabled();
    }
    public function qualificationData():void
    {
        $this->qualificationDataResponse(false);
    }
    public function qualificationSave():void
    {
        if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);return;}
        $this->qualificationDataResponse(true);
    }
    private function qualificationDataResponse(bool $save):void
    {
        header('Content-Type: application/json;charset=UTF-8');header('Cache-Control: private, no-store');
        if(!Auth::check()){http_response_code(401);echo json_encode(['success'=>false,'message'=>'Silakan masuk kembali.']);return;}
        try{
            require_once __DIR__.'/../Services/RekananQualificationService.php';
            $input=$save?$_POST:$_GET;$id=filter_var($input['id']??null,FILTER_VALIDATE_INT);if(!$id||$id<1)throw new InvalidArgumentException('Rekanan tidak valid.');
            $service=new RekananQualificationService(Auth::scopedUser());
            if($save){$raw=(string)($input['data']??'');if(strlen($raw)>2000000)throw new InvalidArgumentException('Data kualifikasi terlalu besar.');$payload=json_decode($raw,true,512,JSON_THROW_ON_ERROR);if(!is_array($payload))throw new InvalidArgumentException('Data kualifikasi tidak valid.');$result=$service->saveQualification($id,$payload);}else{$result=$service->qualification($id);}
            echo json_encode(['success'=>true,'data'=>$result,'message'=>$save?'Data kualifikasi berhasil disimpan.':''],JSON_UNESCAPED_UNICODE);
        }catch(Throwable $e){http_response_code(422);echo json_encode(['success'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
    }
    public function qualificationOptions():void
    {
        $this->qualificationResponse(false);
    }
    public function qualificationExcel():void
    {
        $this->qualificationResponse(false,true);
    }
    public function qualificationPdf():void
    {
        $this->qualificationResponse(true);
    }
    private function qualificationResponse(bool $pdf,bool $excel=false):void
    {
        if(!Auth::check()){http_response_code(401);header('Content-Type: application/json;charset=UTF-8');echo json_encode(['success'=>false,'message'=>'Silakan masuk kembali.']);return;}
        try{
            require_once __DIR__.'/../Services/RekananQualificationService.php';
            $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);if(!$id||$id<1)throw new InvalidArgumentException('Pilih rekanan yang valid.');
            $service=new RekananQualificationService(Auth::scopedUser());
            header('Cache-Control: private, no-store');
            if($pdf){$bytes=$service->pdf($id,$_GET);header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="isian-kualifikasi-'.$id.'.pdf"');echo $bytes;}
            elseif($excel){require_once __DIR__.'/../Services/RekananQualificationExcelService.php';$bytes=(new RekananQualificationExcelService(Auth::scopedUser()))->excel($id,$_GET);header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="isian-kualifikasi-'.$id.'.xlsx"');echo $bytes;}else{header('Content-Type: application/json;charset=UTF-8');echo json_encode(['success'=>true,'data'=>$service->options($id)],JSON_UNESCAPED_UNICODE);}
        }catch(Throwable $e){http_response_code(422);header('Content-Type: application/json;charset=UTF-8');echo json_encode(['success'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
    }
    private function legacyMutationDisabled():void
    {
        http_response_code(403);header('Content-Type: application/json;charset=UTF-8');
        echo json_encode(['success'=>false,'message'=>'Mutasi referensi harus melalui engine berizin sesuai role.']);
    }
}
