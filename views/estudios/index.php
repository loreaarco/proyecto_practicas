<?php $pageTitle = 'Estudios de ' . htmlspecialchars($cliente['nombre']) . ' — ' . APP_NAME; ?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="h3 mb-0"><i class="bi bi-bar-chart-line me-2"></i>Estudios comparativos</h1>
        <small class="text-muted">Cliente: <strong><?= htmlspecialchars($cliente['nombre']) ?></strong></small>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <?php if (empty($estudios)): ?>
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-calculator fs-1"></i>
            <p class="mt-3">No hay estudios para este cliente.</p>
            <p class="small">Para crear un estudio, sube y extrae los datos de una factura primero.</p>
            <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>" class="btn btn-outline-primary">
                Ir al cliente
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Estudio</th>
                        <th>Factura</th>
                        <th>Estado</th>
                        <th>Ahorro estimado</th>
                        <th>Comercial</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estudios as $e): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/estudios/<?= $e['id'] ?>" class="fw-medium text-decoration-none">
                                    <?= htmlspecialchars($e['titulo'] ?? "Estudio #{$e['id']}") ?>
                                </a>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($e['factura_nombre']) ?></td>
                            <td>
                                <span class="badge" style="background:<?= $e['color_hex'] ?>">
                                    <?= htmlspecialchars($e['estado_nombre']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($e['ahorro_anual_estimado']): ?>
                                    <span class="text-success fw-bold">
                                        <?= number_format($e['ahorro_anual_estimado'], 2, ',', '.') ?> €/año
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($e['comercial_nombre']) ?></td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/estudios/<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
