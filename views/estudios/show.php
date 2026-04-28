<?php $pageTitle = 'Estudio comparativo — ' . APP_NAME; ?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>/clientes/<?= $estudio['cliente_id'] ?>" class="btn-back">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="page-title"><?= htmlspecialchars($estudio['titulo'] ?? "Estudio #{$estudio['id']}") ?></h1>
            <p class="page-subtitle mb-0">
                Cliente: <a href="<?= BASE_URL ?>/clientes/<?= $estudio['cliente_id'] ?>"
                            style="color:var(--text);font-weight:500"><?= htmlspecialchars($estudio['cliente_nombre']) ?></a>
            </p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge" style="font-size:12.5px;padding:5px 14px;background:<?= $estudio['color_hex'] ?>">
            <?= htmlspecialchars($estudio['estado_nombre']) ?>
        </span>
        <form method="POST" action="<?= BASE_URL ?>/estudios/<?= $estudio['id'] ?>/calcular"
              onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='<span class=\'spinner-border spinner-border-sm\'></span> Calculando...'">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-calculator"></i> Calcular comparativa
            </button>
        </form>
    </div>
</div>

<!-- Cambiar estado -->
<div class="card mb-4" style="border-style:dashed !important">
    <div class="card-body" style="padding:12px 20px !important">
        <form method="POST" action="<?= BASE_URL ?>/estudios/<?= $estudio['id'] ?>/estado"
              class="d-flex align-items-center gap-3">
            <span style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);white-space:nowrap">
                Cambiar estado
            </span>
            <select name="estado_id" class="form-select form-select-sm" style="max-width:220px">
                <?php foreach ($estados as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $estudio['estado_id'] == $e['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Actualizar</button>
        </form>
    </div>
</div>

<!-- Resultados -->
<?php if (empty($resultados)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-bar-chart-line"></i></div>
                <div class="empty-state-title">Sin resultados</div>
                <p class="empty-state-text">
                    Pulsa <strong>"Calcular comparativa"</strong> para ver las ofertas disponibles y el ahorro estimado.
                </p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-trophy" style="color:var(--yellow);font-size:16px"></i>
        <h2 style="font-size:16px;font-weight:700;margin:0">Ofertas disponibles — ranking por ahorro</h2>
    </div>

    <div class="row g-3">
        <?php foreach ($resultados as $r): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 <?= $r['ranking'] === 1 ? 'offer-best' : '' ?>" style="overflow:hidden">

                    <?php if ($r['ranking'] === 1): ?>
                        <div class="offer-best-banner">
                            <i class="bi bi-star-fill me-1"></i>Mejor oferta
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <!-- Rank + supplier -->
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div style="width:28px;height:28px;border-radius:50%;background:<?= $r['ranking']===1?'#fef3c7':'var(--border-light)' ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:<?= $r['ranking']===1?'var(--yellow)':'var(--text-muted)' ?>;flex-shrink:0">
                                <?= $r['ranking'] ?>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;line-height:1.3"><?= htmlspecialchars($r['comercializadora']) ?></div>
                                <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($r['nombre_oferta']) ?></div>
                            </div>
                        </div>

                        <!-- Costes -->
                        <div class="row g-2 mb-3">
                            <div class="col-6" style="text-align:center;padding:12px;background:var(--border-light);border-radius:var(--radius-sm)">
                                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:4px">Coste estimado</div>
                                <div style="font-size:18px;font-weight:700;letter-spacing:-.3px"><?= number_format($r['coste_calculado'], 2, ',', '.') ?> €</div>
                            </div>
                            <div class="col-6" style="text-align:center;padding:12px;background:var(--border-light);border-radius:var(--radius-sm)">
                                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:4px">Factura actual</div>
                                <div style="font-size:18px;font-weight:700;letter-spacing:-.3px;color:var(--text-muted)"><?= number_format($r['coste_actual'], 2, ',', '.') ?> €</div>
                            </div>
                        </div>

                        <!-- Ahorro -->
                        <div style="text-align:center;padding:14px;background:<?= $r['ahorro_estimado'] > 0 ? 'var(--green-bg)' : 'var(--red-bg)' ?>;border-radius:var(--radius-sm);margin-bottom:12px">
                            <?php if ($r['ahorro_estimado'] > 0): ?>
                                <div style="font-size:22px;font-weight:700;letter-spacing:-.5px;color:var(--green)">
                                    <i class="bi bi-arrow-down-circle-fill" style="font-size:16px;vertical-align:middle;margin-right:4px"></i>
                                    <?= number_format($r['ahorro_estimado'], 2, ',', '.') ?> €
                                </div>
                                <div style="font-size:12px;color:var(--green);margin-top:2px;opacity:.8">
                                    <?= $r['ahorro_pct'] ?>% de ahorro anual estimado
                                </div>
                            <?php else: ?>
                                <div style="font-size:18px;font-weight:700;color:var(--red)">
                                    +<?= number_format(abs($r['ahorro_estimado']), 2, ',', '.') ?> € más
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="font-size:12px;color:var(--text-muted);text-align:center;margin-bottom:12px">
                            Comisión OSCISA: <strong style="color:var(--text)"><?= number_format($r['comision_estimada'], 2, ',', '.') ?> €</strong>
                        </div>

                        <!-- Desglose -->
                        <?php
                        $detalle = is_string($r['detalle_calculo'])
                            ? json_decode($r['detalle_calculo'], true)
                            : $r['detalle_calculo'];
                        ?>
                        <?php if ($detalle): ?>
                            <a class="collapse-toggle d-flex align-items-center gap-1" style="color:var(--text-muted);font-size:12.5px;cursor:pointer"
                               data-bs-toggle="collapse" href="#detalle-<?= $r['ranking'] ?>">
                                <i class="bi bi-chevron-down" style="font-size:11px"></i> Ver desglose
                            </a>
                            <div class="collapse" id="detalle-<?= $r['ranking'] ?>">
                                <dl class="row mt-2 mb-0" style="font-size:12.5px;row-gap:4px">
                                    <?php
                                    $desglose = [
                                        'Término potencia' => $detalle['coste_potencia'] ?? 0,
                                        'Término energía'  => $detalle['coste_energia']  ?? 0,
                                        'Alquiler equipos' => $detalle['coste_alquiler'] ?? 0,
                                        'IEE (5,11%)'      => $detalle['iee']            ?? 0,
                                        'IVA (10%)'        => $detalle['iva']            ?? 0,
                                    ];
                                    foreach ($desglose as $dLabel => $dVal): ?>
                                        <dt class="col-7" style="color:var(--text-muted);font-weight:400"><?= $dLabel ?></dt>
                                        <dd class="col-5 text-end mb-0"><?= number_format($dVal, 2, ',', '.') ?> €</dd>
                                    <?php endforeach; ?>
                                </dl>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
