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

        // A dummy hash also verifies unknown accounts, limiting timing disclosure.
        $hash = $user['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
        $valid = password_verify($passwordInput, $hash);
        if (!$user || !$valid || (int)($user['disable'] ?? 0) === 1
            || (int)($user['disable_login'] ?? 0) === 1) return false;

        if (!in_array($user['type_user'], self::allowedRoles())) {
            return false;
        }

        // 🔐 ANTI SESSION FIXATION
        session_regenerate_id(true);

        unset($user['password']);
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_at'] = time();

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
            'pokja_ulp',
            'staf_opd'
            ,'tapd'
        ];
    }

    // ==========================================
    // GET USER
    // ==========================================
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function scopedUser(): array
    {
        $user = $_SESSION['user'] ?? [];
        unset($user['password']);
        if (in_array($user['type_user'] ?? '', ['super_admin','admin_wilayah','tapd'], true)) {
            $user['registered_kd_opd'] = $user['kd_opd'] ?? null;
            $user['scope_selected'] = !empty($_SESSION['scope_kd_opd']);
            if (!empty($_SESSION['scope_kd_wilayah'])) $user['kd_wilayah'] = $_SESSION['scope_kd_wilayah'];
            $user['kd_opd'] = $_SESSION['scope_kd_opd'] ?? '0';
            $user['scope_kd_opd'] = $user['kd_opd'];
        }
        return $user;
    }

    // ==========================================
    // CHECK LOGIN + TIMEOUT
    // ==========================================
    public static function check()
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }

        unset($_SESSION['user']['password']);
        $_SESSION['login_at'] ??= time();
        if (time() - (int)$_SESSION['login_at'] > 8 * 3600) {
            self::logout();
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

        // Recheck access once per HTTP request so revoked accounts and changed
        // roles do not retain authority through an old session.
        static $checked = [];
        $id = (int)($_SESSION['user']['id'] ?? 0);
        if (!isset($checked[$id])) {
            $active = DB::getInstance()->query(
                'SELECT id,type_user,disable,disable_login,kd_wilayah,kd_opd FROM user_sesendok_biila WHERE id=?',
                [$id]
            )->fetch();
            if (!$active || !empty($active['disable']) || !empty($active['disable_login'])
                || !in_array($active['type_user'], self::allowedRoles(), true)) {
                self::logout();
                return false;
            }
            $_SESSION['user'] = array_replace($_SESSION['user'], $active);
            $checked[$id] = true;
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
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    // ==========================================
    // AMBIL TAHUN
    // ==========================================
    public static function tahun()
    {
        return $_SESSION['user']['tahun'] ?? null;
    }
}
