<?php
require_once __DIR__ . '/../app/Controllers/MapsController.php';
class MapsMemoryStatement {
    public function __construct(private array $row) {}
    public function fetch() { return $this->row ?: false; }
}
class MapsMemoryDB {
    public array $rows=[];
    public int $failAfter=0;
    private array $backup=[];
    private int $id=0;
    public function begin(){ $this->backup=$this->rows; }
    public function rollback(){ $this->rows=$this->backup; }
    public function commit(){}
    public function query($sql,$params){
        foreach($this->rows as $row) if($row['id']==$params[0] && !$row['is_deleted'] && $row['kd_wilayah']===$params[1] && $row['kd_opd']===$params[2])return new MapsMemoryStatement($row);
        return new MapsMemoryStatement([]);
    }
    public function insert($table,$data){
        if($this->failAfter>0 && --$this->failAfter===0)throw new RuntimeException('Simulated insert failure');
        $this->id++;$this->rows[$this->id]=['id'=>$this->id]+$data;
    }
    public function update($table,$data,$where,$params){$this->rows[$params[0]]=array_merge($this->rows[$params[0]],$data);}
    public function lastInsertId(){return $this->id;}
}
function saveRequest($controller,array $post,array $user,string $method='POST'):array {
    $_SESSION=['user'=>$user,'last_activity'=>time()];$_POST=$post;$_SERVER['REQUEST_METHOD']=$method;
    ob_start();$controller->saveGeometry();$output=ob_get_clean();
    $response=json_decode($output,true);if(!is_array($response))throw new RuntimeException('Invalid response: '.$output);return $response;
}
function verify(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$db=new MapsMemoryDB();$property=new ReflectionProperty(DB::class,'instance');$property->setValue(null,$db);
$controller=(new ReflectionClass(MapsController::class))->newInstanceWithoutConstructor();
$scope='__maps_test_'.bin2hex(random_bytes(5));
$user=['id'=>1,'username'=>'test','type_user'=>'kepala_opd','kd_wilayah'=>$scope,'kd_opd'=>'O'];
$directory=dirname(__DIR__).'/storage/uploads/maps/'.$scope.'-O';
$fields=[['name'=>'ID','type'=>'N','width'=>10,'decimals'=>0],['name'=>'AKTIF','type'=>'L']];
$feature=fn($id)=>['type'=>'Feature','properties'=>['ID'=>$id,'AKTIF'=>$id===1],'geometry'=>['type'=>'Point','coordinates'=>[119+$id,-1]]];
$post=['id'=>'0','nama_layer'=>'Layer uji','fields'=>json_encode($fields),'geojson'=>json_encode(['type'=>'FeatureCollection','features'=>[$feature(1),$feature(2)]])];
try {
    $created=saveRequest($controller,$post,$user);verify($created['success'],'create geometry');
    $id=$created['data']['id'];$old=$db->rows[$id]['storage_dir'];
    verify(is_file(dirname(__DIR__).'/'.$old.'/layer.shp'),'SHP saved');
    $edit=$post+['revision'=>hash('sha256',$old)];$edit['id']=(string)$id;
    $wrongUser=$user;$wrongUser['kd_opd']='OTHER';
    verify(!saveRequest($controller,$edit,$wrongUser)['success'],'cross-OPD edit rejected');
    $reader=$user;$reader['type_user']='staf_opd';verify(!saveRequest($controller,$edit,$reader)['success'],'reader edit rejected');
    verify(!saveRequest($controller,$post,$user,'GET')['success'],'GET mutation rejected');
    $updated=saveRequest($controller,$edit,$user);verify($updated['success'],'edit geometry');
    verify($db->rows[$id]['storage_dir']!==$old && is_file(dirname(__DIR__).'/'.$old.'/layer.shp'),'previous SHP snapshot retained');
    verify(!saveRequest($controller,$edit,$user)['success'],'stale revision rejected');
    $edit['revision']=hash('sha256',$db->rows[$id]['storage_dir']);$edit['divide_field']='ID';
    $before=$db->rows[$id];$divided=saveRequest($controller,$edit,$user);
    verify($divided['success'] && count($divided['data']['ids'])===2,'divide creates two SHP layers');
    verify($db->rows[$id]===$before,'divide preserves original layer');
    foreach($divided['data']['ids'] as $newId)verify($db->rows[$newId]['kd_opd']==='O','divide respects scope');
    $rowsBefore=$db->rows;$filesBefore=glob($directory.'/*');$db->failAfter=2;
    verify(!saveRequest($controller,$edit,$user)['success'],'divide failure returned');
    verify($db->rows===$rowsBefore && glob($directory.'/*')===$filesBefore,'divide failure rolls back every row and component folder');
} finally {
    foreach(glob($directory.'/*')?:[] as $folder){foreach(glob($folder.'/*')?:[] as $file)unlink($file);rmdir($folder);}if(is_dir($directory))rmdir($directory);
    $property->setValue(null,null);
}
echo "MAPS GEOMETRY SAVE TESTS COMPLETE: create/edit, role/scope, revisions, snapshots, atomic divide rollback\n";
