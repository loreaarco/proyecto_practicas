<?php $pageTitle = 'Dashboard — ' . APP_NAME; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle mb-0">Resumen de actividad</p>
    </div>
    <a href="<?= BASE_URL ?>/clientes/nuevo" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo cliente
    </a>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon red"><i class="bi bi-people"></i></div>
                <div>
                    <div class="kpi-value"><?= number_format($stats['total_clientes']) ?></div>
                    <div class="kpi-label">Clientes activos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon yellow"><i class="bi bi-file-earmark-text"></i></div>
                <div>
                    <div class="kpi-value"><?= number_format($stats['facturas_pendientes']) ?></div>
                    <div class="kpi-label">Facturas pendientes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon green"><i class="bi bi-piggy-bank"></i></div>
                <div>
                    <div class="kpi-value"><?= number_format($stats['ahorro_total_aceptado'], 0, ',', '.') ?> €</div>
                    <div class="kpi-label">Ahorro anual aceptado</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon blue"><i class="bi bi-bar-chart-line"></i></div>
                <div>
                    <?php $totalEstudios = array_sum(array_column($stats['estudios_por_estado'], 'total')); ?>
                    <div class="kpi-value"><?= number_format($totalEstudios) ?></div>
                    <div class="kpi-label">Estudios totales</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Estado de estudios -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-kanban me-2" style="color:var(--text-muted)"></i>Estado de estudios
            </div>
            <div class="card-body">
                <?php if (empty($stats['estudios_por_estado'])): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="bi bi-bar-chart"></i></div>
                        <p class="empty-state-text">Sin estudios todavía.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($stats['estudios_por_estado'] as $estado): ?>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge" style="background:<?= $estado['color_hex'] ?>;font-size:10px;padding:3px 8px">
                                            <?= $estado['total'] ?>
                                        </span>
                                        <span style="font-size:13px"><?= htmlspecialchars($estado['nombre']) ?></span>
                                    </div>
                                    <span style="font-size:12px;color:var(--text-muted)">
                                        <?= $totalEstudios > 0 ? round($estado['total']/$totalEstudios*100) : 0 ?>%
                                    </span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar"
                                         style="width:<?= $totalEstudios > 0 ? round($estado['total']/$totalEstudios*100) : 0 ?>%;background:<?= $estado['color_hex'] ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actividad reciente -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-clock-history me-2" style="color:var(--text-muted)"></i>Actividad reciente
            </div>
            <?php if (empty($stats['actividad_reciente'])): ?>
                <div class="card-body">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="bi bi-clock"></i></div>
                        <p class="empty-state-text">Sin actividad reciente.</p>
                    </div>
                </div>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($stats['actividad_reciente'] as $item): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div style="min-width:0">
                                    <div style="font-size:13px">
                                        <span class="fw-medium"><?= htmlspecialchars($item['usuario_nombre']) ?></span>
                                        <span style="color:var(--text-subtle);margin:0 4px">→</span>
                                        <a href="<?= BASE_URL ?>/clientes/<?= $item['cliente_id'] ?>" style="color:var(--text);font-weight:500">
                                            <?= htmlspecialchars($item['cliente_nombre']) ?>
                                        </a>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge" style="font-size:10px;background:var(--border-light);color:var(--text-muted);text-transform:capitalize">
                                            <?= $item['tipo'] ?>
                                        </span>
                                        <span style="font-size:12px;color:var(--text-muted)">
                                            <?= htmlspecialchars(mb_substr($item['descripcion'], 0, 70)) ?><?= strlen($item['descripcion']) > 70 ? '…' : '' ?>
                                        </span>
                                    </div>
                                </div>
                                <span style="font-size:12px;color:var(--text-subtle);white-space:nowrap;flex-shrink:0">
                                    <?= date('d/m H:i', strtotime($item['created_at'])) ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
