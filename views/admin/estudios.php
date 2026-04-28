<?php $pageTitle = 'Admin — Estudios — ' . APP_NAME; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-bar-chart-line me-2"></i>Todos los estudios</h1>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Panel admin
    </a>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted">Estado</label>
                <select name="estado_id" class="form-select form-select-sm">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $filtroEstado == $e['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Comercial</label>
                <select name="comercial_id" class="form-select form-select-sm">
                    <option value="">Todos los comerciales</option>
                    <?php foreach ($comerciales as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filtroComercial == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
            </div>
            <div class="col-auto">
                <a href="<?= BASE_URL ?>/admin/estudios" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent fw-semibold"><?= count($estudios) ?> estudio(s)</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Factura</th>
                    <th>Comercial</th>
                    <th>Estado</th>
                    <th>Ahorro estimado</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estudios)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Sin estudios con estos filtros.</td></tr>
                <?php else: ?>
                    <?php foreach ($estudios as $e): ?>
                        <tr>
                            <td class="text-muted"><?= $e['id'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/clientes/<?= $e['cliente_id'] ?>">
                                    <?= htmlspecialchars($e['cliente_nombre']) ?>
                                </a>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars(mb_substr($e['factura_nombre'], 0, 30)) ?>...</td>
                            <td><?= htmlspecialchars($e['comercial_nombre']) ?></td>
                            <td>
                                <span class="badge" style="background:<?= $e['color_hex'] ?>">
                                    <?= htmlspecialchars($e['estado_nombre']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($e['ahorro_anual_estimado']): ?>
                                    <span class="text-success fw-bold">
                                        <?= number_format($e['ahorro_anual_estimado'], 2, ',', '.') ?> €
                                    </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/estudios/<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary">
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
