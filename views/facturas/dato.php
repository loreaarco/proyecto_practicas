<?php
// 1. Incluimos los archivos necesarios respetando tu estructura de carpetas

use App\Repositories\TarifasRepository;

require_once '../../app/Services/LlamaAnalyst.php';
require_once '../../app/Repositories/TarifasRepository.php'; // Asegúrate de que esta ruta existe

// 2. Usamos los Repositorios en lugar de llamadas directas a la DB
$tarifasRepo = new TarifasRepository();
$llama = new \App\Services\LlamaAnalyst();

// Datos de ejemplo (estos deberían venir de la factura seleccionada)
$datosFactura = [
    'cups' => 'ES00...',
    'consumo_p1' => 450,
    'precio_actual' => 0.18
];

// 3. Obtenemos las ofertas usando el método del repositorio
// Si no tienes este método, usa: $ofertas = $tarifasRepo->listarTodos();
$ofertas = $tarifasRepo->listarVigentes(); 

// 4. Ejecutamos la lógica de Llama 3.1
$analisis = $llama->obtenerEstudioAhorro($datosFactura, $ofertas);
?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h3 class="h5 mb-0">Análisis de IA (Llama 3.1)</h3>
    </div>
    <div class="card-body">
        <p><strong>Ahorro Anual:</strong> <span class="text-success"><?php echo $analisis['ahorro_anual']; ?>€</span></p>
        <div class="alert alert-info">
            <?php echo $analisis['argumento_comercial']; ?>
        </div>
    </div>
</div>