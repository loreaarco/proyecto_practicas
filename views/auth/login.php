<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — <?= APP_NAME ?></title>
    <link rel="icon" type="image/webp" href="<?= ASSET_URL ?>/assets/img/logo.webp">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= ASSET_URL ?>/assets/css/app.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card-inner">

        <div class="text-center mb-5">
            <img src="<?= ASSET_URL ?>/assets/img/logo.webp" alt="Grupo Oscisa" height="56" style="object-fit:contain;margin-bottom:16px">
            <h1 style="font-size:20px;font-weight:700;letter-spacing:-.3px;margin-bottom:4px">Grupo Oscisa</h1>
            <p style="font-size:13px;color:var(--text-muted);margin:0">Plataforma de comparación energética</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/auth/login" novalidate>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required autofocus
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="usuario@oscisa.com">
            </div>
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required
                       placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:14px">
                <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
            </button>
        </form>

        <p class="text-center mt-5 mb-0" style="font-size:12px;color:var(--text-subtle)">
            Grupo Oscisa &copy; <?= date('Y') ?>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
