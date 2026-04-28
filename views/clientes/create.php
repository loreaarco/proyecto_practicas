<?php $pageTitle = 'Nuevo cliente — ' . APP_NAME; ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= BASE_URL ?>/clientes" class="btn-back"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title">Nuevo cliente</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/clientes/nuevo" novalidate>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <!-- Datos personales -->
                <div class="col-12"><p class="form-section-title">Datos personales</p></div>

                <div class="col-md-4">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['nombre'] ?? '') ?>" required>
                    <?php if (isset($errors['nombre'])): ?>
                        <div class="invalid-feedback"><?= $errors['nombre'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($old['apellidos'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Empresa</label>
                    <input type="text" name="empresa" class="form-control" value="<?= htmlspecialchars($old['empresa'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CIF/NIF</label>
                    <input type="text" name="cif_nif" class="form-control" value="<?= htmlspecialchars($old['cif_nif'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($old['telefono'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Teléfono 2</label>
                    <input type="text" name="telefono2" class="form-control" value="<?= htmlspecialchars($old['telefono2'] ?? '') ?>">
                </div>

                <!-- Dirección -->
                <div class="col-12 mt-2"><p class="form-section-title">Dirección</p></div>
                <div class="col-md-6">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($old['direccion'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Población</label>
                    <input type="text" name="poblacion" class="form-control" value="<?= htmlspecialchars($old['poblacion'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Provincia</label>
                    <input type="text" name="provincia" class="form-control" value="<?= htmlspecialchars($old['provincia'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">CP</label>
                    <input type="text" name="codigo_postal" class="form-control" maxlength="5"
                           value="<?= htmlspecialchars($old['codigo_postal'] ?? '') ?>">
                </div>

                <!-- Gestión -->
                <div class="col-12 mt-2"><p class="form-section-title">Gestión</p></div>
                <div class="col-md-4">
                    <label class="form-label">Estado inicial</label>
                    <select name="estado_id" class="form-select">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= ($old['estado_id'] ?? 1) == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (Auth::hasRole('admin') && !empty($comerciales)): ?>
                <div class="col-md-4">
                    <label class="form-label">Asignar a comercial</label>
                    <select name="comercial_id" class="form-select">
                        <?php foreach ($comerciales as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($old['comercial_id'] ?? Auth::id()) == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <label class="form-label">Notas internas</label>
                    <textarea name="notas" class="form-control" rows="3"><?= htmlspecialchars($old['notas'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Crear cliente
                </button>
                <a href="<?= BASE_URL ?>/clientes" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
