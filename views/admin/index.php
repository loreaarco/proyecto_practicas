<?php $pageTitle = 'Panel Admin — ' . APP_NAME; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-gear me-2"></i>Panel de administración</h1>
</div>

<div class="row g-3 mb-4">
    <?php
    foreach (
        [
            ['total_clientes',  'Clientes',  'bi-people',      'primary'],
            ['total_estudios',  'Estudios',  'bi-bar-chart',   'success'],
            ['total_usuarios',  'Usuarios',  'bi-person-badge', 'info'],
            ['total_tarifas',   'Tarifas',   'bi-tags',        'warning'],
        ] as [$key, $label, $icon, $color]
    ):

        $stats = [];

        /*
    -------------------------------------
    CONTADORES
    -------------------------------------
    */
        // 1. Obtener la conexión usando el Singleton de tu sistema
        $db = Database::getInstance();
        $stats = [];

        // 2. Ejecutar las consultas
        $stats['total_clientes'] = $db->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
        $stats['total_estudios'] = $db->query("SELECT COUNT(*) FROM estudios")->fetchColumn();
        $stats['total_usuarios'] = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $stats['total_tarifas']  = $db->query("SELECT COUNT(*) FROM tarifas_oferta")->fetchColumn();
        /*
    -------------------------------------
    ESTUDIOS RECIENTES
    -------------------------------------
    */

        // 2. Ejecutar consulta de estudios recientes con seguridad
        try {
            $stats['estudios_recientes'] = $db->query("
            SELECT 
                e.id, 
                e.created_at, 
                e.ahorro_anual_estimado,
                c.nombre AS cliente_nombre,
                u.nombre AS comercial_nombre,
                -- Usamos COALESCE por si falla el JOIN o la tabla de estados está vacía
                IFNULL(es.nombre, 'Pendiente') AS estado_nombre,
                IFNULL(es.color_hex, '#6c757d') AS color_hex
            FROM estudios e
            LEFT JOIN clientes c ON c.id = e.cliente_id
            LEFT JOIN usuarios u ON u.id = e.comercial_id
            -- ¡ATENCIÓN! Revisa en PHPMyAdmin si el nombre es 'estados_estudio'
            LEFT JOIN estados_estudio es ON es.id = e.estado_id
            ORDER BY e.created_at DESC
            LIMIT 10
        ")->fetchAll();
        } catch (PDOException $e) {
            // Si la tabla no existe o falla la SQL, evitamos el Fatal Error
            $stats['estudios_recientes'] = [];
            // Opcional: registrar el error para saber qué tabla falta
            // \core\Logger::error("Error en Dashboard: " . $e->getMessage());
        }

    ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-<?= $color ?> bg-opacity-10 rounded p-3">
                        <i class="bi <?= $icon ?> fs-4 text-<?= $color ?>"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold"><?= number_format($stats[$key]) ?></div>
                        <div class="text-muted small"><?= $label ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent fw-semibold">Estudios recientes</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Cliente</th>
                    <th>Comercial</th>
                    <th>Estado</th>
                    <th>Ahorro estimado</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['estudios_recientes'] as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['cliente_nombre']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($e['comercial_nombre']) ?></td>
                        <td><span class="badge" style="background:<?= $e['color_hex'] ?>"><?= htmlspecialchars($e['estado_nombre']) ?></span></td>
                        <td><?= $e['ahorro_anual_estimado'] ? number_format($e['ahorro_anual_estimado'], 2, ',', '.') . ' €' : '—' ?></td>
                        <td><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
                        <td><a href="<?= BASE_URL ?>/estudios/<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>