<?php

class SeguimientoController extends Controller
{
    private SeguimientoRepository $repo;
    private ClienteRepository     $clienteRepo;

    public function __construct()
    {
        $this->repo        = new SeguimientoRepository();
        $this->clienteRepo = new ClienteRepository();
    }

    public function index(int $clienteId): void
    {
        Auth::requireAuth();
        $cliente      = $this->getClienteOr404($clienteId);
        $seguimientos = $this->repo->listarPorCliente($clienteId);
        $this->render('seguimiento/index', compact('cliente', 'seguimientos'));
    }

    public function store(int $clienteId): void
    {
        Auth::requireAuth();
        $this->getClienteOr404($clienteId);

        $errors = $this->validateRequired(['descripcion' => 'Descripción']);
        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect("/clientes/{$clienteId}");
        }

        $this->repo->crear([
            'cliente_id'     => $clienteId,
            'estudio_id'     => $this->input('estudio_id') ?: null,
            'usuario_id'     => Auth::id(),
            'tipo'           => $this->input('tipo', 'nota'),
            'descripcion'    => $this->input('descripcion'),
            'proxima_accion' => $this->input('proxima_accion'),
            'fecha_proxima'  => $this->input('fecha_proxima') ?: null,
        ]);

        Session::flash('success', 'Seguimiento registrado.');
        $this->redirect("/clientes/{$clienteId}");
    }

    private function getClienteOr404(int $clienteId): array
    {
        $cliente = $this->clienteRepo->buscarPorId($clienteId);
        if (!$cliente) {
            http_response_code(404); include BASE_PATH . '/views/errors/404.php'; exit;
        }
        if (!Auth::hasRole('admin') && $cliente['comercial_id'] !== Auth::id()) {
            http_response_code(403); include BASE_PATH . '/views/errors/403.php'; exit;
        }
        return $cliente;
    }
}
