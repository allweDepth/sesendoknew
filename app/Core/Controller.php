<?php

class Controller {

    protected function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function view($path, $data = [], $layout = 'app') {

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

}