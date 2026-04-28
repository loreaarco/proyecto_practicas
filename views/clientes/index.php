<?php $pageTitle = 'Clientes — ' . APP_NAME; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Clientes</h1>
        <p class="page-subtitle mb-0"><?= number_format($total) ?> cliente<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/clientes/nuevo" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo cliente
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body" style="padding:14px 20px !important">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar nombre, empresa, CIF..."
                       value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="estado_id" class="form-select">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= ($filtros['estado_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
            <div class="col-auto">
                <a href="<?= BASE_URL ?>/clientes" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>CIF / NIF</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th style="text-align:center">Facturas</th>
                    <th>Comercial</th>
                    <th style="width:48px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                                <div class="empty-state-title">Sin resultados</div>
                                <p class="empty-state-text">No hay clientes con los filtros aplicados.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/clientes/<?= $c['id'] ?>" class="fw-medium"
                                   style="color:var(--text)">
                                    <?= htmlspecialchars($c['nombre']) ?>
                                    <?php if ($c['apellidos']): ?> <?= htmlspecialchars($c['apellidos']) ?><?php endif; ?>
                                </a>
                                <?php if ($c['empresa']): ?>
                                    <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($c['empresa']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--text-muted);font-size:13px"><?= htmlspecialchars($c['cif_nif'] ?? '—') ?></td>
                            <td style="font-size:13px"><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
                            <td>
                                <span class="badge" style="background:<?= $c['color_hex'] ?>">
                                    <?= htmlspecialchars($c['estado_nombre']) ?>
                                </span>
                            </td>
                            <td style="text-align:center">
                                <span style="font-size:13px;color:var(--text-muted)"><?= $c['total_facturas'] ?></span>
                            </td>
                            <td style="font-size:13px;color:var(--text-muted)"><?= htmlspecialchars($c['comercial_nombre']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/clientes/<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPags > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination pagination-sm mb-0 justify-content-center">
                    <?php for ($p = 1; $p <= $totalPags; $p++): ?>
                        <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => $p])) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
