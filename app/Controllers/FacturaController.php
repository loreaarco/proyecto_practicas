<?php

use Smalot\PdfParser\Parser;

class FacturaController extends Controller
{
    private FacturaRepository    $facturaRepo;
    private ClienteRepository    $clienteRepo;

    public function __construct()
    {
        $this->facturaRepo = new FacturaRepository();
        $this->clienteRepo = new ClienteRepository();
    }

    public function index(int $clienteId): void
    {
        Auth::requireAuth();
        $cliente  = $this->getClienteOr404($clienteId);
        $facturas = $this->facturaRepo->listarPorCliente($clienteId);
        $this->render('facturas/index', compact('cliente', 'facturas'));
    }

    public function create(int $clienteId): void
    {
        Auth::requireAuth();
        $cliente = $this->getClienteOr404($clienteId);
        $error   = Session::getFlash('error');
        $this->render('facturas/create', compact('cliente', 'error'));
    }

    public function store(int $clienteId): void
    {
        Auth::requireAuth();
        $cliente = $this->getClienteOr404($clienteId);

        if (empty($_FILES['factura']) || $_FILES['factura']['error'] === UPLOAD_ERR_NO_FILE) {
            Session::flash('error', 'Debes seleccionar un archivo de factura.');
            $this->redirect("/clientes/{$clienteId}/facturas/subir");
        }

        try {
            $uploadService = new FacturaUploadService();
            $fileData      = $uploadService->procesar($_FILES['factura'], $clienteId);

            $facturaId = $this->facturaRepo->crear([
                'cliente_id'  => $clienteId,
                'subida_por'  => Auth::id(),
                ...$fileData,
            ]);

            Session::flash('success', 'Factura subida correctamente. Puedes lanzar la extracción de datos.');
            $this->redirect("/facturas/{$facturaId}");

        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect("/clientes/{$clienteId}/facturas/subir");
        }
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $factura = $this->getFacturaOr404($id);
        $datos   = $this->facturaRepo->getDatosExtraidos($id);
        $success = Session::getFlash('success');
        $error   = Session::getFlash('error');
        $this->render('facturas/show', compact('factura', 'datos', 'success', 'error'));
    }

    /**
     * Muestra el formulario de revisión/edición manual de los datos extraídos.
     */
    public function datos(int $id): void
    {
        Auth::requireAuth();
        $factura = $this->getFacturaOr404($id);
        $datos   = $this->facturaRepo->getDatosExtraidos($id) ?? [];
        $errors  = Session::getFlash('errors', []);
        $this->render('facturas/datos', compact('factura', 'datos', 'errors'));
    }

    /**
     * Guarda los datos editados manualmente.
     */
    public function guardarDatos(int $id): void
    {
        Auth::requireAuth();
        $factura = $this->getFacturaOr404($id);

        $camposNumericos = [
            'potencia_p1_kw', 'potencia_p2_kw', 'potencia_p3_kw',
            'consumo_p1_kwh', 'consumo_p2_kwh', 'consumo_p3_kwh',
            'consumo_total_kwh', 'importe_potencia', 'importe_energia',
            'importe_impuestos', 'importe_total', 'dias_facturados',
        ];

        $datos = [];
        foreach ($_POST as $k => $v) {
            if (in_array($k, $camposNumericos)) {
                $datos[$k] = is_numeric($v) ? (float) $v : null;
            } else {
                $datos[$k] = htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8') ?: null;
            }
        }

        $this->facturaRepo->guardarDatosExtraidos($id, $datos);
        $this->facturaRepo->marcarRevisadoManual($id, Auth::id());

        Session::flash('success', 'Datos guardados y marcados como revisados manualmente.');
        $this->redirect("/facturas/{$id}");
    }

    /**
     * Lanza la extracción automática con OpenAI.
     */
    public function extraerDatos(int $id): void
    {
        Auth::requireAuth();
        $factura = $this->getFacturaOr404($id);
        $intentos = (int) $factura['intentos_extraccion'];

        $this->facturaRepo->actualizarEstadoExtraccion($id, 'procesando', $intentos + 1);

        try {
            // --- SOLUCIÓN TEMPORAL SIN COMPOSER ---
            // Tenemos que incluir los archivos manualmente. 
            // Nota: Esto es un poco rudimentario. Lo ideal es usar el autoload.php que genera composer.
            
            $rutaBase = BASE_PATH . '/vendor/smalot/pdfparser/src/Smalot/PdfParser/';

            if (!file_exists($rutaBase . 'Parser.php')) {
                throw new Exception("La librería PdfParser no se encuentra en /vendor/");
            }

            
            define('BASE_PATH', dirname(__DIR__));

            require_once BASE_PATH . '/vendor/autoload.php';

            require_once BASE_PATH . '/config/app.php';
            // ---------------------------------------

            $uploadService = new FacturaUploadService(); 
            $rutaAbsoluta  = $uploadService->rutaAbsoluta($factura['ruta_almacen']);
            
            // Usamos el nombre completo de la clase con su namespace
            $parser = new \Smalot\PdfParser\Parser(); 
            $pdf    = $parser->parseFile($rutaAbsoluta);
            $texto  = $pdf->getText();

            $llm = new LlmAnalisisService();
            $datosIA = $llm->analizarFactura($texto);

            $this->facturaRepo->finalizarExtraccionIA($id, $datosIA, $intentos + 1);

            Session::flash('success', '¡Análisis con el Módulo LLM completado!');

        } catch (Exception $e) {
            $this->facturaRepo->actualizarEstadoExtraccion($id, 'error', $intentos + 1);
            Logger::error('FacturaController', 'Error', ['id' => $id, 'error' => $e->getMessage()]);
            Session::flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect("/facturas/{$id}");
    }

    /**
     * Sirve el archivo original de la factura con cabeceras correctas.
     */
    public function verArchivo(int $id): void
    {
        Auth::requireAuth();
        $factura = $this->getFacturaOr404($id);

        $uploadService = new FacturaUploadService();
        $rutaAbsoluta  = $uploadService->rutaAbsoluta($factura['ruta_almacen']);

        if (!file_exists($rutaAbsoluta)) {
            http_response_code(404);
            echo 'Archivo no encontrado.';
            exit;
        }

        header('Content-Type: ' . $factura['mime_type']);
        header('Content-Disposition: inline; filename="' . $factura['nombre_original'] . '"');
        header('Content-Length: ' . filesize($rutaAbsoluta));
        readfile($rutaAbsoluta);
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function getFacturaOr404(int $id): array
    {
        $factura = $this->facturaRepo->buscarPorId($id);
        if (!$factura) {
            http_response_code(404);
            include BASE_PATH . '/views/errors/404.php';
            exit;
        }
        // Verificar acceso al cliente propietario
        $this->getClienteOr404($factura['cliente_id']);
        return $factura;
    }

    private function getClienteOr404(int $clienteId): array
    {
        $cliente = $this->clienteRepo->buscarPorId($clienteId);
        if (!$cliente) {
            http_response_code(404);
            include BASE_PATH . '/views/errors/404.php';
            exit;
        }
        if (!Auth::hasRole('admin') && $cliente['comercial_id'] !== Auth::id()) {
            http_response_code(403);
            include BASE_PATH . '/views/errors/403.php';
            exit;
        }
        return $cliente;
    }
}
