<?php
// Suponiendo que tienes un autoloader o incluyes tus clases
require_once '../../app/Services/LlamaAnalyst.php';

use App\Services\LlamaAnalyst;

$llama = new LlamaAnalyst();

// 1. Obtener datos (esto vendría de tu lógica de Base de Datos)
// Simulamos los datos que ya tienes en PostgreSQL tras el OCR
$facturaActual = [
    'periodos' => [
        'p1' => ['consumo' => 120, 'precio' => 0.19],
        'p2' => ['consumo' => 150, 'precio' => 0.15],
        'p3' => ['consumo' => 200, 'precio' => 0.11]
    ],
    'potencia' => 4.6,
    'total_mes' => 85.50
];

// 2. Obtener ofertas disponibles para comparar
$ofertas = [
    ['id' => 'DIGI_01', 'nombre' => 'Tarifa Eco Digiytal', 'precio_kwh' => 0.12],
    ['id' => 'OSCISA_FIX', 'nombre' => 'OSCISA Fijo Plus', 'precio_kwh' => 0.13]
];

// 3. LLAMADA AL LLM: Aquí Llama 3.1 ejecuta el razonamiento comercial
$resultado = $llama->obtenerEstudioAhorro($facturaActual, $ofertas);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Análisis de Factura Actual</h6>
                </div>
                <div class="card-body">
                    <p>CUPS: <strong>ES0021...</strong></p>
                    <p>Gasto mensual: <span class="text-danger"><?= $facturaActual['total_mes'] ?>€</span></p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Ahorro Estimado Anual (IA)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $resultado['ahorro_anual'] ?> € / año
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-magic fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6><i class="fas fa-robot"></i> Argumento sugerido para el cliente:</h6>
                    <div class="alert alert-light border">
                        <em>"<?= $resultado['argumento_comercial'] ?>"</em>
                    </div>
                    
                    <?php if(!empty($resultado['alertas'])): ?>
                        <div class="mt-3">
                            <?php foreach($resultado['alertas'] as $alerta): ?>
                                <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> <?= $alerta ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <a href="../seguimiento/update.php?id=123&estado=oferta_presentada" class="btn btn-primary">
            Presentar Oferta: <?= $resultado['mejor_oferta_id'] ?>
        </a>
    </div>
</div>