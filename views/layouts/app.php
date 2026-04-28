<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <link rel="icon" type="image/webp" href="<?= ASSET_URL ?>/assets/img/logo.webp">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= ASSET_URL ?>/assets/css/app.css">
</head>
<body>

<?php if (Auth::check()): ?>
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">

        <a class="navbar-brand" href="<?= BASE_URL ?>/dashboard">
            <img src="<?= ASSET_URL ?>/assets/img/logo.webp" alt="<?= APP_NAME ?>" height="32" style="object-fit:contain">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto ms-3 gap-1">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/dashboard">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/clientes">
                        <i class="bi bi-people me-1"></i>Clientes
                    </a>
                </li>
                <?php if (Auth::hasRole('admin')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-gear me-1"></i>Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin">
                            <i class="bi bi-speedometer me-2"></i>Panel Admin
                        </a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/tarifas">
                            <i class="bi bi-tags me-2"></i>Tarifas
                        </a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/usuarios">
                            <i class="bi bi-people me-2"></i>Usuarios
                        </a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link nav-user-trigger dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <span class="nav-user-avatar">
                            <?= mb_strtoupper(mb_substr(Auth::user()['nombre'], 0, 1)) ?>
                        </span>
                        <span><?= htmlspecialchars(Auth::user()['nombre']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text"><?= htmlspecialchars(Auth::rol()) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="container-fluid py-4">
    <?php
    $success = $success ?? Session::getFlash('success');
    $error   = $error   ?? Session::getFlash('error');
    if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php include $content; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSET_URL ?>/assets/js/app.js"></script>
</body>
</html>
