<?php
/**
 * OSCISA Solutions — Script de verificación de instalación
 *
 * Accede a: http://tu-dominio/oscisa/public/setup.php
 * IMPORTANTE: Elimina o protege este archivo una vez verificada la instalación.
 */

// Autoloader (igual que en index.php)
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

// Carga configuración (necesitamos BASE_PATH, env(), etc.)
require_once dirname(__DIR__) . '/config/app.php';

$checks = [];
$allOk  = true;

// ── Helper ────────────────────────────────────────────────────
function check(string $name, bool $ok, string $detail = '', string $fix = ''): array {
    return compact('name', 'ok', 'detail', 'fix');
}

// ── 1. Versión de PHP ─────────────────────────────────────────
$phpVersion = PHP_VERSION;
$phpOk      = version_compare($phpVersion, '8.0.0', '>=');
$checks[]   = check(
    'PHP 8.0+',
    $phpOk,
    "Versión detectada: {$phpVersion}",
    $phpOk ? '' : 'Actualiza PHP a 8.0 o superior.'
);
if (!$phpOk) $allOk = false;

// ── 2. Extensiones PHP requeridas ─────────────────────────────
$required = ['pdo', 'pdo_mysql', 'curl', 'fileinfo', 'mbstring', 'json', 'session'];
foreach ($required as $ext) {
    $ok       = extension_loaded($ext);
    $checks[] = check("Extensión: {$ext}", $ok, '', $ok ? '' : "Activa la extensión {$ext} en php.ini");
    if (!$ok) $allOk = false;
}

// ── 3. Variables de entorno críticas ─────────────────────────
$envVars = [
    'DB_HOST'        => 'Host de base de datos',
    'DB_NAME'        => 'Nombre de la BD',
    'DB_USER'        => 'Usuario de la BD',
    'OPENAI_API_KEY' => 'API Key de OpenAI',
];
foreach ($envVars as $var => $label) {
    $val      = env($var, '');
    $ok       = !empty($val) && !in_array($val, ['tu-servidor-remoto.com', 'sk-xxxxxxxxxxxxxxxxxxxxxxxx']);
    $checks[] = check(
        ".env: {$var}",
        $ok,
        $ok ? "✓ Configurado" : "Falta o tiene valor de ejemplo",
        $ok ? '' : "Edita el fichero .env y rellena {$var}"
    );
    if (!$ok) $allOk = false;
}

// ── 4. Conexión a la base de datos ───────────────────────────
try {
    $pdo    = Database::getInstance();
    $ver    = $pdo->query('SELECT VERSION()')->fetchColumn();
    $checks[] = check('Conexión BD', true, "MySQL/MariaDB {$ver}");
} catch (Throwable $e) {
    $checks[] = check('Conexión BD', false, $e->getMessage(), 'Verifica DB_HOST, DB_USER, DB_PASS, DB_NAME en .env');
    $allOk = false;
}

// ── 5. Tablas instaladas ─────────────────────────────────────
$tablas = ['roles','usuarios','clientes','facturas','datos_extraidos_factura',
           'comercializadoras','tarifas_oferta','estudios','resultados_comparativa',
           'seguimiento_comercial','estados_comerciales','logs_sistema'];
try {
    $pdo = Database::getInstance();
    foreach ($tablas as $tabla) {
        $existe   = (bool) $pdo->query("SHOW TABLES LIKE '{$tabla}'")->fetch();
        $checks[] = check("Tabla: {$tabla}", $existe, '', $existe ? '' : "Importa database/schema.sql");
        if (!$existe) $allOk = false;
    }
} catch (Throwable $e) {
    // Si no hay conexión, ya se registró arriba
}

