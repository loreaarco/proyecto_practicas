<?php

/**
 * ComparadorService - Motor de comparación de facturas (SIN IA)
 *
 * Toda la lógica es programación clásica: fórmulas, reglas y tablas.
 *
 * Arquitectura del comparador:
 *  1. Recibe los datos extraídos de la factura del cliente
 *  2. Obtiene todas las tarifas activas compatibles
 *  3. Para cada tarifa, calcula el coste que habría pagado el cliente
 *  4. Calcula el ahorro respecto a lo que pagó realmente
 *  5. Calcula la comisión que recibiría OSCISA
 *  6. Devuelve ranking ordenado por ahorro
 *
 * Para modificar fórmulas: editar calcularCosteTarifa()
 * Para modificar criterios: editar filtrarTarifasCompatibles()
 * Para modificar comisiones: editar calcularComision()
 */
class ComparadorService
{
    private PDO $db;

    // Impuesto sobre la electricidad (IEE): 5,11269632% sobre base energía
    private const IEE_PORCENTAJE = 0.0511269632;

    // IVA eléctrico en vigor (puede cambiar, debe ser configurable)
    private const IVA_ELECTRICIDAD = 0.10;  // 10%

    // Alquiler de equipos (estimado mensual medio si no aparece en factura)
    private const ALQUILER_EQUIPOS_DIA = 0.026919;  // €/día

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Punto de entrada principal del comparador.
     *
     * @param  array $datosFactura  Fila de datos_extraidos_factura
     * @return array Lista de resultados ordenados por ahorro descendente
     */
    public function comparar(array $datosFactura): array
    {
        $this->validarDatosMinimos($datosFactura);

        $costActual = (float) $datosFactura['importe_total'];
        $tarifas    = $this->filtrarTarifasCompatibles($datosFactura);

        if (empty($tarifas)) {
            Logger::warning('Comparador', 'No se encontraron tarifas compatibles', [
                'tarifa_acceso' => $datosFactura['tarifa_acceso'],
                'tipo'          => $datosFactura['tipo_suministro'],
            ]);
            return [];
        }

        $resultados = [];
        foreach ($tarifas as $tarifa) {
            $resultado = $this->evaluarTarifa($tarifa, $datosFactura, $costActual);
            if ($resultado !== null) {
                $resultados[] = $resultado;
            }
        }

        // Ordenar por ahorro estimado descendente (mejor primero)
        usort($resultados, fn($a, $b) => $b['ahorro_estimado'] <=> $a['ahorro_estimado']);

        // Asignar ranking
        foreach ($resultados as $pos => &$r) {
            $r['ranking'] = $pos + 1;
        }

        return $resultados;
    }

    // ── Filtrado de tarifas compatibles ───────────────────────────

