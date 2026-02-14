#!/bin/bash

echo "🏛 INSTALL SIPD STABLE BASE v1.0"

# ==================================================
# STRUKTUR FOLDER
# ==================================================

mkdir -p app/Core
mkdir -p app/Controllers
mkdir -p app/Models
mkdir -p app/Views/layouts/components
mkdir -p app/Views/{auth,dashboard}
mkdir -p app/Views/anggaran/{renstra,renja,dpa,renja_perubahan,dppa}
mkdir -p config routes public/assets/{css,js}

# ==================================================
# CONFIG DATABASE
# ==================================================

cat > config/database.php <<'EOF'
<?php
return [
    'host'=>'127.0.0.1',
    'dbname'=>'sesendokneo_db',
    'username'=>'root',
    'password'=>''
];
EOF

# ==================================================
# DATABASE CORE
# ==================================================

cat > app/Core/Database.php <<'EOF'
<?php
class Database {
    private static $instance;
    private $pdo;

    private function __construct(){
        $config=require __DIR__.'/../../config/database.php';
        $this->pdo=new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
            ]
        );
    }

    public static function getInstance(){
        if(!self::$instance){
            self::$instance=new self();
        }
        return self::$instance;
    }

    public function query($sql,$params=[]){
        $stmt=$this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
EOF

# ==================================================
# AUTH
# ==================================================

cat > app/Core/Auth.php <<'EOF'
<?php
require_once 'Database.php';

class Auth {

    public static function login($username,$password){
        $db=Database::getInstance();
        $user=$db->query(
            "SELECT * FROM user_sesendok_biila WHERE username=? LIMIT 1",
            [$username]
        )->fetch();

        if($user && $user['password']==$password){
            $_SESSION['user']=$user;
            $_SESSION['tahun']=$user['tahun'];
            $_SESSION['kd_opd']=$user['kd_organisasi'];
            return true;
        }
        return false;
    }

    public static function check(){
        return isset($_SESSION['user']);
    }

    public static function user(){
        return $_SESSION['user']??null;
    }

    public static function tahun(){
        return $_SESSION['tahun']??null;
    }

    public static function logout(){
        session_destroy();
    }
}
EOF

# ==================================================
# CONTROLLER BASE
# ==================================================

cat > app/Core/Controller.php <<'EOF'
<?php
class Controller{
    protected function view($path,$data=[]){
        extract($data);
        require __DIR__.'/../Views/layouts/app.php';
    }
}
EOF

# ==================================================
# ROUTER
# ==================================================

cat > app/Core/Router.php <<'EOF'
<?php
class Router{
    public static function route($uri){
        $routes=require __DIR__.'/../../routes/web.php';
        return $routes[$uri]??null;
    }
}
EOF

# ==================================================
# PUBLIC INDEX
# ==================================================

cat > public/index.php <<'EOF'
<?php
session_start();

require_once '../app/Core/Database.php';
require_once '../app/Core/Auth.php';
require_once '../app/Core/Controller.php';
require_once '../app/Core/Router.php';

$uri=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$route=Router::route($uri);

if($route){
    require_once "../app/Controllers/".$route[0].".php";
    $controller=new $route[0];
    $method=$route[1];
    $controller->$method();
}else{
    echo "404 Not Found";
}
EOF

# ==================================================
# ROUTES
# ==================================================

cat > routes/web.php <<'EOF'
<?php
return [
    '/' => ['DashboardController','index'],
    '/login'=>['AuthController','loginForm'],
    '/login/proses'=>['AuthController','login'],
    '/logout'=>['AuthController','logout'],

    '/renstra'=>['RenstraController','index'],
    '/renja'=>['RenjaController','index'],
    '/dpa'=>['DpaController','index'],
    '/renja_perubahan'=>['RenjaPerubahanController','index'],
    '/dppa'=>['DppaController','index'],
];
EOF

# ==================================================
# DASHBOARD CONTROLLER
# ==================================================

cat > app/Controllers/DashboardController.php <<'EOF'
<?php
require_once __DIR__.'/../Core/Controller.php';

class DashboardController extends Controller{

    public function index(){
        if(!Auth::check()){
            header("Location:/login");
            exit;
        }
        $path='dashboard/index';
        $this->view($path);
    }
}
EOF

# ==================================================
# AUTH CONTROLLER
# ==================================================

cat > app/Controllers/AuthController.php <<'EOF'
<?php
require_once __DIR__.'/../Core/Controller.php';

class AuthController extends Controller{

    public function loginForm(){
        $path='auth/login';
        $this->view($path);
    }

    public function login(){
        if(Auth::login($_POST['username'],$_POST['password'])){
            header("Location:/");
        }else{
            echo "Login gagal";
        }
    }

    public function logout(){
        Auth::logout();
        header("Location:/login");
    }
}
EOF

# ==================================================
# ANGGARAN CONTROLLERS
# ==================================================

for c in Renstra Renja Dpa RenjaPerubahan Dppa
do
cat > app/Controllers/${c}Controller.php <<EOF
<?php
require_once __DIR__.'/../Core/Controller.php';

class ${c}Controller extends Controller{

    public function index(){
        if(!Auth::check()){
            header("Location:/login");
            exit;
        }
        \$path='anggaran/$(echo $c | tr '[:upper:]' '[:lower:]')/index';
        \$this->view(\$path);
    }
}
EOF
done

# ==================================================
# LOGIN VIEW
# ==================================================

cat > app/Views/auth/login.php <<'EOF'
<div class="ui middle aligned center aligned grid" style="height:100vh">
<div class="column" style="max-width:400px">
<form class="ui large form" method="POST" action="/login/proses">
<div class="ui segment">
<h2 class="ui teal header">Login SIPD</h2>
<div class="field">
<input type="text" name="username" placeholder="Username">
</div>
<div class="field">
<input type="password" name="password" placeholder="Password">
</div>
<button class="ui fluid teal button">Login</button>
</div>
</form>
</div>
</div>
EOF

# ==================================================
# DASHBOARD VIEW
# ==================================================

cat > app/Views/dashboard/index.php <<'EOF'
<h2>Dashboard Tahun Anggaran: <?php echo Auth::tahun(); ?></h2>
EOF

# ==================================================
# LAYOUT FOMANTIC
# ==================================================

cat > app/Views/layouts/app.php <<'EOF'
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>SIPD</title>
<link rel="stylesheet" href="/assets/css/fomantic.min.css">
</head>
<body>

<div class="ui sidebar inverted vertical menu left">
    <a class="item" href="/">Dashboard</a>
    <div class="item">
        <div class="header">Anggaran</div>
        <div class="menu">
            <a class="item" href="/renstra">Renstra</a>
            <a class="item" href="/renja">Renja</a>
            <a class="item" href="/dpa">DPA</a>
            <a class="item" href="/renja_perubahan">Renja Perubahan</a>
            <a class="item" href="/dppa">DPPA</a>
        </div>
    </div>
    <a class="item" href="/logout">Logout</a>
</div>

<div class="pusher">
<div class="ui top fixed menu">
<div class="item">
<button class="ui icon button" id="sidebar-toggle">
<i class="bars icon"></i>
</button>
</div>
<div class="header item">SIPD</div>
</div>

<div style="margin-top:70px;padding:20px">
<?php require __DIR__.'/../'.$path.'.php'; ?>
</div>
</div>

<script src="/assets/js/jquery.min.js"></script>
<script src="/assets/js/fomantic.min.js"></script>
<script src="/assets/js/app.js"></script>

</body>
</html>
EOF

# ==================================================
# JS
# ==================================================

cat > public/assets/js/app.js <<'EOF'
$(document).ready(function(){

    $('.ui.sidebar')
        .sidebar({
            context: $('.pusher'),
            transition: 'overlay'
        });

    $('#sidebar-toggle').on('click',function(){
        $('.ui.sidebar').sidebar('toggle');
    });

});
EOF

echo "✅ SIPD STABLE BASE v1.0 SIAP"
echo "Download Fomantic & jQuery ke public/assets"
echo "Jalankan: php -S localhost:8000 -t public"
