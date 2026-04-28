<?php

class ClienteController extends Controller
{
    private ClienteRepository $repo;

    public function __construct()
    {
        $this->repo = new ClienteRepository();
    }

    public function index(): void
    {
        Auth::requireAuth();

        $filtros = [
            'buscar'       => $this->query('buscar'),
            'estado_id'    => $this->query('estado_id'),
            'comercial_id' => Auth::hasRole('admin') ? $this->query('comercial_id') : Auth::id(),
        ];

        $pagina    = max(1, (int) $this->query('pagina', 1));
        $total     = $this->repo->contar($filtros);
        $clientes  = $this->repo->listar($filtros, $pagina, 20);
        $estados   = $this->getEstados();
        $totalPags = (int) ceil($total / 20);

        $this->render('clientes/index', compact('clientes', 'filtros', 'pagina', 'total', 'totalPags', 'estados'));
    }

    public function create(): void
    {
        Auth::requireAuth();
        $estados    = $this->getEstados();
        $comerciales = Auth::hasRole('admin') ? $this->getComerciales() : [];
        $errors     = Session::getFlash('errors', []);
        $old        = Session::getFlash('old', []);

        $this->render('clientes/create', compact('estados', 'comerciales', 'errors', 'old'));
    }

    public function store(): void
    {
        Auth::requireAuth();

        $errors = $this->validateRequired([
            'nombre'  => 'Nombre',
        ]);

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', $_POST);
            $this->redirect('/clientes/nuevo');
        }

        $datos = [
            'comercial_id'  => Auth::hasRole('admin')
                                ? (int) $this->input('comercial_id', Auth::id())
                                : Auth::id(),
            'estado_id'     => (int) $this->input('estado_id', 1),
            'nombre'        => $this->input('nombre'),
            'apellidos'     => $this->input('apellidos'),
            'empresa'       => $this->input('empresa'),
            'cif_nif'       => $this->input('cif_nif'),
            'email'         => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'telefono'      => $this->input('telefono'),
            'telefono2'     => $this->input('telefono2'),
            'direccion'     => $this->input('direccion'),
            'poblacion'     => $this->input('poblacion'),
            'provincia'     => $this->input('provincia'),
            'codigo_postal' => $this->input('codigo_postal'),
            'notas'         => $this->input('notas'),
        ];

        $id = $this->repo->crear($datos);
        Logger::info('Cliente', 'Cliente creado', ['id' => $id, 'nombre' => $datos['nombre']]);
        Session::flash('success', 'Cliente creado correctamente.');
        $this->redirect("/clientes/{$id}");
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $cliente = $this->getClienteOr404($id);

        $facturaRepo = new FacturaRepository();
        $facturas    = $facturaRepo->listarPorCliente($id);

        $seguimientoRepo = new SeguimientoRepository();
        $seguimientos    = $seguimientoRepo->listarPorCliente($id);

        $estudiosRepo = new EstudioRepository();
        $estudios     = $estudiosRepo->listarPorCliente($id);

        $estados = $this->getEstados();
        $success = Session::getFlash('success');
        $error   = Session::getFlash('error');

        $this->render('clientes/show', compact(
            'cliente', 'facturas', 'seguimientos', 'estudios', 'estados', 'success', 'error'
        ));
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $cliente     = $this->getClienteOr404($id);
        $estados     = $this->getEstados();
        $comerciales = Auth::hasRole('admin') ? $this->getComerciales() : [];
        $errors      = Session::getFlash('errors', []);

        $this->render('clientes/edit', compact('cliente', 'estados', 'comerciales', 'errors'));
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $this->getClienteOr404($id);

        $errors = $this->validateRequired(['nombre' => 'Nombre']);
        if (!empty($errors)) {
            Session::flash('errors', $errors);
            $this->redirect("/clientes/{$id}/editar");
        }

        $datos = [
            'comercial_id'  => Auth::hasRole('admin')
                                ? (int) $this->input('comercial_id')
                                : Auth::id(),
            'estado_id'     => (int) $this->input('estado_id', 1),
            'nombre'        => $this->input('nombre'),
            'apellidos'     => $this->input('apellidos'),
            'empresa'       => $this->input('empresa'),
            'cif_nif'       => $this->input('cif_nif'),
            'email'         => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'telefono'      => $this->input('telefono'),
            'telefono2'     => $this->input('telefono2'),
            'direccion'     => $this->input('direccion'),
            'poblacion'     => $this->input('poblacion'),
            'provincia'     => $this->input('provincia'),
            'codigo_postal' => $this->input('codigo_postal'),
            'notas'         => $this->input('notas'),
        ];

        $this->repo->actualizar($id, $datos);
        Session::flash('success', 'Cliente actualizado correctamente.');
        $this->redirect("/clientes/{$id}");
    }

    public function cambiarEstado(int $id): void
    {
        Auth::requireAuth();
        $this->getClienteOr404($id);
        $estadoId = (int) $this->input('estado_id');

        if ($estadoId < 1) {
            $this->redirect("/clientes/{$id}");
        }

        $this->repo->cambiarEstado($id, $estadoId);
        $this->redirect("/clientes/{$id}");
    }

    public function destroy(int $id): void
    {
        Auth::requireRole('admin');
        $this->getClienteOr404($id);
        $this->repo->eliminar($id);
        Logger::info('Cliente', 'Cliente eliminado (lógico)', ['id' => $id]);
        Session::flash('success', 'Cliente eliminado.');
        $this->redirect('/clientes');
    }

    // ── Helpers privados ─────────────────────────────────────────

    private function getClienteOr404(int $id): array
    {
        $cliente = $this->repo->buscarPorId($id);
        if (!$cliente) {
            http_response_code(404);
            include BASE_PATH . '/views/errors/404.php';
            exit;
        }
        // Un comercial solo puede ver sus propios clientes
        if (!Auth::hasRole('admin') && $cliente['comercial_id'] !== Auth::id()) {
            http_response_code(403);
            include BASE_PATH . '/views/errors/403.php';
            exit;
        }
        return $cliente;
    }

    private function getEstados(): array
    {
        $stmt = Database::getInstance()->query(
            'SELECT id, nombre, color_hex FROM estados_comerciales WHERE activo = 1 ORDER BY orden'
        );
        return $stmt->fetchAll();
    }

    private function getComerciales(): array
    {
        $stmt = Database::getInstance()->query(
            "SELECT u.id, u.nombre FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE u.activo = 1 AND r.nombre IN ('comercial', 'supervisor', 'admin')
             ORDER BY u.nombre"
        );
        return $stmt->fetchAll();
    }
}
