<?php $pageTitle = 'Admin — Usuarios — ' . APP_NAME; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-person-badge me-2"></i>Usuarios del sistema</h1>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Panel admin
    </a>
</div>

<div class="row g-4">
    <!-- Listado -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold"><?= count($usuarios) ?> usuario(s)</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Último acceso</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td class="fw-medium"><?= htmlspecialchars($u['nombre']) ?> <?= htmlspecialchars($u['apellidos'] ?? '') ?></td>
                                <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php
                                    $rolColor = ['admin'=>'danger','comercial'=>'primary','supervisor'=>'warning'];
                                    $col = $rolColor[$u['rol_nombre']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $col ?>"><?= htmlspecialchars($u['rol_nombre']) ?></span>
                                </td>
                                <td class="small text-muted">
                                    <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca' ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $u['activo'] ? 'success' : 'secondary' ?>">
                                        <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Crear usuario -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">Crear usuario</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/admin/usuarios/nuevo">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellidos</label>
                        <input type="text" name="apellidos" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña inicial</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                        <div class="form-text">Mínimo 8 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="rol_id" class="form-select" required>
                            <option value="2">Comercial</option>
                            <option value="3">Supervisor</option>
                            <option value="1">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus me-1"></i>Crear usuario
                    </button>
                </form>
                <div class="alert alert-info mt-3 small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    La creación de usuarios (lógica completa) se activará en la próxima iteración.
                </div>
            </div>
        </div>
    </div>
</div>
