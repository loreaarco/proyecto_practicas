<?php $pageTitle = 'Factura — ' . APP_NAME; ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= BASE_URL ?>/clientes/<?= $factura['cliente_id'] ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="page-title" style="font-size:17px"><?= htmlspecialchars($factura['nombre_original']) ?></h1>
        <p class="page-subtitle mb-0">
            Cliente: <a href="<?= BASE_URL ?>/clientes/<?= $factura['cliente_id'] ?>"
                        style="color:var(--text);font-weight:500"><?= htmlspecialchars($factura['cliente_nombre']) ?></a>
        </p>
    </div>
</div>

<div class="row g-4">
    <!-- Panel lateral -->
    <div class="col-lg-4">

        <!-- Estado y acciones -->
        <div class="card mb-3">
            <div class="card-body">
                <p style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:10px">
                    Estado de extracción
                </p>
                <?php
                $extractionBadge = [
                    'pendiente'  => ['bg' => 'var(--yellow-bg)',  'color' => 'var(--yellow)',  'label' => 'Pendiente'],
                    'procesando' => ['bg' => 'var(--blue-bg)',    'color' => 'var(--blue)',    'label' => 'Procesando'],
                    'completada' => ['bg' => 'var(--green-bg)',   'color' => 'var(--green)',   'label' => 'Completada'],
                    'error'      => ['bg' => 'var(--red-bg)',     'color' => 'var(--red)',     'label' => 'Error'],
                ];
                $eb = $extractionBadge[$factura['estado_extraccion']] ?? ['bg'=>'var(--border-light)','color'=>'var(--text-muted)','label'=>$factura['estado_extraccion']];
                ?>
                <span class="badge mb-3" style="font-size:12.5px;padding:5px 14px;background:<?= $eb['bg'] ?>;color:<?= $eb['color'] ?>">
                    <?= $eb['label'] ?>
                </span>

                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>/facturas/<?= $factura['id'] ?>/ver" target="_blank"
                       class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i> Ver archivo original
                    </a>

                    <?php if (in_array($factura['estado_extraccion'], ['pendiente', 'error'])): ?>
                        <form method="POST" action="<?= BASE_URL ?>/facturas/<?= $factura['id'] ?>/extraer"
                              onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='<span class=\'spinner-border spinner-border-sm\'></span> Extrayendo...'">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-robot"></i> Extraer datos con IA
                            </button>
                        </form>
                        <p style="font-size:12px;color:var(--text-muted);text-align:center;margin:0">
                            Intentos: <?= $factura['intentos_extraccion'] ?>/5
                        </p>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/facturas/<?= $factura['id'] ?>/datos"
                       class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Revisar / editar datos
                    </a>
                </div>
            </div>
        </div>

        <!-- Metadatos -->
        <div class="card">
            <div class="card-body">
                <dl class="row mb-0" style="row-gap:10px">
                    <?php
                    $meta = [
                        'Subido'  => date('d/m/Y H:i', strtotime($factura['created_at'])),
                        'Por'     => $factura['subida_por_nombre'],
                        'Tamaño'  => round($factura['tamanio_bytes'] / 1024, 1) . ' KB',
                        'Tipo'    => $factura['mime_type'],
                    ];
                    foreach ($meta as $lbl => $val): ?>
                        <dt class="col-5" style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);padding-top:0">
                            <?= $lbl ?>
                        </dt>
                        <dd class="col-7 mb-0" style="font-size:13px"><?= htmlspecialchars($val) ?></dd>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Datos extraídos -->
    <div class="col-lg-8">
        <?php if ($datos): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-table me-2" style="color:var(--text-muted)"></i>Datos extraídos</span>
                <?php if ($datos['revisado_manual']): ?>
                    <span class="badge" style="background:var(--green-bg);color:var(--green)">
                        <i class="bi bi-check-circle me-1"></i>Revisado manualmente
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">

                <!-- Suministro -->
                <p class="form-section-title mb-3">Titular y suministro</p>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Titular</div>
                        <div style="font-size:13.5px"><?= htmlspecialchars($datos['titular_nombre'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">CIF/NIF</div>
                        <div style="font-size:13.5px"><?= htmlspecialchars($datos['titular_cif_nif'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Tipo</div>
                        <div style="font-size:13.5px;text-transform:capitalize"><?= htmlspecialchars($datos['tipo_suministro'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-5">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Comercializadora</div>
                        <div style="font-size:13.5px"><?= htmlspecialchars($datos['comercializadora'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Tarifa</div>
                        <div style="font-size:13.5px"><?= htmlspecialchars($datos['tarifa_acceso'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">CUPS</div>
                        <div style="font-size:12px;font-family:monospace"><?= htmlspecialchars($datos['cups'] ?? '—') ?></div>
                    </div>
                </div>

                <hr>

                <!-- Periodo y consumo -->
                <p class="form-section-title mb-3">Periodo y consumo</p>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Periodo</div>
                        <div style="font-size:13.5px">
                            <?php if ($datos['periodo_inicio'] && $datos['periodo_fin']): ?>
                                <?= date('d/m/Y', strtotime($datos['periodo_inicio'])) ?> — <?= date('d/m/Y', strtotime($datos['periodo_fin'])) ?>
                            <?php else: ?>—<?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Días</div>
                        <div style="font-size:13.5px"><?= $datos['dias_facturados'] ?? '—' ?></div>
                    </div>
                    <div class="col-md-3">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Consumo total</div>
                        <div style="font-size:13.5px">
                            <?= $datos['consumo_total_kwh'] ? number_format($datos['consumo_total_kwh'], 0, ',', '.') . ' kWh' : '—' ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Importe total</div>
                        <div style="font-size:22px;font-weight:700;letter-spacing:-.5px;color:var(--text)">
                            <?= $datos['importe_total'] ? number_format($datos['importe_total'], 2, ',', '.') . ' €' : '—' ?>
                        </div>
                    </div>

                    <?php
                    $potencias = [];
                    for ($p = 1; $p <= 6; $p++) {
                        if ($datos["potencia_p{$p}_kw"] ?? null) {
                            $potencias[] = "<span style='background:var(--border-light);border-radius:4px;padding:2px 7px;font-size:12px'>P{$p}: " . number_format($datos["potencia_p{$p}_kw"], 3) . " kW</span>";
                        }
                    }
                    ?>
                    <?php if (!empty($potencias)): ?>
                    <div class="col-12">
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:6px">Potencias contratadas</div>
                        <div class="d-flex flex-wrap gap-1"><?= implode('', $potencias) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                    <div class="empty-state-title">Sin datos extraídos</div>
                    <p class="empty-state-text">
                        Pulsa <strong>"Extraer datos con IA"</strong> para iniciar el proceso automático.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php 
        $datosJson = !empty($factura['datos_json']) ? json_decode($factura['datos_json'], true) : []; 
        if (!empty($datosJson)):
        ?>
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="bi bi-robot me-2"></i>Análisis Módulo LLM
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase mb-1" style="font-size: 12px; font-weight: 600;">Mejor Oferta</h6>
                        <h5 class="fw-bold text-primary mb-0"><?= htmlspecialchars($datosJson['mejor_oferta'] ?? 'No detectada') ?></h5>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted text-uppercase mb-1" style="font-size: 12px; font-weight: 600;">Ahorro Anual</h6>
                        <h5 class="fw-bold text-success mb-0"><?= isset($factura['max_ahorro']) ? number_format((float)$factura['max_ahorro'], 2, ',', '.') : '0,00' ?> €</h5>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted text-uppercase mb-1" style="font-size: 12px; font-weight: 600;">Comisión</h6>
                        <h5 class="fw-bold text-info mb-0"><?= isset($factura['comision_estimada']) ? number_format((float)$factura['comision_estimada'], 2, ',', '.') : '0,00' ?> €</h5>
                    </div>
                </div>
                
                <div class="alert alert-light border mb-0 p-3">
                    <i class="bi bi-lightbulb text-warning me-2"></i>
                    <strong>Recomendación:</strong> 
                    <?= htmlspecialchars($datosJson['recomendacion'] ?? 'Análisis pendiente o no disponible') ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
