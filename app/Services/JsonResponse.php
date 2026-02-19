<?php

class JsonResponse
{
    public static function success(
        string $message = '',
        ?array $meta = null,
        mixed $data = null,
        int $code = 200
    ): string {

        http_response_code($code);

        return json_encode([
            'success' => true,
            'message' => $message,
            'meta'    => $meta,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function error(
        string $message = '',
        int $code = 400,
        ?array $errors = null
    ): string {

        http_response_code($code);

        return json_encode([
            'success' => false,
            'message' => $message,
            'meta'    => null,
            'data'    => null,
            'errors'  => $errors
        ], JSON_UNESCAPED_UNICODE);
    }
}