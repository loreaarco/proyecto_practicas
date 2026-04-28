<?php $pageTitle = 'Revisar datos — ' . APP_NAME; ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= BASE_URL ?>/facturas/<?= $factura['id'] ?>" class="btn-back"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title">Revisar / editar datos extraídos</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/facturas/<?= $factura['id'] ?>/datos">

            <div class="row g-3">
                <div class="col-12"><p class="form-section-title">Titular y suministro</p></div>
                <div class="col-md-5">
                    <label class="form-label">Nombre titular</label>
                    <input type="text" name="titular_nombre" class="form-control" value="<?= htmlspecialchars($datos['titular_nombre'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CIF/NIF titular</label>
                    <input type="text" name="titular_cif_nif" class="form-control" value="<?= htmlspecialchars($datos['titular_cif_nif'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">CUPS</label>
                    <input type="text" name="cups" class="form-control" value="<?= htmlspecialchars($datos['cups'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dirección suministro</label>
                    <input type="text" name="direccion_suministro" class="form-control" value="<?= htmlspecialchars($datos['direccion_suministro'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Comercializadora actual</label>
                    <input type="text" name="comercializadora" class="form-control" value="<?= htmlspecialchars($datos['comercializadora'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo suministro</label>
                    <select name="tipo_suministro" class="form-select">
                        <?php foreach (['electricidad', 'gas', 'dual'] as $tipo): ?>
                            <option value="<?= $tipo ?>" <?= ($datos['tipo_suministro'] ?? '') === $tipo ? 'selected' : '' ?>><?= ucfirst($tipo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tarifa acceso</label>
                    <input type="text" name="tarifa_acceso" class="form-control" value="<?= htmlspecialchars($datos['tarifa_acceso'] ?? '') ?>" placeholder="2.0TD">
                </div>

                <div class="col-12 mt-2"><p class="form-section-title">Periodo y consumo</p></div>
                <div class="col-md-2">
                    <label class="form-label">Inicio periodo</label>
                    <input type="date" name="periodo_inicio" class="form-control" value="<?= $datos['periodo_inicio'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fin periodo</label>
                    <input type="date" name="periodo_fin" class="form-control" value="<?= $datos['periodo_fin'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Días facturados</label>
                    <input type="number" name="dias_facturados" class="form-control" value="<?= $datos['dias_facturados'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Consumo total (kWh)</label>
                    <input type="number" step="0.001" name="consumo_total_kwh" class="form-control" value="<?= $datos['consumo_total_kwh'] ?? '' ?>">
                </div>

                <!-- Potencias -->
                <div class="col-12 mt-2"><p class="form-section-title">Potencias contratadas (kW)</p></div>
                <?php for ($p = 1; $p <= 6; $p++): ?>
                <div class="col-md-2">
                    <label class="form-label">P<?= $p ?></label>
                    <input type="number" step="0.001" name="potencia_p<?= $p ?>_kw" class="form-control"
                           value="<?= $datos["potencia_p{$p}_kw"] ?? '' ?>">
                </div>
                <?php endfor; ?>

                <!-- Consumos por periodo -->
                <div class="col-12 mt-2"><p class="form-section-title">Consumo por periodo (kWh)</p></div>
                <?php for ($p = 1; $p <= 6; $p++): ?>
                <div class="col-md-2">
                    <label class="form-label">P<?= $p ?></label>
                    <input type="number" step="0.001" name="consumo_p<?= $p ?>_kwh" class="form-control"
                           value="<?= $datos["consumo_p{$p}_kwh"] ?? '' ?>">
                </div>
                <?php endfor; ?>

                <!-- Importes -->
                <div class="col-12 mt-2"><p class="form-section-title">Importes (€)</p></div>
                <div class="col-md-3">
                    <label class="form-label">Importe potencia</label>
                    <input type="number" step="0.01" name="importe_potencia" class="form-control" value="<?= $datos['importe_potencia'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Importe energía</label>
                    <input type="number" step="0.01" name="importe_energia" class="form-control" value="<?= $datos['importe_energia'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Impuestos</label>
                    <input type="number" step="0.01" name="importe_impuestos" class="form-control" value="<?= $datos['importe_impuestos'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Importe TOTAL</label>
                    <input type="number" step="0.01" name="importe_total" class="form-control fw-bold" value="<?= $datos['importe_total'] ?? '' ?>">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-check-lg me-1"></i>Guardar y marcar como revisado
                </button>
                <a href="<?= BASE_URL ?>/facturas/<?= $factura['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
