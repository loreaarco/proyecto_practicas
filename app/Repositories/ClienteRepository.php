<?php

/**
 * ClienteRepository - Acceso a datos de la tabla `clientes`
 *
 * Solo contiene SQL. La lógica de negocio va en el servicio.
 */
class ClienteRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Listado paginado de clientes con filtros opcionales.
     */
    public function listar(array $filtros = [], int $pagina = 1, int $porPagina = 20): array
    {
        $where  = ['c.activo = 1'];
        $params = [];

        if (!empty($filtros['comercial_id'])) {
            $where[]  = 'c.comercial_id = :comercial_id';
            $params[':comercial_id'] = (int) $filtros['comercial_id'];
        }
        if (!empty($filtros['estado_id'])) {
            $where[]  = 'c.estado_id = :estado_id';
            $params[':estado_id'] = (int) $filtros['estado_id'];
        }
        if (!empty($filtros['buscar'])) {
            $where[]  = '(c.nombre LIKE :buscar OR c.empresa LIKE :buscar OR c.cif_nif LIKE :buscar OR c.email LIKE :buscar)';
            $params[':buscar'] = '%' . $filtros['buscar'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($pagina - 1) * $porPagina;

        $sql = "SELECT c.*, e.nombre AS estado_nombre, e.color_hex,
                       u.nombre AS comercial_nombre,
                       COUNT(f.id) AS total_facturas
                FROM clientes c
                JOIN estados_comerciales e ON e.id = c.estado_id
                JOIN usuarios u ON u.id = c.comercial_id
                LEFT JOIN facturas f ON f.cliente_id = c.id
                WHERE {$whereStr}
                GROUP BY c.id
                ORDER BY c.updated_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Total de clientes para paginación.
     */
    public function contar(array $filtros = []): int
    {
        $where  = ['c.activo = 1'];
        $params = [];

        if (!empty($filtros['comercial_id'])) {
            $where[]  = 'c.comercial_id = :comercial_id';
            $params[':comercial_id'] = (int) $filtros['comercial_id'];
        }
        if (!empty($filtros['estado_id'])) {
            $where[]  = 'c.estado_id = :estado_id';
            $params[':estado_id'] = (int) $filtros['estado_id'];
        }
        if (!empty($filtros['buscar'])) {
            $where[]  = '(c.nombre LIKE :buscar OR c.empresa LIKE :buscar OR c.cif_nif LIKE :buscar)';
            $params[':buscar'] = '%' . $filtros['buscar'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM clientes c WHERE {$whereStr}");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, e.nombre AS estado_nombre, e.color_hex,
                    u.nombre AS comercial_nombre, u.email AS comercial_email
             FROM clientes c
             JOIN estados_comerciales e ON e.id = c.estado_id
             JOIN usuarios u ON u.id = c.comercial_id
             WHERE c.id = :id AND c.activo = 1
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO clientes
             (comercial_id, estado_id, nombre, apellidos, empresa, cif_nif,
              email, telefono, telefono2, direccion, poblacion, provincia, codigo_postal, notas)
             VALUES
             (:comercial_id, :estado_id, :nombre, :apellidos, :empresa, :cif_nif,
              :email, :telefono, :telefono2, :direccion, :poblacion, :provincia, :codigo_postal, :notas)'
        );
        $stmt->execute($this->mapearDatos($datos));
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE clientes SET
             comercial_id = :comercial_id, estado_id = :estado_id,
             nombre = :nombre, apellidos = :apellidos, empresa = :empresa,
             cif_nif = :cif_nif, email = :email, telefono = :telefono,
             telefono2 = :telefono2, direccion = :direccion, poblacion = :poblacion,
             provincia = :provincia, codigo_postal = :codigo_postal, notas = :notas
             WHERE id = :id'
        );
        $datos = $this->mapearDatos($datos);
        $datos[':id'] = $id;
        return $stmt->execute($datos);
    }

    public function cambiarEstado(int $clienteId, int $estadoId): bool
    {
        $stmt = $this->db->prepare('UPDATE clientes SET estado_id = :estado WHERE id = :id');
        return $stmt->execute([':estado' => $estadoId, ':id' => $clienteId]);
    }

    public function eliminar(int $id): bool
    {
        // Borrado lógico
        $stmt = $this->db->prepare('UPDATE clientes SET activo = 0 WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    private function mapearDatos(array $d): array
    {
        return [
            ':comercial_id'  => $d['comercial_id']  ?? null,
            ':estado_id'     => $d['estado_id']      ?? 1,
            ':nombre'        => $d['nombre']         ?? '',
            ':apellidos'     => $d['apellidos']      ?? null,
            ':empresa'       => $d['empresa']        ?? null,
            ':cif_nif'       => $d['cif_nif']        ?? null,
            ':email'         => $d['email']          ?? null,
            ':telefono'      => $d['telefono']       ?? null,
            ':telefono2'     => $d['telefono2']      ?? null,
            ':direccion'     => $d['direccion']      ?? null,
            ':poblacion'     => $d['poblacion']      ?? null,
            ':provincia'     => $d['provincia']      ?? null,
            ':codigo_postal' => $d['codigo_postal']  ?? null,
            ':notas'         => $d['notas']          ?? null,
        ];
    }
}
