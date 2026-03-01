<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/BeritaModel.php';
class HomeController extends Controller
{
  public function spa()
{
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        exit;
    }

    require '../app/Views/layouts/app.php';
}
  public function index()
  {
    $this->view('home/home', ['active' => 'berita'], 'public');
  }
  public function home()
  {
    $this->view('home/home', ['active' => 'home'], 'public');
  }
  public function berita()
  {
    $this->view('home/berita', ['active' => 'berita'], 'public');
  }
  public function datateknis()
  {
    $this->view('home/datateknis', ['active' => 'datateknis'], 'public');
  }

  public function organisasi()
  {
    $this->view('home/organisasi', ['active' => 'organisasi'], 'public');
  }

  public function pelayanan()
  {
    $this->view('home/pelayanan', ['active' => 'pelayanan'], 'public');
  }
  public function news()
  {
    $model = new BeritaModel();
    $berita = $model->getAll();

    $this->view('home/berita', [
      'active' => 'berita',
      'berita' => $berita
    ], 'public');
  }
}
