<?php

class EstudioController extends Controller
{
    private EstudioRepository  $estudioRepo;
    private ClienteRepository  $clienteRepo;
    private FacturaRepository  $facturaRepo;

    public function __construct()
    {
        $this->estudioRepo = new EstudioRepository();
        $this->clienteRepo = new ClienteRepository();
        $this->facturaRepo = new FacturaRepository();
    }

    public function index(int $clienteId): void
    {
        Auth::requireAuth();
        $cliente  = $this->getClienteOr404($clienteId);
        $estudios = $this->estudioRepo->listarPorCliente($clienteId);
        $this->render('estudios/index', compact('cliente', 'estudios'));
    }

    /**
     * Crea un nuevo estudio vinculado a una factura del cliente.
     */
    public function store(int $clienteId): void
    {
        Auth::requireAuth();
        $cliente   = $this->getClienteOr404($clienteId);
        $facturaId = (int) $this->input('factura_id');

        if (!$facturaId) {
            Session::flash('error', 'Debes seleccionar una factura para crear el estudio.');
            $this->redirect("/clientes/{$clienteId}");
        }

        $factura = $this->facturaRepo->buscarPorId($facturaId);
        if (!$factura || $factura['cliente_id'] !== $clienteId) {
            Session::flash('error', 'Factura no válida.');
            $this->redirect("/clientes/{$clienteId}");
        }

        if ($factura['estado_extraccion'] !== 'completada') {
            Session::flash('error', 'La factura debe tener los datos extraídos antes de crear un estudio.');
            $this->redirect("/clientes/{$clienteId}");
        }

        $estudioId = $this->estudioRepo->crear([
            'cliente_id'   => $clienteId,
            'factura_id'   => $facturaId,
            'comercial_id' => Auth::id(),
            'titulo'       => $this->input('titulo') ?? "Estudio " . date('d/m/Y'),
            'notas'        => $this->input('notas'),
        ]);

        Session::flash('success', 'Estudio creado. Ahora puedes lanzar la comparativa.');
        $this->redirect("/estudios/{$estudioId}");
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $estudio    = $this->getEstudioOr404($id);
        $resultados = $this->estudioRepo->getResultados($id);
        $estados    = $this->getEstados();
        $success    = Session::getFlash('success');
        $error      = Session::getFlash('error');

        $this->render('estudios/show', compact('estudio', 'resultados', 'estados', 'success', 'error'));
    }

    /**
     * Lanza el motor de comparación sin IA.
     */
    public function calcular(int $id): void
    {
        Auth::requireAuth();
        $estudio = $this->getEstudioOr404($id);

        $datos = $this->facturaRepo->getDatosExtraidos($estudio['factura_id']);
        if (!$datos) {
            Session::flash('error', 'No hay datos extraídos para calcular la comparativa.');
            $this->redirect("/estudios/{$id}");
        }

        try {
            $comparador  = new ComparadorService();
            $resultados  = $comparador->comparar($datos);

            if (empty($resultados)) {
                Session::flash('error', 'No se encontraron tarifas compatibles para comparar.');
                $this->redirect("/estudios/{$id}");
            }

            $this->estudioRepo->guardarResultados($id, $resultados);

            Logger::info('Estudio', 'Comparativa calculada', [
                'estudio_id' => $id,
                'ofertas'    => count($resultados),
            ]);

            Session::flash('success', 'Comparativa calculada con ' . count($resultados) . ' ofertas.');

        } catch (RuntimeException $e) {
            Logger::error('EstudioController', 'Error en comparativa', ['estudio_id' => $id, 'error' => $e->getMessage()]);
            Session::flash('error', 'Error al calcular: ' . $e->getMessage());
        }

        $this->redirect("/estudios/{$id}");
    }

    public function cambiarEstado(int $id): void
    {
        Auth::requireAuth();
        $this->getEstudioOr404($id);
        $estadoId = (int) $this->input('estado_id');
        $this->estudioRepo->cambiarEstado($id, $estadoId);
        $this->redirect("/estudios/{$id}");
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function getEstudioOr404(int $id): array
    {
        $estudio = $this->estudioRepo->buscarPorId($id);
        if (!$estudio) {
            http_response_code(404);
            include BASE_PATH . '/views/errors/404.php';
            exit;
        }
        if (!Auth::hasRole('admin') && $estudio['comercial_id'] !== Auth::id()) {
            http_response_code(403);
            include BASE_PATH . '/views/errors/403.php';
            exit;
        }
        return $estudio;
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

    private function getEstados(): array
    {
        $stmt = Database::getInstance()->query(
            'SELECT id, nombre, color_hex FROM estados_comerciales WHERE activo = 1 ORDER BY orden'
        );
        return $stmt->fetchAll();
    }
    // En app/Controllers/Controller.php (o donde esté tu clase base)
    protected function render(string $path, array $data = []): void
    {
        // Extrae el array para que ['cliente' => $obj] se convierta en $cliente
        extract($data);

        $fullPath = __DIR__ . '/../../views/' . $path . '.php';

        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            die("La vista $path no existe.");
        }
    }
}