    /**
     * Solo devuelve tarifas activas, vigentes y del mismo tipo de acceso.
     */
    private function filtrarTarifasCompatibles(array $datosFactura): array
    {
        $sql = 'SELECT t.*, c.nombre AS comercializadora_nombre, c.logo_path
                FROM tarifas_oferta t
                JOIN comercializadoras c ON c.id = t.comercializadora_id
                WHERE t.activa = 1
                  AND t.tipo_suministro = :tipo
                  AND t.vigente_desde <= CURDATE()
                  AND (t.vigente_hasta IS NULL OR t.vigente_hasta >= CURDATE())';

        $params = [':tipo' => $datosFactura['tipo_suministro'] ?? 'electricidad'];

        // Filtrar por tarifa de acceso si se conoce (p.ej. 2.0TD, 3.0TD)
        if (!empty($datosFactura['tarifa_acceso'])) {
            $sql .= ' AND t.tarifa_acceso = :tarifa_acceso';
            $params[':tarifa_acceso'] = $datosFactura['tarifa_acceso'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Evaluación de una tarifa ──────────────────────────────────

    private function evaluarTarifa(array $tarifa, array $datos, float $costActual): ?array
    {
        try {
            $desglose = $this->calcularCosteTarifa($tarifa, $datos);
            $costeCalculado = $desglose['total_con_impuestos'];

            $ahorro    = round($costActual - $costeCalculado, 2);
            $ahorroPct = $costActual > 0 ? round(($ahorro / $costActual) * 100, 2) : 0;

            $comision = $this->calcularComision($tarifa, $datos);

            return [
                'tarifa_id'            => $tarifa['id'],
                'tarifa_nombre'        => $tarifa['nombre_oferta'],
                'comercializadora_id'  => $tarifa['comercializadora_id'],
                'comercializadora'     => $tarifa['comercializadora_nombre'],
                'logo_path'            => $tarifa['logo_path'],
                'coste_calculado'      => round($costeCalculado, 2),
                'coste_actual'         => round($costActual, 2),
                'ahorro_estimado'      => $ahorro,
                'ahorro_pct'           => $ahorroPct,
                'comision_estimada'    => round($comision, 2),
                'detalle_calculo'      => $desglose,
                'ranking'              => 0,
            ];
        } catch (Throwable $e) {
            Logger::warning('Comparador', 'Error evaluando tarifa', [
                'tarifa_id' => $tarifa['id'],
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ── Fórmulas de cálculo ───────────────────────────────────────

    /**
     * Calcula el coste con una tarifa para el consumo real del cliente.
     *
     * Estructura de una factura eléctrica española (2.0TD / 3.0TD):
     *   Término de potencia  = Σ(potencia_pi × precio_pi × dias / 365)
     *   Término de energía   = Σ(consumo_pi × precio_pi)
     *   Alquiler equipos     = €/día × días
     *   Base imponible       = potencia + energía + alquiler
     *   IEE (5,11%)          = base × IEE_PORCENTAJE
     *   IVA (10%)            = (base + IEE) × IVA
     *   Total                = base + IEE + IVA
     */
    private function calcularCosteTarifa(array $tarifa, array $datos): array
    {
        $dias = (int) ($datos['dias_facturados'] ?? 30);

        // ── Término de potencia ──────────────────────────────────
        $costePotencia = 0.0;
        for ($p = 1; $p <= 6; $p++) {
            $potencia = (float) ($datos["potencia_p{$p}_kw"] ?? 0);
            $precio   = (float) ($tarifa["precio_potencia_p{$p}"] ?? 0);
            if ($potencia > 0 && $precio > 0) {
                // El precio de potencia es €/kW/año, dividimos por 365 y multiplicamos por días
                $costePotencia += $potencia * $precio * $dias / 365;
            }
        }

        // ── Término de energía ───────────────────────────────────
        $costeEnergia = 0.0;
        for ($p = 1; $p <= 6; $p++) {
            $consumo = (float) ($datos["consumo_p{$p}_kwh"] ?? 0);
            $precio  = (float) ($tarifa["precio_energia_p{$p}"] ?? 0);
            if ($consumo > 0 && $precio > 0) {
                $costeEnergia += $consumo * $precio;
            }
        }

        // Aplicar descuento de la tarifa si tiene uno
        if ($tarifa['descuento_pct'] > 0) {
            $descuento     = ($costePotencia + $costeEnergia) * ($tarifa['descuento_pct'] / 100);
            $costePotencia = $costePotencia * (1 - $tarifa['descuento_pct'] / 100);
            $costeEnergia  = $costeEnergia  * (1 - $tarifa['descuento_pct'] / 100);
        } else {
            $descuento = 0;
        }

        // ── Alquiler de equipos ──────────────────────────────────
        $costeAlquiler = self::ALQUILER_EQUIPOS_DIA * $dias;

        // ── Impuestos ────────────────────────────────────────────
        $baseImponible = $costePotencia + $costeEnergia + $costeAlquiler;
        $iee           = $baseImponible * self::IEE_PORCENTAJE;
        $baseConIEE    = $baseImponible + $iee;
        $iva           = $baseConIEE * self::IVA_ELECTRICIDAD;
        $total         = $baseConIEE + $iva;

        return [
            'dias_facturados'   => $dias,
            'coste_potencia'    => round($costePotencia, 4),
            'coste_energia'     => round($costeEnergia, 4),
            'descuento'         => round($descuento, 4),
            'coste_alquiler'    => round($costeAlquiler, 4),
            'base_imponible'    => round($baseImponible, 4),
            'iee'               => round($iee, 4),
            'iva'               => round($iva, 4),
            'total_con_impuestos' => round($total, 2),
        ];
    }

    /**
     * Calcula la comisión estimada para OSCISA según el modelo de la tarifa.
     *
     * Tipos:
     *  - 'fija':       valor fijo (€) según periodicidad
     *  - 'porcentaje': % sobre el coste anual estimado
     */
    private function calcularComision(array $tarifa, array $datos): float
    {
        if ($tarifa['comision_tipo'] === 'porcentaje') {
            // Estimamos coste anual desde el periodo facturado
            $dias       = max((int) ($datos['dias_facturados'] ?? 30), 1);
            $costeAnual = ((float) $datos['importe_total']) * (365 / $dias);
            return $costeAnual * ($tarifa['comision_valor'] / 100);
        }

        // Comisión fija: devolver el valor tal cual (es anual, mensual o única)
        return (float) $tarifa['comision_valor'];
    }

    // ── Validación ────────────────────────────────────────────────

    private function validarDatosMinimos(array $datos): void
    {
        if (empty($datos['importe_total'])) {
            throw new RuntimeException('Para comparar se necesita el importe total de la factura.');
        }
        if (empty($datos['tipo_suministro'])) {
            throw new RuntimeException('Para comparar se necesita el tipo de suministro (electricidad/gas).');
        }
    }
}
