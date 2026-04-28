<?php

class SeguimientoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listarPorCliente(int $clienteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, u.nombre AS usuario_nombre
             FROM seguimiento_comercial s
             JOIN usuarios u ON u.id = s.usuario_id
             WHERE s.cliente_id = :cid
             ORDER BY s.created_at DESC'
        );
        $stmt->execute([':cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO seguimiento_comercial (cliente_id, estudio_id, usuario_id, tipo, descripcion, proxima_accion, fecha_proxima)
             VALUES (:cliente_id, :estudio_id, :usuario_id, :tipo, :descripcion, :proxima_accion, :fecha_proxima)'
        );
        $stmt->execute([
            ':cliente_id'     => $datos['cliente_id'],
            ':estudio_id'     => $datos['estudio_id'] ?? null,
            ':usuario_id'     => $datos['usuario_id'],
            ':tipo'           => $datos['tipo'] ?? 'nota',
            ':descripcion'    => $datos['descripcion'],
            ':proxima_accion' => $datos['proxima_accion'] ?? null,
            ':fecha_proxima'  => $datos['fecha_proxima'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
