<?php

class Controller {

    protected function view($path, $data = [], $layout = 'app') {

        extract($data);

        ob_start();
        require __DIR__ . '/../Views/' . $path . '.php';
        $content = ob_get_clean();

        if ($layout === 'public') {
            require __DIR__ . '/../Views/layouts/public.php';
        } else {
            require __DIR__ . '/../Views/layouts/app.php';
        }
    }

}