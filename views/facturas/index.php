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
                        <th>Ahorro Estimado</th>
                        <th>Comisión</th>
                        <th>Estado extracción</th>
                        <th>Fecha subida</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $f):
                        $badgeMap = ['pendiente'=>'warning','procesando'=>'info','completada'=>'success','error'=>'danger'];
                        $badge = $badgeMap[$f['estado_extraccion']] ?? 'secondary';
                        
                        // Parsear datos JSON si están presentes
                        $dj = !empty($f['datos_json']) ? json_decode($f['datos_json'], true) : [];
                        $comercializadora = $f['comercializadora'] ?? $dj['comercializadora'] ?? '—';
                        $importe = $f['importe_total'] ?? $dj['importe_total'] ?? null;
                        $periodo_inicio = $f['periodo_inicio'] ?? $dj['periodo_inicio'] ?? null;
                        $periodo_fin = $f['periodo_fin'] ?? $dj['periodo_fin'] ?? null;
                    ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/facturas/<?= $f['id'] ?>">
                                    <i class="bi bi-file-earmark-<?= str_contains($f['mime_type'], 'pdf') ? 'pdf text-danger' : 'image text-primary' ?> me-1"></i>
                                    <?= htmlspecialchars($f['nombre_original']) ?>
                                </a>
                            </td>
                            <td class="small"><?= htmlspecialchars($comercializadora) ?></td>
                            <td class="small">
                                <?php if ($periodo_inicio && $periodo_fin): ?>
                                    <?= date('d/m/Y', strtotime($periodo_inicio)) ?>
                                    — <?= date('d/m/Y', strtotime($periodo_fin)) ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?= $importe ? number_format((float)$importe, 2, ',', '.') . ' €' : '—' ?></td>
                            <td class="text-success fw-bold"><?= isset($f['max_ahorro']) ? number_format((float)$f['max_ahorro'], 2, ',', '.') . ' €' : '—' ?></td>
                            <td class="text-primary"><?= isset($f['comision_estimada']) ? number_format((float)$f['comision_estimada'], 2, ',', '.') . ' €' : '—' ?></td>
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
