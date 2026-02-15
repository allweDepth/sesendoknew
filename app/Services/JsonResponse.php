<?php

class JsonResponse
{
    public static function success(
        string $message = '',
        ?array $meta = null,
        mixed $data = null
    ): string {
        return json_encode([
            'success' => true,
            'message' => $message,
            'meta'    => $meta,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function error(
        string $message = '',
        ?array $meta = null
    ): string {
        return json_encode([
            'success' => false,
            'message' => $message,
            'meta'    => $meta,
            'data'    => null
        ], JSON_UNESCAPED_UNICODE);
    }
}
