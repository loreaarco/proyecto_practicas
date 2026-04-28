<?php

/**
 * Front Controller - Único punto de entrada de la aplicación
 *
 * Todo el tráfico HTTP pasa por aquí.
 * Define autoloading, carga configuración, arranca sesión y despacha la ruta.
 */

// ── Autoloader PSR-4 simple (sin Composer) ───────────────────
spl_autoload_register(function (string $class): void {
    $dirs = [
        dirname(__DIR__) . '/core/',
        dirname(__DIR__) . '/app/Controllers/',
        dirname(__DIR__) . '/app/Services/',
        dirname(__DIR__) . '/app/Repositories/',
        dirname(__DIR__) . '/app/Models/',
        dirname(__DIR__) . '/app/Helpers/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Configuración de la aplicación ───────────────────────────
require_once dirname(__DIR__) . '/config/app.php';

// ── Inicio de sesión segura ───────────────────────────────────
Session::start();

// ── Cargar rutas y despachar ──────────────────────────────────
$router = new Router();
require_once BASE_PATH . '/routes/web.php';
$router->dispatch();
