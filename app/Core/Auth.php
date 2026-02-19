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
            [$usernameInput, $usernameInput]
        );

        if (!$user) return false;
        if (!password_verify($passwordInput, $user['password'])) return false;

        // 🔥 PANGGIL DI SINI
        if (!in_array($user['type_user'], self::allowedRoles())) {
            $_SESSION['login_error'] =
                "Role Anda ({$user['type_user']}) belum dikenali sistem.";
            return false;
        }

        $_SESSION['user'] = $user;

        return true;
    }
    //Type User yang di izinka
    public static function allowedRoles(): array
    {
        return [
            'super_admin',
            // 'admin',
            'admin_wilayah',
            'admin_opd',
            'editor',
            'viewer'
        ];
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
