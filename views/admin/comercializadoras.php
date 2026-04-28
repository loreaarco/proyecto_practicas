<?php $pageTitle = 'Admin — Comercializadoras — ' . APP_NAME; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-building me-2"></i>Comercializadoras</h1>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Panel admin
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent fw-semibold"><?= count($comercializadoras) ?> comercializadoras</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Nombre</th><th>ID corto</th><th>Estado</th></tr>
            </thead>
            <tbody>
                <?php foreach ($comercializadoras as $c): ?>
                    <tr>
                        <td class="text-muted"><?= $c['id'] ?></td>
                        <td class="fw-medium"><?= htmlspecialchars($c['nombre']) ?></td>
                        <td><code><?= htmlspecialchars($c['nombre_corto'] ?? '—') ?></code></td>
                        <td>
                            <span class="badge bg-<?= $c['activa'] ? 'success' : 'secondary' ?>">
                                <?= $c['activa'] ? 'Activa' : 'Inactiva' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
