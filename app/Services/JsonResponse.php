<?php

class JsonResponse
{
    /* =========================================================
       SUCCESS RESPONSE
       ========================================================= */
    public static function success(
        string $message = "OK",
        array $meta = [],
        $data = []
    ): string {

        return json_encode([
            'success' => true,
            'message' => $message,
            'meta'    => $meta,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    /* =========================================================
       ERROR RESPONSE
       ========================================================= */
    public static function error(
        string $message = "Terjadi kesalahan",
        int $code = 400,
        array $errors = []
    ): string {

        http_response_code($code);

        return json_encode([
            'success' => false,
            'message' => $message,
            'meta'    => [],
            'data'    => [],
            'errors'  => $errors
        ], JSON_UNESCAPED_UNICODE);
    }
}