// ── 6. Directorios con escritura ─────────────────────────────
$dirs = [
    BASE_PATH . '/storage/facturas' => 'storage/facturas',
    BASE_PATH . '/storage/logs'     => 'storage/logs',
];
foreach ($dirs as $path => $label) {
    // Crear si no existe
    if (!is_dir($path)) {
        @mkdir($path, 0750, true);
    }
    $ok       = is_dir($path) && is_writable($path);
    $checks[] = check("Escritura: {$label}", $ok, $path, $ok ? '' : "chmod 750 {$label}");
    if (!$ok) $allOk = false;
}

// ── 7. mod_rewrite ───────────────────────────────────────────
$modRewrite = function_exists('apache_get_modules')
    ? in_array('mod_rewrite', apache_get_modules())
    : true; // En nginx o FastCGI asumimos OK
$checks[] = check('mod_rewrite', $modRewrite, '', $modRewrite ? '' : 'Activa mod_rewrite en Apache y AllowOverride All');
if (!$modRewrite) $allOk = false;

// ── 8. cURL para OpenAI API ───────────────────────────────────
if (function_exists('curl_version')) {
    $cv = curl_version();
    $checks[] = check('cURL', true, 'v' . $cv['version'] . ' — SSL: ' . $cv['ssl_version']);
} else {
    $checks[] = check('cURL', false, '', 'Activa la extensión cURL en php.ini');
    $allOk = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup — OSCISA Solutions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light py-4">
<div class="container" style="max-width:750px">
    <div class="text-center mb-4">
        <h2><i class="bi bi-lightning-charge-fill text-primary me-2"></i>OSCISA Solutions</h2>
        <h5 class="text-muted">Verificación de instalación</h5>
    </div>

    <?php if ($allOk): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Todo correcto.</strong> El sistema está listo para funcionar.
            <hr>
            <a href="<?= BASE_URL ?>" class="btn btn-success btn-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i>Ir a la aplicación
            </a>
            <span class="text-muted ms-3 small">
                Recuerda <strong>eliminar o proteger</strong> este archivo (setup.php) antes de usar en producción.
            </span>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Hay elementos que requieren atención</strong> antes de poder usar la aplicación.
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:40px"></th>
                    <th>Verificación</th>
                    <th>Detalle</th>
                    <th>Acción recomendada</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $c): ?>
                <tr>
                    <td class="text-center">
                        <?php if ($c['ok']): ?>
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                        <?php endif; ?>
                    </td>
                    <td class="fw-medium"><?= htmlspecialchars($c['name']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($c['detail']) ?></td>
                    <td class="small text-danger"><?= htmlspecialchars($c['fix']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-transparent fw-semibold">Pasos para instalar desde cero</div>
        <div class="card-body">
            <ol class="mb-0">
                <li class="mb-2">Sube todos los archivos del proyecto a tu servidor (salvo <code>.env</code> y <code>storage/facturas/</code>).</li>
                <li class="mb-2">Crea la base de datos en tu servidor remoto e importa <code>database/schema.sql</code>.</li>
                <li class="mb-2">Crea el fichero <code>.env</code> en la raíz del proyecto (no incluido en el repositorio):<br>
                    <code class="bg-light px-2 py-1 d-inline-block rounded mt-1">cp .env.example .env</code>
                </li>
                <li class="mb-2">Rellena en <code>.env</code>: <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>OPENAI_API_KEY</code> y <code>APP_URL</code>.</li>
                <li class="mb-2">Asegúrate de que <code>storage/facturas/</code> y <code>storage/logs/</code> tienen permisos de escritura (<code>chmod 750</code>).</li>
                <li class="mb-2">Accede a la app y haz login con <code>admin@oscisa.com</code> / <code>admin1234</code>. <strong>Cambia la contraseña inmediatamente.</strong></li>
                <li>Elimina o protege este archivo <code>public/setup.php</code>.</li>
            </ol>
        </div>
    </div>

    <p class="text-center text-muted small mt-3">
        OSCISA Solutions &copy; <?= date('Y') ?> — setup.php v1.0
    </p>
</div>
</body>
</html>
