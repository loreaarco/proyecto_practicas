<?php $pageTitle = 'Facturas de ' . htmlspecialchars($cliente['nombre']) . ' — ' . APP_NAME; ?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="h3 mb-0"><i class="bi bi-file-earmark-text me-2"></i>Facturas</h1>
        <small class="text-muted">Cliente: <strong><?= htmlspecialchars($cliente['nombre']) ?></strong></small>
    </div>
    <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/facturas/subir" class="btn btn-primary ms-auto">
        <i class="bi bi-upload me-1"></i>Subir factura
    </a>
</div>

<div class="card border-0 shadow-sm">
    <?php if (empty($facturas)): ?>
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-cloud-upload fs-1"></i>
            <p class="mt-3">No hay facturas subidas para este cliente.</p>
            <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/facturas/subir" class="btn btn-primary">
                <i class="bi bi-upload me-1"></i>Subir primera factura
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Archivo</th>
                        <th>Comercializadora</th>
                        <th>Periodo</th>
                        <th>Importe</th>
                        <th>Estado extracción</th>
                        <th>Fecha subida</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $f):
                        $badgeMap = ['pendiente'=>'warning','procesando'=>'info','completada'=>'success','error'=>'danger'];
                        $badge = $badgeMap[$f['estado_extraccion']] ?? 'secondary';
                    ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/facturas/<?= $f['id'] ?>">
                                    <i class="bi bi-file-earmark-<?= str_contains($f['mime_type'], 'pdf') ? 'pdf text-danger' : 'image text-primary' ?> me-1"></i>
                                    <?= htmlspecialchars($f['nombre_original']) ?>
                                </a>
                            </td>
                            <td class="small"><?= htmlspecialchars($f['comercializadora'] ?? '—') ?></td>
                            <td class="small">
                                <?php if ($f['periodo_inicio'] && $f['periodo_fin']): ?>
                                    <?= date('d/m/Y', strtotime($f['periodo_inicio'])) ?>
                                    — <?= date('d/m/Y', strtotime($f['periodo_fin'])) ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?= $f['importe_total'] ? number_format($f['importe_total'], 2, ',', '.') . ' €' : '—' ?></td>
                            <td><span class="badge bg-<?= $badge ?>"><?= $f['estado_extraccion'] ?></span></td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($f['created_at'])) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= BASE_URL ?>/facturas/<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/facturas/<?= $f['id'] ?>/ver" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver archivo">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
