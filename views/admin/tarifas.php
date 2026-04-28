<?php $pageTitle = 'Admin — Tarifas — ' . APP_NAME; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-tags me-2"></i>Tarifas de oferta</h1>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Panel admin
    </a>
</div>

<div class="row g-4">
    <!-- Listado de tarifas -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold"><?= count($tarifas) ?> tarifa(s) configuradas</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Comercializadora</th>
                            <th>Oferta</th>
                            <th>Acceso</th>
                            <th>E. P1 (€/kWh)</th>
                            <th>E. P2 (€/kWh)</th>
                            <th>E. P3 (€/kWh)</th>
                            <th>Comisión</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tarifas)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Sin tarifas configuradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tarifas as $t): ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($t['comercializadora_nombre']) ?></td>
                                    <td><?= htmlspecialchars($t['nombre_oferta']) ?></td>
                                    <td><code><?= htmlspecialchars($t['tarifa_acceso']) ?></code></td>
                                    <td><?= number_format($t['precio_energia_p1'], 6) ?></td>
                                    <td><?= number_format($t['precio_energia_p2'], 6) ?></td>
                                    <td><?= number_format($t['precio_energia_p3'], 6) ?></td>
                                    <td>
                                        <?php if ($t['comision_tipo'] === 'fija'): ?>
                                            <?= number_format($t['comision_valor'], 2, ',', '.') ?> €/<?= $t['comision_periodicidad'] ?>
                                        <?php else: ?>
                                            <?= $t['comision_valor'] ?>%/<?= $t['comision_periodicidad'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $t['activa'] ? 'success' : 'secondary' ?>">
                                            <?= $t['activa'] ? 'Activa' : 'Inactiva' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulario nueva tarifa -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">Añadir tarifa</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/admin/tarifas/nueva">
                    <div class="mb-2">
                        <label class="form-label small">Comercializadora</label>
                        <select name="comercializadora_id" class="form-select form-select-sm" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($comercializadoras as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Nombre de la oferta</label>
                        <input type="text" name="nombre_oferta" class="form-control form-control-sm" required
                               placeholder="Ej: Iberdrola Pyme 2.0TD">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Tarifa de acceso</label>
                        <select name="tarifa_acceso" class="form-select form-select-sm" required>
                            <option value="2.0TD">2.0TD (doméstica/pyme pequeña)</option>
                            <option value="3.0TD">3.0TD (pyme mediana)</option>
                            <option value="6.1TD">6.1TD (gran consumo)</option>
                        </select>
                    </div>

                    <hr class="my-2">
                    <p class="small text-muted fw-semibold">Precio energía (€/kWh)</p>
                    <?php foreach (['P1','P2','P3'] as $p): ?>
                    <div class="mb-2">
                        <label class="form-label small"><?= $p ?></label>
                        <input type="number" step="0.000001" name="precio_energia_<?= strtolower($p) ?>"
                               class="form-control form-control-sm" placeholder="0.000000" required>
                    </div>
                    <?php endforeach; ?>

                    <hr class="my-2">
                    <p class="small text-muted fw-semibold">Precio potencia (€/kW/año)</p>
                    <?php foreach (['P1','P2'] as $p): ?>
                    <div class="mb-2">
                        <label class="form-label small"><?= $p ?></label>
                        <input type="number" step="0.000001" name="precio_potencia_<?= strtolower($p) ?>"
                               class="form-control form-control-sm" placeholder="0.000000" required>
                    </div>
                    <?php endforeach; ?>

                    <hr class="my-2">
                    <p class="small text-muted fw-semibold">Comisión OSCISA</p>
                    <div class="mb-2">
                        <div class="input-group input-group-sm">
                            <select name="comision_tipo" class="form-select form-select-sm">
                                <option value="fija">Fija (€)</option>
                                <option value="porcentaje">Porcentaje (%)</option>
                            </select>
                            <input type="number" step="0.01" name="comision_valor" class="form-control form-control-sm"
                                   placeholder="Valor" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <select name="comision_periodicidad" class="form-select form-select-sm">
                            <option value="anual">Anual</option>
                            <option value="mensual">Mensual</option>
                            <option value="unica">Única</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Vigente desde</label>
                        <input type="date" name="vigente_desde" class="form-control form-control-sm"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-lg me-1"></i>Añadir tarifa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
