<?php

require_once __DIR__ . '/../Core/Controller.php';

class MappingController extends Controller
{
    public function index()
    {
        $this->view('mapping/index');
    }
}