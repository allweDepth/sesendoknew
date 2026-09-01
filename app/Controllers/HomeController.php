<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/BeritaModel.php';
class HomeController extends Controller
{
  public function spa()
{
    if (!isset($_SESSION['user'])) {
        header('Location: ' . app_url('/'));
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
    $this->publicContent('data_teknis','Data Teknis');
  }

  public function organisasi()
  {
    $this->publicContent('organisasi','Organisasi');
  }

  public function pelayanan()
  {
    $this->publicContent('pelayanan','Pelayanan');
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
  private function publicContent(string $type,string $title):void
  {
    $items=(new BeritaModel())->getAll(null,$type);$this->view('home/public_content',['active'=>$type==='data_teknis'?'datateknis':$type,'title'=>$title,'items'=>$items,'type'=>$type],'public');
  }
}
