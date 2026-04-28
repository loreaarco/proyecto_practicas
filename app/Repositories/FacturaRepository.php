<?php

/**
 * FacturaRepository - Acceso a datos de facturas y datos extraídos
 */
class FacturaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listarPorCliente(int $clienteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT f.*, d.importe_total, d.comercializadora, d.periodo_inicio, d.periodo_fin,
                    d.consumo_total_kwh, d.revisado_manual,
                    u.nombre AS subida_por_nombre
             FROM facturas f
             JOIN usuarios u ON u.id = f.subida_por
             LEFT JOIN datos_extraidos_factura d ON d.factura_id = f.id
             WHERE f.cliente_id = :cid
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([':cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function finalizarExtraccionIA(int $id, array $datosIA, int $intentos): void
    {
        $ahorro   = (float)($datosIA['ahorro_estimado'] ?? 0);
        $comision = (float)($datosIA['comision_estimada'] ?? 0);

        $sql = "UPDATE facturas SET 
                datos_json = :json,
                max_ahorro = :ahorro, 
                comision_estimada = :comision, 
                estado_extraccion = 'completada',
                intentos_extraccion = :intentos
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'json'     => json_encode($datosIA, JSON_UNESCAPED_UNICODE),
            'ahorro'   => $ahorro,
            'comision' => $comision,
            'intentos' => $intentos,
            'id'       => $id
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT f.*, c.nombre AS cliente_nombre, c.id AS cliente_id,
                    u.nombre AS subida_por_nombre
             FROM facturas f
             JOIN clientes c ON c.id = f.cliente_id
             JOIN usuarios u ON u.id = f.subida_por
             WHERE f.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO facturas
             (cliente_id, subida_por, nombre_original, nombre_fichero, ruta_almacen, mime_type, tamanio_bytes)
             VALUES (:cliente_id, :subida_por, :nombre_original, :nombre_fichero, :ruta_almacen, :mime_type, :tamanio_bytes)'
        );
        $stmt->execute([
            ':cliente_id'      => $datos['cliente_id'],
            ':subida_por'      => $datos['subida_por'],
            ':nombre_original' => $datos['nombre_original'],
            ':nombre_fichero'  => $datos['nombre_fichero'],
            ':ruta_almacen'    => $datos['ruta_almacen'],
            ':mime_type'       => $datos['mime_type'],
            ':tamanio_bytes'   => $datos['tamanio_bytes'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizarEstadoExtraccion(int $facturaId, string $estado, int $intentos): void
    {
        $stmt = $this->db->prepare(
            'UPDATE facturas SET estado_extraccion = :estado, intentos_extraccion = :intentos WHERE id = :id'
        );
        $stmt->execute([':estado' => $estado, ':intentos' => $intentos, ':id' => $facturaId]);
    }

    // ── Datos extraídos ──────────────────────────────────────────

    public function getDatosExtraidos(int $facturaId): ?array
    {
        // Intenta obtener de datos_extraidos_factura primero
        $stmt = $this->db->prepare(
            'SELECT * FROM datos_extraidos_factura WHERE factura_id = :fid LIMIT 1'
        );
        $stmt->execute([':fid' => $facturaId]);
        $datos = $stmt->fetch();

        if ($datos) {
            return $datos;
        }

        // Si no existe, devuelve datos_json de la factura si lo hay
        $stmt = $this->db->prepare('SELECT datos_json FROM facturas WHERE id = :fid LIMIT 1');
        $stmt->execute([':fid' => $facturaId]);
        $row = $stmt->fetch();

        if ($row && $row['datos_json']) {
            return json_decode($row['datos_json'], true);
        }

        return null;
    }

    public function guardarDatosExtraidos(int $facturaId, array $datos): void
    {
        // Upsert: si ya existe, actualiza; si no, inserta
        $existing = $this->getDatosExtraidos($facturaId);

        $campos = [
            'titular_nombre',
            'titular_cif_nif',
            'direccion_suministro',
            'cups',
            'comercializadora',
            'tipo_suministro',
            'tarifa_acceso',
            'potencia_p1_kw',
            'potencia_p2_kw',
            'potencia_p3_kw',
            'consumo_p1_kwh',
            'consumo_p2_kwh',
            'consumo_p3_kwh',
            'consumo_p4_kwh',
            'consumo_p5_kwh',
            'consumo_p6_kwh',
            'consumo_total_kwh',
            'periodo_inicio',
            'periodo_fin',
            'dias_facturados',
            'importe_potencia',
            'importe_energia',
            'importe_impuestos',
            'importe_total',
            'consumo_gas_kwh',
            'importe_gas',
            'datos_extra',
            'tokens_usados',
            'modelo_ia',
        ];

        $params = [':factura_id' => $facturaId];
        foreach ($campos as $campo) {
            $params[':' . $campo] = $datos[$campo] ?? null;
        }

        if ($existing) {
            $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", $campos));
            $stmt = $this->db->prepare("UPDATE datos_extraidos_factura SET {$sets} WHERE factura_id = :factura_id");
        } else {
            $colList = 'factura_id, ' . implode(', ', $campos);
            $valList = ':factura_id, ' . implode(', ', array_map(fn($c) => ":{$c}", $campos));
            $stmt = $this->db->prepare("INSERT INTO datos_extraidos_factura ({$colList}) VALUES ({$valList})");
        }

        $stmt->execute($params);
    }

    public function marcarRevisadoManual(int $facturaId, int $usuarioId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE datos_extraidos_factura SET revisado_manual = 1, revisado_por = :uid WHERE factura_id = :fid'
        );
        $stmt->execute([':uid' => $usuarioId, ':fid' => $facturaId]);
    }
}
