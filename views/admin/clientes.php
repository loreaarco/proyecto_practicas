<?php $pageTitle = 'Admin — Clientes — ' . APP_NAME; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i>Todos los clientes</h1>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Panel admin
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent fw-semibold"><?= count($clientes) ?> cliente(s) activos</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Cliente</th>
                    <th>CIF/NIF</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Comercial</th>
                    <th>Alta</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Sin clientes.</td></tr>
                <?php else: ?>
                    <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/clientes/<?= $c['id'] ?>" class="fw-medium text-decoration-none">
                                    <?= htmlspecialchars($c['nombre']) ?>
                                    <?= $c['apellidos'] ? htmlspecialchars($c['apellidos']) : '' ?>
                                </a>
                                <?php if ($c['empresa']): ?>
                                    <div class="text-muted"><?= htmlspecialchars($c['empresa']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($c['cif_nif'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
                            <td>
                                <span class="badge" style="background:<?= $c['color_hex'] ?>">
                                    <?= htmlspecialchars($c['estado_nombre']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($c['comercial_nombre']) ?></td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/clientes/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
