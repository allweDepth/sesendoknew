<?php
require_once __DIR__ . '/../Core/Auth.php';

class HomeController extends Controller
{
  public function index()
  {
    if (Auth::check()) {
      header("Location: /dashboard");
      exit;
    }

    $this->view('home/index', [], 'public');
  }
  public function datateknis()
  {
    $this->view('home/datateknis', [], 'public');
  }

  public function organisasi()
  {
    // load data organisasi dari model
    $this->view('home/organisasi', ['active' => 'organisasi'], 'public');
  }

  public function pelayanan()
  {
    // load data pelayanan dari model
    $this->view('home/pelayanan', ['active' => 'pelayanan'], 'public');
  }
}
