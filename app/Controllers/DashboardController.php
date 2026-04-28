<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $db  = Database::getInstance();
        $uid = Auth::id();
        $esAdmin = Auth::hasRole('admin');

        // Métricas para el panel
        $whereComercial = $esAdmin ? '' : 'AND c.comercial_id = ' . $uid;

        $stats = [];

        // Total clientes activos
        $stmt = $db->query("SELECT COUNT(*) FROM clientes c WHERE c.activo = 1 {$whereComercial}");
        $stats['total_clientes'] = (int) $stmt->fetchColumn();

        // Estudios por estado
        $sql = "SELECT ec.nombre, ec.color_hex, COUNT(es.id) AS total
                FROM estudios es
                JOIN estados_comerciales ec ON ec.id = es.estado_id
                JOIN clientes c ON c.id = es.cliente_id
                WHERE 1=1 {$whereComercial}
                GROUP BY ec.id ORDER BY ec.orden";
        $stats['estudios_por_estado'] = $db->query($sql)->fetchAll();

        // Facturas pendientes de extracción
        $sql = "SELECT COUNT(*) FROM facturas f JOIN clientes c ON c.id = f.cliente_id
                WHERE f.estado_extraccion = 'pendiente' {$whereComercial}";
        $stats['facturas_pendientes'] = (int) $db->query($sql)->fetchColumn();

        // Ahorro total estimado en estudios aceptados
        $sql = "SELECT COALESCE(SUM(es.ahorro_anual_estimado), 0)
                FROM estudios es
                JOIN clientes c ON c.id = es.cliente_id
                JOIN estados_comerciales ec ON ec.id = es.estado_id
                WHERE ec.nombre = 'Aceptado' {$whereComercial}";
        $stats['ahorro_total_aceptado'] = (float) $db->query($sql)->fetchColumn();

        // Actividad reciente
        $sql = "SELECT s.*, u.nombre AS usuario_nombre, c.nombre AS cliente_nombre, c.id AS cliente_id
                FROM seguimiento_comercial s
                JOIN usuarios u ON u.id = s.usuario_id
                JOIN clientes c ON c.id = s.cliente_id
                WHERE 1=1 {$whereComercial}
                ORDER BY s.created_at DESC LIMIT 10";
        $stats['actividad_reciente'] = $db->query($sql)->fetchAll();

        $this->render('dashboard/index', compact('stats'));
    }
}
