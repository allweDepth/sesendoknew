<?php

class ApiController
{
    public function handle()
    {
      ini_set('display_errors', 0);

    header('Content-Type: application/json');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
        $module = $_REQUEST['module'] ?? null;
        $action = $_REQUEST['action'] ?? null;

        if (!$module || !$action) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Module atau action tidak valid'
            ]);
            return;
        }

        /*
        ==============================
        PUBLIC MODULE (tanpa login)
        ==============================
        */
        $publicModules = ['public'];

        if (!in_array($module, $publicModules)) {
            if (!isset($_SESSION['user'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'expired' => true,
                    'message' => 'Session habis. Silakan login ulang.'
                ]);
                return;
            }
        }

        $modelName = ucfirst($module) . 'Model';
        $modelPath = __DIR__ . '/../Models/' . $modelName . '.php';

        if (!file_exists($modelPath)) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Model tidak ditemukan'
            ]);
            return;
        }

        require_once $modelPath;

        if (!class_exists($modelName)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Class model tidak ditemukan'
            ]);
            return;
        }

        $model = new $modelName();

        if (!method_exists($model, $action)) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Method tidak ditemukan'
            ]);
            return;
        }

        try {
            $result = $model->$action($_REQUEST);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}