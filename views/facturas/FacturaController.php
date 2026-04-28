// ... (vienen todos tus métodos anteriores: index, store, extraerDatos, etc.)

    /**
     * Muestra el detalle de la factura y los datos de la IA
     */
    public function show(int $id): void 
    {
        Auth::requireAuth();
        $factura = $this->getFacturaOr404($id);
        
        // Decodificar el JSON guardado en la base de datos. 
        // Si es null o está vacío, creamos un array vacío por defecto.
        $datosJson = json_decode($factura['datos_json'] ?? '', true) ?: [];

        $success = Session::getFlash('success');
        $error = Session::getFlash('error');

        // IMPORTANTE: Pasar 'datosJson' en el compact
        $this->render('facturas/show', compact('factura', 'datosJson', 'success', 'error'));
    }

    // El último método de la clase suele ser el helper
    private function getFacturaOr404(int $id): array
    {
        $factura = $this->facturaRepo->buscarPorId($id);
        if (!$factura) {
            $this->redirect('/404');
            exit;
        }
        return $factura;
    }

} 