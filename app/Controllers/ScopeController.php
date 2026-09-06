<?php
require_once __DIR__.'/../Core/Auth.php';
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/../Services/JsonResponse.php';

class ScopeController extends Controller
{
    private function regional(): array
    {
        if (!Auth::check()) throw new RuntimeException('Unauthorized');
        $user = Auth::user();
        if (!in_array($user['type_user']??'', ['super_admin','admin_wilayah','tapd'], true)) throw new RuntimeException('Pemilihan OPD hanya tersedia untuk role regional.');
        return $user;
    }

    public function options(): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try {
            $user=$this->regional();$db=DB::getInstance();$params=[];$where='is_deleted=0';
            if(($user['type_user']??'')!=='super_admin'){$where.=' AND kd_wilayah=?';$params[]=$user['kd_wilayah'];}
            $rows=$db->query("SELECT kode,uraian,kd_wilayah FROM organisasi_neo WHERE $where AND kode IS NOT NULL AND kode<>'' AND kode<>'0' ORDER BY kd_wilayah,uraian",$params)->fetchAll();
            echo JsonResponse::success('Daftar OPD',[],['rows'=>$rows,'selected_opd'=>$_SESSION['scope_kd_opd']??'0','selected_wilayah'=>$_SESSION['scope_kd_wilayah']??($user['kd_wilayah']??'')]);
        } catch(Throwable $e){echo JsonResponse::error($e->getMessage(),403);}
    }

    public function select(): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        try {
            $user=$this->regional();$opd=trim((string)($_POST['kd_opd']??'0'));
            if($opd==='0'){unset($_SESSION['scope_kd_opd']);if(($user['type_user']??'')==='super_admin')unset($_SESSION['scope_kd_wilayah']);echo JsonResponse::success('Menampilkan seluruh OPD dalam lingkup role.');return;}
            $params=[$opd];$sql="SELECT kode,kd_wilayah,uraian FROM organisasi_neo WHERE kode=? AND is_deleted=0";
            if(($user['type_user']??'')!=='super_admin'){$sql.=' AND kd_wilayah=?';$params[]=$user['kd_wilayah'];}
            $row=DB::getInstance()->query($sql.' LIMIT 1',$params)->fetch();if(!$row)throw new RuntimeException('OPD berada di luar lingkup pengguna.');
            $_SESSION['scope_kd_opd']=$row['kode'];$_SESSION['scope_kd_wilayah']=$row['kd_wilayah'];
            echo JsonResponse::success('Konteks OPD aktif: '.$row['uraian'],[],['kd_opd'=>$row['kode'],'kd_wilayah'=>$row['kd_wilayah']]);
        } catch(Throwable $e){echo JsonResponse::error($e->getMessage(),403);}
    }
}
