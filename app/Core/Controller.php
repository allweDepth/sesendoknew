<?php

class Controller
{

    protected function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function view($path, $data = [], $layout = 'app')
    {

        extract($data);

        ob_start();
        require __DIR__ . '/../Views/' . $path . '.php';
        $content = ob_get_clean();

        // ✅ JIKA AJAX → kirim hanya content
        if ($this->isAjax()) {
            echo $content;
            return;
        }

        // ✅ JIKA NORMAL REQUEST → kirim layout
        if ($layout === 'public') {
            require __DIR__ . '/../Views/layouts/public.php';
        } else {
            require __DIR__ . '/../Views/layouts/app.php';
        }
    }

    //SETELAH TIDAK DEV PAKAI INI
    /*
    protected function view($path, $data = [], $layout = 'app')
    {
        extract($data);

        $basePath = __DIR__ . '/../Views/';
        $viewPath = $basePath . $path . '.php';

        // 🔎 Jika file tidak ada, coba folder/index.php
        if (!file_exists($viewPath)) {
            $viewPath = $basePath . $path . '/index.php';
        }

        // ❌ Jika tetap tidak ada → 404 elegan
        if (!file_exists($viewPath)) {

            http_response_code(404);

            // Jika AJAX → kirim pesan JSON atau partial
            if ($this->isAjax()) {
                echo '<div class="ui negative message">
                    <div class="header">404</div>
                    <p>Halaman tidak ditemukan.</p>
                  </div>';
                return;
            }

            require $basePath . 'errors/404.php';
            exit;
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // ✅ Jika AJAX → hanya kirim content
        if ($this->isAjax()) {
            echo $content;
            return;
        }

        // ✅ Jika normal request → pakai layout
        if ($layout === 'public') {
            require $basePath . 'layouts/public.php';
        } else {
            require $basePath . 'layouts/app.php';
        }
    }
        */
}
