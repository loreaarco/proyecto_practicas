<?php

class AdminController extends Controller
{
    public function index(): void
    {
        Auth::requireRole('admin');
        $db = Database::getInstance();

        $stats = [
            'total_clientes'  => (int) $db->query('SELECT COUNT(*) FROM clientes WHERE activo = 1')->fetchColumn(),
            'total_estudios'  => (int) $db->query('SELECT COUNT(*) FROM estudios')->fetchColumn(),
            'total_usuarios'  => (int) $db->query('SELECT COUNT(*) FROM usuarios WHERE activo = 1')->fetchColumn(),
            'total_tarifas'   => (int) $db->query('SELECT COUNT(*) FROM tarifas_oferta WHERE activa = 1')->fetchColumn(),
        ];

        // Estudios recientes
        $stats['estudios_recientes'] = $db->query(
            'SELECT es.*, ec.nombre AS estado_nombre, ec.color_hex,
                    c.nombre AS cliente_nombre, u.nombre AS comercial_nombre
             FROM estudios es
             JOIN estados_comerciales ec ON ec.id = es.estado_id
             JOIN clientes c ON c.id = es.cliente_id
             JOIN usuarios u ON u.id = es.comercial_id
             ORDER BY es.created_at DESC LIMIT 15'
        )->fetchAll();

        $this->render('admin/index', compact('stats'));
    }

    public function clientes(): void
    {
        Auth::requireRole('admin');
        $db = Database::getInstance();

        $clientes = $db->query(
            'SELECT c.*, ec.nombre AS estado_nombre, ec.color_hex, u.nombre AS comercial_nombre
             FROM clientes c
             JOIN estados_comerciales ec ON ec.id = c.estado_id
             JOIN usuarios u ON u.id = c.comercial_id
             WHERE c.activo = 1
             ORDER BY c.created_at DESC'
        )->fetchAll();

        $this->render('admin/clientes', compact('clientes'));
    }

    public function estudios(): void
    {
        Auth::requireRole('admin');
        $db = Database::getInstance();

        $estados = $db->query('SELECT id, nombre FROM estados_comerciales WHERE activo=1 ORDER BY orden')->fetchAll();
        $filtroEstado = $this->query('estado_id');
        $filtroComercial = $this->query('comercial_id');

        $where  = ['1=1'];
        $params = [];
        if ($filtroEstado) {
            $where[] = 'es.estado_id = :estado';
            $params[':estado'] = $filtroEstado;
        }
        if ($filtroComercial) {
            $where[] = 'es.comercial_id = :comercial';
            $params[':comercial'] = $filtroComercial;
        }

        $sql = 'SELECT es.*, ec.nombre AS estado_nombre, ec.color_hex,
                       c.nombre AS cliente_nombre, u.nombre AS comercial_nombre,
                       f.nombre_original AS factura_nombre
                FROM estudios es
                JOIN estados_comerciales ec ON ec.id = es.estado_id
                JOIN clientes c ON c.id = es.cliente_id
                JOIN usuarios u ON u.id = es.comercial_id
                JOIN facturas f ON f.id = es.factura_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY es.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $estudios = $stmt->fetchAll();

        $comerciales = $db->query('SELECT id, nombre FROM usuarios WHERE activo=1 ORDER BY nombre')->fetchAll();

        $this->render('admin/estudios', compact('estudios', 'estados', 'comerciales', 'filtroEstado', 'filtroComercial'));
    }

    public function tarifas(): void
    {
        Auth::requireRole('admin');
        $db = Database::getInstance();

        $tarifas = $db->query(
            'SELECT t.*, c.nombre AS comercializadora_nombre
             FROM tarifas_oferta t
             JOIN comercializadoras c ON c.id = t.comercializadora_id
             ORDER BY c.nombre, t.tarifa_acceso'
        )->fetchAll();

        $comercializadoras = $db->query('SELECT id, nombre FROM comercializadoras WHERE activa=1 ORDER BY nombre')->fetchAll();

        $this->render('admin/tarifas', compact('tarifas', 'comercializadoras'));
    }

