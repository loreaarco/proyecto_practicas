<?php $pageTitle = htmlspecialchars($cliente['nombre']) . ' — ' . APP_NAME; ?>

<!-- Cabecera -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>/clientes" class="btn-back"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title">
                <?= htmlspecialchars($cliente['nombre']) ?>
                <?= $cliente['apellidos'] ? ' ' . htmlspecialchars($cliente['apellidos']) : '' ?>
            </h1>
            <?php if ($cliente['empresa']): ?>
                <p class="page-subtitle mb-0"><?= htmlspecialchars($cliente['empresa']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge fs-6 px-3" style="background:<?= $cliente['color_hex'] ?>">
            <?= htmlspecialchars($cliente['estado_nombre']) ?>
        </span>
        <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/editar" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil"></i> Editar
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Columna izquierda -->
    <div class="col-lg-4">
        <!-- Datos -->
        <div class="card mb-3">
            <div class="card-header">Datos del cliente</div>
            <div class="card-body">
                <dl class="row mb-0" style="row-gap:12px">
                    <?php
                    $campos = [
                        'CIF/NIF'    => $cliente['cif_nif'] ?? null,
                        'Email'      => $cliente['email'] ?? null,
                        'Teléfono'   => $cliente['telefono'] ?? null,
                        'Comercial'  => $cliente['comercial_nombre'] ?? null,
                        'Alta'       => date('d/m/Y', strtotime($cliente['created_at'])),
                    ];
                    $direccion = implode(', ', array_filter([
                        $cliente['direccion'], $cliente['poblacion'],
                        $cliente['codigo_postal'], $cliente['provincia']
                    ]));
                    if ($direccion) $campos['Dirección'] = $direccion;
                    foreach ($campos as $label => $value): ?>
                        <dt class="col-5" style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);padding-top:0">
                            <?= $label ?>
                        </dt>
                        <dd class="col-7 mb-0" style="font-size:13.5px">
                            <?= htmlspecialchars($value ?: '—') ?>
                        </dd>
                    <?php endforeach; ?>
                </dl>
                <?php if ($cliente['notas']): ?>
                    <hr>
                    <p style="font-size:13px;color:var(--text-muted);margin:0">
                        <?= nl2br(htmlspecialchars($cliente['notas'])) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cambiar estado -->
        <div class="card mb-3">
            <div class="card-body" style="padding:14px 20px !important">
                <p style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:8px">
                    Cambiar estado
                </p>
                <form method="POST" action="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/estado"
                      class="d-flex gap-2">
                    <select name="estado_id" class="form-select form-select-sm flex-grow-1">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= $cliente['estado_id'] == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">Guardar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Columna derecha -->
    <div class="col-lg-8">

        <!-- Facturas -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-text me-2" style="color:var(--text-muted)"></i>Facturas</span>
                <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/facturas/subir" class="btn btn-primary btn-sm">
                    <i class="bi bi-upload"></i> Subir
                </a>
            </div>
            <?php if (empty($facturas)): ?>
                <div class="card-body">
                    <div class="empty-state" style="padding:28px 24px">
                        <div class="empty-state-icon"><i class="bi bi-file-earmark"></i></div>
                        <p class="empty-state-text">Sin facturas subidas todavía.</p>
                    </div>
                </div>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php
                    $extractionBadge = [
                        'pendiente'  => ['bg' => 'var(--yellow-bg)',  'color' => 'var(--yellow)'],
                        'procesando' => ['bg' => 'var(--blue-bg)',    'color' => 'var(--blue)'],
                        'completada' => ['bg' => 'var(--green-bg)',   'color' => 'var(--green)'],
                        'error'      => ['bg' => 'var(--red-bg)',     'color' => 'var(--red)'],
                    ];
                    foreach ($facturas as $f):
                        $eb = $extractionBadge[$f['estado_extraccion']] ?? ['bg'=>'var(--border-light)','color'=>'var(--text-muted)'];
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="<?= BASE_URL ?>/facturas/<?= $f['id'] ?>" style="font-size:13.5px;font-weight:500;color:var(--text)">
                                    <?= htmlspecialchars($f['nombre_original']) ?>
                                </a>
                                <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
                                    <?= date('d/m/Y', strtotime($f['created_at'])) ?>
                                    <?php if ($f['comercializadora']): ?>
                                        <span style="margin:0 4px;opacity:.4">·</span><?= htmlspecialchars($f['comercializadora']) ?>
                                    <?php endif; ?>
                                    <?php if ($f['importe_total']): ?>
                                        <span style="margin:0 4px;opacity:.4">·</span><strong style="color:var(--text)"><?= number_format($f['importe_total'], 2, ',', '.') ?> €</strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge" style="background:<?= $eb['bg'] ?>;color:<?= $eb['color'] ?>">
                                <?= $f['estado_extraccion'] ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Nuevo estudio -->
        <?php if (!empty($facturas)):
            $facturasCompletas = array_filter($facturas, fn($f) => $f['estado_extraccion'] === 'completada');
        ?>
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-calculator me-2" style="color:var(--text-muted)"></i>Nuevo estudio comparativo
            </div>
            <div class="card-body">
                <?php if (empty($facturasCompletas)): ?>
                    <p style="font-size:13px;color:var(--text-muted);margin:0">
                        Para crear un estudio necesitas al menos una factura con datos extraídos.
                    </p>
                <?php else: ?>
                    <form method="POST" action="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/estudios"
                          class="row g-2">
                        <div class="col-md-5">
                            <select name="factura_id" class="form-select form-select-sm" required>
                                <option value="">Seleccionar factura...</option>
                                <?php foreach ($facturasCompletas as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nombre_original']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="titulo" class="form-control form-control-sm"
                                   placeholder="Título del estudio (opcional)">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-success w-100">
                                <i class="bi bi-plus-lg"></i> Crear
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estudios -->
        <?php if (!empty($estudios)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-bar-chart-line me-2" style="color:var(--text-muted)"></i>Estudios
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($estudios as $e): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= BASE_URL ?>/estudios/<?= $e['id'] ?>"
                           style="font-size:13.5px;font-weight:500;color:var(--text)">
                            <?= htmlspecialchars($e['titulo'] ?? "Estudio #{$e['id']}") ?>
                        </a>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($e['ahorro_anual_estimado']): ?>
                                <span style="font-size:13px;font-weight:700;color:var(--green)">
                                    +<?= number_format($e['ahorro_anual_estimado'], 0, ',', '.') ?> €/año
                                </span>
                            <?php endif; ?>
                            <span class="badge" style="background:<?= $e['color_hex'] ?>">
                                <?= htmlspecialchars($e['estado_nombre']) ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Seguimiento comercial -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-journal-text me-2" style="color:var(--text-muted)"></i>Seguimiento comercial
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/seguimiento"
                      class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select name="tipo" class="form-select form-select-sm">
                                <?php foreach (['nota'=>'Nota','llamada'=>'Llamada','email'=>'Email','reunion'=>'Reunión','whatsapp'=>'WhatsApp'] as $v=>$l): ?>
                                    <option value="<?= $v ?>"><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <textarea name="descripcion" class="form-control form-control-sm"
                                      rows="2" placeholder="Descripción de la acción..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="proxima_accion" class="form-control form-control-sm"
                                   placeholder="Próxima acción (opcional)">
                        </div>
                        <div class="col-md-4">
                            <input type="date" name="fecha_proxima" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Guardar</button>
                        </div>
                    </div>
                </form>

                <?php if (!empty($seguimientos)): ?>
                    <div class="timeline" style="max-height:360px;overflow-y:auto;padding-right:4px">
                        <?php
                        $tipoIcons = [
                            'llamada'  => 'bi-telephone',
                            'email'    => 'bi-envelope',
                            'reunion'  => 'bi-calendar-event',
                            'whatsapp' => 'bi-whatsapp',
                            'nota'     => 'bi-sticky',
                        ];
                        foreach ($seguimientos as $s):
                            $icon = $tipoIcons[$s['tipo']] ?? 'bi-chat';
                        ?>
                            <div class="timeline-item">
                                <div class="timeline-dot">
                                    <i class="bi <?= $icon ?>"></i>
                                </div>
                                <div class="timeline-body">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size:13px;font-weight:600"><?= htmlspecialchars($s['usuario_nombre']) ?></span>
                                        <span style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></span>
                                    </div>
                                    <span class="timeline-badge"><?= $s['tipo'] ?></span>
                                    <p style="font-size:13px;margin:4px 0 0">
                                        <?= nl2br(htmlspecialchars($s['descripcion'])) ?>
                                    </p>
                                    <?php if ($s['proxima_accion']): ?>
                                        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--blue);margin-top:8px">
                                            <i class="bi bi-calendar2-check"></i>
                                            <?= htmlspecialchars($s['proxima_accion']) ?>
                                            <?= $s['fecha_proxima'] ? ' — ' . date('d/m/Y', strtotime($s['fecha_proxima'])) : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
