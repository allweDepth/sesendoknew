<?php

class Controller {

    protected function view($path, $data = []) {

        extract($data);

        // Render view dulu
        ob_start();
        require __DIR__ . '/../Views/' . $path . '.php';
        $content = ob_get_clean();

        // Masukkan ke layout
        require __DIR__ . '/../Views/layouts/app.php';
    }

}