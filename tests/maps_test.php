<?php
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Controllers/MapsController.php';

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    echo "PASS: $message\n";
};

$route = Router::route('/maps?layer=test');
$assert($route === ['MapsController', 'index'], 'route Maps terdaftar');
$assert(Router::route('/maps/layers') === ['MapsController', 'layers'], 'route daftar layer tersedia');
$assert(Router::route('/maps/upload') === ['MapsController', 'upload'], 'route upload layer tersedia');
$assert(Router::route('/maps/file') === ['MapsController', 'file'], 'route file privat tersedia');
$assert(Router::route('/maps/delete') === ['MapsController', 'delete'], 'route hapus layer tersedia');

$sidebar = file_get_contents(__DIR__ . '/../app/Views/partials/sidebar.php');
$view = file_get_contents(__DIR__ . '/../app/Views/maps/index.php');
$script = file_get_contents(__DIR__ . '/../public/assets/js/maps.js');
$router = file_get_contents(__DIR__ . '/../public/assets/js/core/spa-router.js');
$controller = file_get_contents(__DIR__ . '/../app/Controllers/MapsController.php');
$migration = file_get_contents(__DIR__ . '/../database/migrations/20261002_phase59_maps_layers.sql');
$layout = file_get_contents(__DIR__ . '/../app/Views/layouts/app.php');
$frontController = file_get_contents(__DIR__ . '/../public/index.php');

$assert(str_contains($sidebar, 'href="/maps"') && str_contains($sidebar, 'data-title="Maps"'), 'menu Maps tersedia di sidebar');
$assert(str_contains($view, 'accept=".shp,.shx,.dbf,.prj"') && str_contains($view, 'Unggah ke OPD'), 'halaman mengunggah SHP beserta file pendamping ke OPD');
$assert(str_contains($script, 'parseShapefile') && str_contains($script, 'parseDbf'), 'parser geometri dan atribut tersedia');
foreach (['[1, 11, 21]', '[3, 5, 13, 15, 23, 25]', '[8, 18, 28]'] as $shapeTypes) {
    $assert(str_contains($script, $shapeTypes), 'parser mendukung tipe shapefile ' . $shapeTypes);
}
$assert(str_contains($script, 'proj4(projectionText, "EPSG:4326")'), 'sistem koordinat PRJ dikonversi ke WGS 84');
$assert(str_contains($script, 'MAX_FILE_BYTES') && str_contains($script, 'MAX_FEATURES') && str_contains($script, 'MAX_POINTS'), 'parser memiliki batas ukuran dan jumlah geometri');
$assert(str_contains($script, 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'), 'basemap OpenStreetMap menggunakan XYZ');
$assert(str_contains($script, 'World_Topo_Map') && str_contains($script, 'World_Imagery'), 'basemap Esri terrain dan satellite tersedia');
$assert(str_contains($view, 'Google Roadmap') && str_contains($view, 'Google Hybrid') && str_contains($script, 'new google.maps.Map') && str_contains($script, 'new google.maps.Data'), 'basemap Google memakai Google Maps Platform dengan renderer resminya');
$assert(str_contains($controller, "['admin_opd', 'kepala_opd', 'pa_kpa']"), 'upload dibatasi pada role pengelola OPD');
$assert(str_contains($controller, 'storage/uploads/maps/') && str_contains($controller, 'scopeFilter'), 'layer tersimpan privat dan akses dibatasi scope wilayah/OPD');
$instance = (new ReflectionClass(MapsController::class))->newInstanceWithoutConstructor();
$scopeFilter = new ReflectionMethod(MapsController::class, 'scopeFilter');
[$opdWhere, $opdParams] = $scopeFilter->invoke($instance, ['type_user' => 'admin_opd', 'kd_wilayah' => 'W01', 'kd_opd' => 'O02']);
$assert($opdWhere === ' AND kd_wilayah=? AND kd_opd=?' && $opdParams === ['W01', 'O02'], 'layer role OPD hanya terlihat pada scope wilayah dan OPD miliknya');
$_SESSION['scope_kd_opd'] = 'O03';
[$regionalWhere, $regionalParams] = $scopeFilter->invoke($instance, ['type_user' => 'admin_wilayah', 'kd_wilayah' => 'W01']);
$assert($regionalWhere === ' AND kd_wilayah=? AND kd_opd=?' && $regionalParams === ['W01', 'O03'], 'layer regional tetap terikat pada wilayah aktif');
unset($_SESSION['scope_kd_opd']);
$assert(str_contains($migration, 'kd_wilayah') && str_contains($migration, 'kd_opd') && str_contains($migration, 'maps_layers'), 'migrasi menyimpan layer berdasarkan wilayah dan OPD');
$assert(str_contains($layout, '/assets/vendor/leaflet/leaflet.js') && str_contains($layout, '/assets/vendor/leaflet/proj4.js'), 'Leaflet dan proj4 dibundel lokal');
$assert(str_contains($frontController, '/maps/upload') && str_contains($frontController, '100 * 1024 * 1024'), 'front controller memberi batas request khusus upload SHP');
$assert(str_contains($router, 'window.initMapsPage()'), 'router menginisialisasi halaman Maps saat navigasi SPA');

echo "MAPS TESTS COMPLETE\n";
