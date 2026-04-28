<?php $pageTitle = 'Seguimiento — ' . htmlspecialchars($cliente['nombre']) . ' — ' . APP_NAME; ?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="h3 mb-0"><i class="bi bi-journal-text me-2"></i>Seguimiento comercial</h1>
        <small class="text-muted">Cliente: <strong><?= htmlspecialchars($cliente['nombre']) ?></strong></small>
    </div>
</div>

<div class="row g-4">
    <!-- Formulario nueva acción -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">Nueva acción</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/seguimiento">
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="nota">Nota interna</option>
                            <option value="llamada">Llamada</option>
                            <option value="email">Email</option>
                            <option value="reunion">Reunión</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción <span class="text-danger">*</span></label>
                        <textarea name="descripcion" class="form-control" rows="4" required
                                  placeholder="Descripción de la acción o contacto..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Próxima acción</label>
                        <input type="text" name="proxima_accion" class="form-control"
                               placeholder="Qué hay que hacer a continuación...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha próxima acción</label>
                        <input type="date" name="fecha_proxima" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i>Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Timeline de seguimientos -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                Historial (<?= count($seguimientos) ?> registros)
            </div>
            <div class="card-body">
                <?php if (empty($seguimientos)): ?>
                    <p class="text-muted text-center py-3">Sin registros de seguimiento todavía.</p>
                <?php else: ?>
                    <?php
                    $iconos = [
                        'llamada'  => ['bi-telephone-fill', 'text-success'],
                        'email'    => ['bi-envelope-fill', 'text-primary'],
                        'reunion'  => ['bi-calendar-event-fill', 'text-warning'],
                        'whatsapp' => ['bi-whatsapp', 'text-success'],
                        'nota'     => ['bi-sticky-fill', 'text-secondary'],
                        'otro'     => ['bi-three-dots', 'text-muted'],
                    ];
                    foreach ($seguimientos as $s):
                        [$ico, $col] = $iconos[$s['tipo']] ?? ['bi-dot', 'text-muted'];
                    ?>
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0 pt-1">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                 style="width:36px;height:36px">
                                <i class="bi <?= $ico ?> <?= $col ?>"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <strong><?= htmlspecialchars($s['usuario_nombre']) ?></strong>
                                <small class="text-muted ms-2 text-nowrap">
                                    <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?>
                                </small>
                            </div>
                            <p class="mb-1 mt-1"><?= nl2br(htmlspecialchars($s['descripcion'])) ?></p>
                            <?php if ($s['proxima_accion']): ?>
                                <div class="alert alert-light py-1 px-2 mb-0 small">
                                    <i class="bi bi-calendar2-check me-1 text-primary"></i>
                                    <strong>Próxima acción:</strong> <?= htmlspecialchars($s['proxima_accion']) ?>
                                    <?php if ($s['fecha_proxima']): ?>
                                        — <span class="text-primary"><?= date('d/m/Y', strtotime($s['fecha_proxima'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
