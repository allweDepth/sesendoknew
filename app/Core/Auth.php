<?php

require_once __DIR__ . '/DB.php';

class Auth
{
    public static function login($username, $password)
    {
        $db = DB::getInstance();

        $user = $db->first(
            'user_sesendok_biila',
            'WHERE username = ? AND disable = 0',
            [$username]
        );

        if ($user && $user['password'] == $password) {
            $_SESSION['user'] = $user;
            return true;
        }

        return false;
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
