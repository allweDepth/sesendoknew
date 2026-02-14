<?php
require_once __DIR__.'/../Core/Controller.php';

class DpaController extends Controller{

    public function index(){
        if(!Auth::check()){
            header("Location:/login");
            exit;
        }
        $path='anggaran/dpa/index';
        $this->view($path);
    }
}
