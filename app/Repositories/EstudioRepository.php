<?php

class EstudioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listarPorCliente(int $clienteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT es.*, ec.nombre AS estado_nombre, ec.color_hex,
                    u.nombre AS comercial_nombre,
                    f.nombre_original AS factura_nombre
             FROM estudios es
             JOIN estados_comerciales ec ON ec.id = es.estado_id
             JOIN usuarios u ON u.id = es.comercial_id
             JOIN facturas f ON f.id = es.factura_id
             WHERE es.cliente_id = :cid
             ORDER BY es.created_at DESC'
        );
        $stmt->execute([':cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT es.*, ec.nombre AS estado_nombre, ec.color_hex,
                    c.nombre AS cliente_nombre, c.id AS cliente_id,
                    u.nombre AS comercial_nombre,
                    f.nombre_original AS factura_nombre, f.id AS factura_id
             FROM estudios es
             JOIN estados_comerciales ec ON ec.id = es.estado_id
             JOIN clientes c ON c.id = es.cliente_id
             JOIN usuarios u ON u.id = es.comercial_id
             JOIN facturas f ON f.id = es.factura_id
             WHERE es.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO estudios (cliente_id, factura_id, comercial_id, estado_id, titulo, notas)
             VALUES (:cliente_id, :factura_id, :comercial_id, :estado_id, :titulo, :notas)'
        );
        $stmt->execute([
            ':cliente_id'   => $datos['cliente_id'],
            ':factura_id'   => $datos['factura_id'],
            ':comercial_id' => $datos['comercial_id'],
            ':estado_id'    => $datos['estado_id'] ?? 1,
            ':titulo'       => $datos['titulo'] ?? null,
            ':notas'        => $datos['notas'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function guardarResultados(int $estudioId, array $resultados): void
    {
        // Borrar resultados anteriores antes de insertar los nuevos
        $this->db->prepare('DELETE FROM resultados_comparativa WHERE estudio_id = :id')
                 ->execute([':id' => $estudioId]);

        $stmt = $this->db->prepare(
            'INSERT INTO resultados_comparativa
             (estudio_id, tarifa_id, coste_calculado, coste_actual, ahorro_estimado, ahorro_pct, comision_estimada, ranking, detalle_calculo)
             VALUES (:estudio_id, :tarifa_id, :coste_calc, :coste_actual, :ahorro, :ahorro_pct, :comision, :ranking, :detalle)'
        );

        $mejorAhorro = null;
        foreach ($resultados as $r) {
            $stmt->execute([
                ':estudio_id'  => $estudioId,
                ':tarifa_id'   => $r['tarifa_id'],
                ':coste_calc'  => $r['coste_calculado'],
                ':coste_actual'=> $r['coste_actual'],
                ':ahorro'      => $r['ahorro_estimado'],
                ':ahorro_pct'  => $r['ahorro_pct'],
                ':comision'    => $r['comision_estimada'],
                ':ranking'     => $r['ranking'],
                ':detalle'     => json_encode($r['detalle_calculo']),
            ]);
            if ($mejorAhorro === null || $r['ahorro_estimado'] > $mejorAhorro) {
                $mejorAhorro = $r['ahorro_estimado'];
            }
        }

        // Actualizar ahorro en el estudio
        if ($mejorAhorro !== null) {
            $this->db->prepare('UPDATE estudios SET ahorro_anual_estimado = :ahorro WHERE id = :id')
                     ->execute([':ahorro' => $mejorAhorro, ':id' => $estudioId]);
        }
    }

    public function getResultados(int $estudioId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, t.nombre_oferta, c.nombre AS comercializadora, c.logo_path
             FROM resultados_comparativa r
             JOIN tarifas_oferta t ON t.id = r.tarifa_id
             JOIN comercializadoras c ON c.id = t.comercializadora_id
             WHERE r.estudio_id = :id
             ORDER BY r.ranking ASC'
        );
        $stmt->execute([':id' => $estudioId]);
        return $stmt->fetchAll();
    }

    public function cambiarEstado(int $estudioId, int $estadoId): bool
    {
        $stmt = $this->db->prepare('UPDATE estudios SET estado_id = :estado WHERE id = :id');
        return $stmt->execute([':estado' => $estadoId, ':id' => $estudioId]);
    }
}
