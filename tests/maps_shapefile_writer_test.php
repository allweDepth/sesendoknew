<?php
require_once __DIR__ . '/../app/Services/MapsShapefileWriter.php';
require_once __DIR__ . '/../app/Controllers/MapsController.php';
require_once __DIR__ . '/../app/Core/Router.php';
function check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); echo "PASS: $message\n"; }
function reject(callable $operation): void { try { $operation(); } catch (InvalidArgumentException $e) { return; } throw new RuntimeException('Invalid input accepted'); }
$fields = [['name'=>'ID','type'=>'N','width'=>10,'decimals'=>0],['name'=>'NAMA','type'=>'C','width'=>80],['name'=>'AKTIF','type'=>'L'],['name'=>'TANGGAL','type'=>'D'],['name'=>'NILAI','type'=>'N','width'=>12,'decimals'=>2]];
$feature = ['type'=>'Feature','properties'=>['ID'=>1,'NAMA'=>'Jalan café 测试','AKTIF'=>true,'TANGGAL'=>'2026-10-04','NILAI'=>12.5], 'geometry'=>['type'=>'Point','coordinates'=>[119.35,-1.25]]];
$collection=['type'=>'FeatureCollection','features'=>[$feature]];
$parts=MapsShapefileWriter::build($collection,$fields);
check(array_keys($parts)===['shp','shx','dbf','prj','cpg'],'SHP lengkap dengan SHX/DBF/PRJ/CPG');
check(unpack('N',substr($parts['shp'],0,4))[1]===9994 && unpack('N',substr($parts['shp'],24,4))[1]*2===strlen($parts['shp']),'header SHP dan panjang file benar');
check(unpack('N',substr($parts['shx'],100,4))[1]===50 && unpack('N',substr($parts['shx'],104,4))[1]===10,'SHX menunjuk record titik dalam satuan word');
check(unpack('e',substr($parts['shp'],112,8))[1]===119.35,'koordinat SHP little-endian');
check(str_contains($parts['dbf'],'Jalan café 测试') && $parts['cpg']==='UTF-8','DBF menyimpan teks UTF-8 tanpa pemotongan');
$headerLength=unpack('v',substr($parts['dbf'],8,2))[1];
check(substr($parts['dbf'],$headerLength+1+10+80,1)==='T' && str_contains($parts['dbf'],'20261004'),'field boolean dan tanggal disimpan');
$feature['geometry']=['type'=>'Polygon','coordinates'=>[[[0,0],[4,0],[4,4],[0,4],[0,0]],[[1,1],[1,2],[2,2],[2,1],[1,1]]]];
$collection['features']=[$feature];$poly=MapsShapefileWriter::build($collection,$fields);
check(unpack('V',substr($poly['shp'],108,4))[1]===5 && unpack('V',substr($poly['shp'],144,4))[1]===2,'poligon dengan lubang ditulis sebagai dua ring');
$points=[];for($i=0;$i<10;$i++)$points[]=array_values(unpack('e2',substr($poly['shp'],160+$i*16,16)));
$area=static function(array $ring):float{$a=0;for($i=0;$i<count($ring)-1;$i++)$a+=$ring[$i][0]*$ring[$i+1][1]-$ring[$i+1][0]*$ring[$i][1];return $a;};
check($area(array_slice($points,0,5))<0 && $area(array_slice($points,5,5))>0,'shell clockwise dan hole counterclockwise');
$bad=$collection;$bad['features'][0]['geometry']['coordinates'][0][4]=[0,1];reject(fn()=>MapsShapefileWriter::build($bad,$fields));
$bad=$collection;$bad['features'][0]['geometry']=['type'=>'Point','coordinates'=>[181,0]];reject(fn()=>MapsShapefileWriter::build($bad,$fields));
$bad=$collection;$bad['features'][0]['geometry']=['type'=>'Point','coordinates'=>[119,0,20]];reject(fn()=>MapsShapefileWriter::build($bad,$fields));
$bad=$collection;$bad['features'][0]['properties']['ID']=1.2;reject(fn()=>MapsShapefileWriter::build($bad,$fields));
$bad=$collection;$bad['features'][0]['properties']['AKTIF']='false';reject(fn()=>MapsShapefileWriter::build($bad,$fields));
$bad=$collection;$bad['features'][0]['properties']['TANGGAL']='2026-02-30';reject(fn()=>MapsShapefileWriter::build($bad,$fields));
reject(fn()=>MapsShapefileWriter::validateFields([['name'=>'abc','type'=>'C'],['name'=>'ABC','type'=>'C']]));
reject(fn()=>MapsShapefileWriter::validateFields([['name'=>'long_name_123','type'=>'C']]));
check(Router::route('/maps/geometry')===['MapsController','saveGeometry'] && Router::route('/maps/download')===['MapsController','download'],'route simpan geometri dan unduh SHP');
$controller=(new ReflectionClass(MapsController::class))->newInstanceWithoutConstructor();
$role=new ReflectionMethod(MapsController::class,'canManageLayers');
check($role->invoke($controller,['type_user'=>'kepala_opd','kd_wilayah'=>'W','kd_opd'=>'O']),'kepala OPD dapat menggambar dan mengedit SHP pada scope OPD');
check(!$role->invoke($controller,['type_user'=>'staf','kd_wilayah'=>'W','kd_opd'=>'O']),'role pembaca tidak dapat mengedit SHP');
check(!$role->invoke($controller,['type_user'=>'kepala_opd','kd_wilayah'=>'W','kd_opd'=>'0']),'pengelola tanpa scope OPD tidak dapat mengedit');
echo "MAPS SHAPEFILE WRITER TESTS COMPLETE\n";
