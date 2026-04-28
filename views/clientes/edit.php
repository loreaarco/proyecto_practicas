<?php $pageTitle = 'Editar cliente — ' . APP_NAME; ?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h3 mb-0"><i class="bi bi-pencil me-2"></i>Editar cliente</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/editar" novalidate>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-12"><h6 class="text-muted text-uppercase small fw-bold">Datos personales</h6></div>

                <div class="col-md-4">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($cliente['apellidos'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Empresa</label>
                    <input type="text" name="empresa" class="form-control" value="<?= htmlspecialchars($cliente['empresa'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CIF/NIF</label>
                    <input type="text" name="cif_nif" class="form-control" value="<?= htmlspecialchars($cliente['cif_nif'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Teléfono 2</label>
                    <input type="text" name="telefono2" class="form-control" value="<?= htmlspecialchars($cliente['telefono2'] ?? '') ?>">
                </div>

                <div class="col-12 mt-2"><h6 class="text-muted text-uppercase small fw-bold">Dirección</h6></div>
                <div class="col-md-6">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Población</label>
                    <input type="text" name="poblacion" class="form-control" value="<?= htmlspecialchars($cliente['poblacion'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Provincia</label>
                    <input type="text" name="provincia" class="form-control" value="<?= htmlspecialchars($cliente['provincia'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">CP</label>
                    <input type="text" name="codigo_postal" class="form-control" value="<?= htmlspecialchars($cliente['codigo_postal'] ?? '') ?>">
                </div>

                <div class="col-12 mt-2"><h6 class="text-muted text-uppercase small fw-bold">Gestión</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado_id" class="form-select">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= $cliente['estado_id'] == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (Auth::hasRole('admin') && !empty($comerciales)): ?>
                <div class="col-md-4">
                    <label class="form-label">Comercial</label>
                    <select name="comercial_id" class="form-select">
                        <?php foreach ($comerciales as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $cliente['comercial_id'] == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <label class="form-label">Notas internas</label>
                    <textarea name="notas" class="form-control" rows="3"><?= htmlspecialchars($cliente['notas'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i>Guardar cambios
                </button>
                <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
