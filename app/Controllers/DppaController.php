<?php
require_once __DIR__.'/../Core/Controller.php';

class DppaController extends Controller{

    public function index(){
        if(!Auth::check()){
            header("Location:/login");
            exit;
        }
        $path='anggaran/dppa/index';
        $this->view($path);
    }
}
