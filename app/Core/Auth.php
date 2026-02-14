<?php

require_once __DIR__ . '/DB.php';

class Auth
{
    public static function login($usernameInput, $passwordInput)
    {
        $db = DB::getInstance();

        $usernameInput = trim($usernameInput);

        $user = $db->first(
            'user_sesendok_biila',
            'WHERE username = ? OR email = ?',
            [$usernameInput, $usernameInput] // WAJIB 2 PARAMETER
        );

        if (!$user) {
            return false;
        }

        if (!password_verify($passwordInput, $user['password'])) {
            return false;
        }

        $_SESSION['user'] = $user;

        return true;
    }

    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check()
    {
        return isset($_SESSION['user']);
    }

    public static function logout()
    {
        unset($_SESSION['user']);
        session_destroy();
    }

    public static function tahun()
    {
        return $_SESSION['user']['tahun'] ?? null;
    }
}