    public function nuevaTarifa(): void
    {
        Auth::requireRole('admin');
        $db = Database::getInstance();

        $campos = [
            'comercializadora_id',
            'nombre_oferta',
            'tarifa_acceso',
            'vigente_desde',
        ];
        foreach ($campos as $c) {
            if (empty($_POST[$c])) {
                Session::flash('error', 'Faltan campos obligatorios.');
                $this->redirect('/admin/tarifas');
            }
        }

        $stmt = $db->prepare(
            'INSERT INTO tarifas_oferta
             (comercializadora_id, nombre_oferta, tipo_suministro, tarifa_acceso,
              precio_energia_p1, precio_energia_p2, precio_energia_p3,
              precio_potencia_p1, precio_potencia_p2,
              descuento_pct, comision_tipo, comision_valor, comision_periodicidad,
              vigente_desde, activa)
             VALUES
             (:cid, :nombre, :tipo, :acceso,
              :ep1, :ep2, :ep3,
              :pp1, :pp2,
              :desc, :ctipo, :cval, :cper,
              :desde, 1)'
        );
        $stmt->execute([
            ':cid'   => (int) $_POST['comercializadora_id'],
            ':nombre' => htmlspecialchars(trim($_POST['nombre_oferta']), ENT_QUOTES, 'UTF-8'),
            ':tipo'  => $_POST['tipo_suministro'] ?? 'electricidad',
            ':acceso' => htmlspecialchars(trim($_POST['tarifa_acceso']), ENT_QUOTES, 'UTF-8'),
            ':ep1'   => (float) ($_POST['precio_energia_p1'] ?? 0),
            ':ep2'   => (float) ($_POST['precio_energia_p2'] ?? 0),
            ':ep3'   => (float) ($_POST['precio_energia_p3'] ?? 0),
            ':pp1'   => (float) ($_POST['precio_potencia_p1'] ?? 0),
            ':pp2'   => (float) ($_POST['precio_potencia_p2'] ?? 0),
            ':desc'  => (float) ($_POST['descuento_pct'] ?? 0),
            ':ctipo' => in_array($_POST['comision_tipo'] ?? '', ['fija', 'porcentaje']) ? $_POST['comision_tipo'] : 'fija',
            ':cval'  => (float) ($_POST['comision_valor'] ?? 0),
            ':cper'  => in_array($_POST['comision_periodicidad'] ?? '', ['unica', 'anual', 'mensual']) ? $_POST['comision_periodicidad'] : 'anual',
            ':desde' => $_POST['vigente_desde'],
        ]);

        Logger::info('Admin', 'Tarifa creada', ['id' => $db->lastInsertId()]);
        Session::flash('success', 'Tarifa añadida correctamente.');
        $this->redirect('/admin/tarifas');
    }

    public function usuarios(): void
    {
        Auth::requireRole('admin');
        $db = Database::getInstance();

        $usuarios = $db->query(
            'SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u JOIN roles r ON r.id = u.rol_id
             ORDER BY u.nombre'
        )->fetchAll();

        $this->render('admin/usuarios', compact('usuarios'));
    }

    public function comercializadoras(): void
    {
        Auth::requireRole('admin');
        $comercializadoras = Database::getInstance()->query('SELECT * FROM comercializadoras ORDER BY nombre')->fetchAll();
        $this->render('admin/comercializadoras', compact('comercializadoras'));
    }

    public function nuevoUsuario(): void
    {
        Auth::requireRole('admin');
        $db = Database::getInstance();

        $nombre   = htmlspecialchars(trim($_POST['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $rolId    = (int) ($_POST['rol_id'] ?? 2);

        if (!$nombre || !$email || strlen($password) < 8) {
            Session::flash('error', 'Nombre, email y contraseña (mín. 8 caracteres) son obligatorios.');
            $this->redirect('/admin/usuarios');
        }

        // Verificar email único
        $existe = $db->prepare('SELECT id FROM usuarios WHERE email = :email');
        $existe->execute([':email' => $email]);
        if ($existe->fetch()) {
            Session::flash('error', 'Ya existe un usuario con ese email.');
            $this->redirect('/admin/usuarios');
        }

        $stmt = $db->prepare(
            'INSERT INTO usuarios (rol_id, nombre, apellidos, email, password_hash)
             VALUES (:rol, :nombre, :apellidos, :email, :hash)'
        );
        $stmt->execute([
            ':rol'      => $rolId,
            ':nombre'   => $nombre,
            ':apellidos' => htmlspecialchars(trim($_POST['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?: null,
            ':email'    => $email,
            ':hash'     => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        Logger::info('Admin', 'Usuario creado', ['email' => $email]);
        Session::flash('success', "Usuario {$nombre} creado correctamente.");
        $this->redirect('/admin/usuarios');
    }
}
