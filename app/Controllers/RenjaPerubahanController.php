<?php
require_once __DIR__.'/../Core/Controller.php';

class RenjaPerubahanController extends Controller{

    public function index(){
        if(!Auth::check()){
            header("Location:/login");
            exit;
        }
        $path='anggaran/renjaperubahan/index';
        $this->view($path);
    }
}
