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
        if ((int)($user['disable'] ?? 0) === 1) {
            $_SESSION['login_error'] = 'Akun dinonaktifkan. Hubungi pengelola OPD.';
            return false;
        }
        if (!password_verify($passwordInput, $user['password'])) return false;

        if (!in_array($user['type_user'], self::allowedRoles())) {
            $_SESSION['login_error'] =
                "Role Anda ({$user['type_user']}) belum dikenali sistem.";
            return false;
        }

        // 🔐 ANTI SESSION FIXATION
        session_regenerate_id(true);

        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();

        return true;
    }

    // ==========================================
    // ROLE YANG DIIZINKAN
    // ==========================================
    public static function allowedRoles(): array
    {
        return [
            'super_admin',
            'admin_wilayah',
            'admin_opd',
            'editor',
            'viewer',
            'user',
            'kepala_opd',
            'pa_kpa',
            'ppk',
            'pptk',
            'ppk_skpd',
            'bendahara',
            'pejabat_pengadaan',
            'staf_opd'
        ];
    }

    // ==========================================
    // GET USER
    // ==========================================
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    // ==========================================
    // CHECK LOGIN + TIMEOUT
    // ==========================================
    public static function check()
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }

        $timeout = 1800; // 30 menit

        if (
            isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity']) > $timeout
        ) {
            self::logout();
            return false;
        }

        // update activity
        $_SESSION['last_activity'] = time();

        return true;
    }

    // ==========================================
    // LOGOUT BERSIH TOTAL
    // ==========================================
    public static function logout()
    {
        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    // ==========================================
    // AMBIL TAHUN
    // ==========================================
    public static function tahun()
    {
        return $_SESSION['user']['tahun'] ?? null;
    }
}
