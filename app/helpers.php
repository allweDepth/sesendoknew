<?php
// app/helpers.php

if (!function_exists('env')) {
    /**
     * Get the value of an environment variable.
     */
    function env(string $key, $default = null)
    {
        static $env = null;

        if ($env === null) {
            $env = [];
            $file = BASE_PATH . '/.env';

            if (file_exists($file)) {
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $matches)) {
                        $env[$matches[1]] = $matches[2];
                    }
                }
            }
        }

        return $env[$key] ?? $default;
    }
}