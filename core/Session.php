<?php

/**
 * Session - Gestión segura de sesiones PHP
 */
class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started) return;

        $name     = env('SESSION_NAME', 'oscisa_session');
        $lifetime = (int) env('SESSION_LIFETIME', 7200);

        session_name($name);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),  // Solo HTTPS en producción
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;

        // Regenerar ID cada 30 minutos para prevenir fijación de sesión
        if (!isset($_SESSION['_last_regen'])) {
            $_SESSION['_last_regen'] = time();
        } elseif (time() - $_SESSION['_last_regen'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_last_regen'] = time();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function destroy(): void
    {
        session_unset();
        session_destroy();
        self::$started = false;
    }
}
