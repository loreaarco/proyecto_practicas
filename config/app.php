<?php

/**
 * Carga las variables de entorno desde el fichero .env
 * y define las constantes globales de la aplicación.
 */

// ── Carga .env ──────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// ── Helper global para leer variables de entorno ─────────────
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

// ── Constantes globales ──────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL',  rtrim(env('APP_URL', 'http://localhost/oscisa/public'), '/'));
// ASSET_URL apunta siempre a la carpeta public/ real en el servidor.
// Si document root = public/ → mismo que BASE_URL.
// Si document root = raíz del proyecto → BASE_URL . '/public'
define('ASSET_URL', rtrim(env('ASSET_URL', env('APP_URL', 'http://localhost/oscisa/public')), '/'));
define('APP_NAME',  env('APP_NAME', 'OSCISA Solutions'));
define('APP_ENV',   env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');

// ── Zona horaria ─────────────────────────────────────────────
date_default_timezone_set(env('APP_TIMEZONE', 'Europe/Madrid'));

// ── Manejo de errores ────────────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    // En producción, los errores se capturan y se loguean
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
        Logger::error('PHP', $errstr, ['file' => $errfile, 'line' => $errline, 'code' => $errno]);
        return true;
    });
    set_exception_handler(function (Throwable $e): void {
        Logger::critical('Exception', $e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        http_response_code(500);
        include BASE_PATH . '/views/errors/500.php';
        exit;
    });
}
