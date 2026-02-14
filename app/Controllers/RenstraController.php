<?php
require_once __DIR__.'/../Core/Controller.php';

class RenstraController extends Controller{

    public function index(){
        if(!Auth::check()){
            header("Location:/login");
            exit;
        }
        $path='anggaran/renstra/index';
        $this->view($path);
    }
}
