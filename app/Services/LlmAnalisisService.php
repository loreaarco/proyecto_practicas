<?php

/**
 * LlmAnalisisService - Módulo LLM para análisis y razonamiento de facturas
 *
 * Extrae datos técnicos de la factura, evalúa el catálogo de tarifas,
 * calcula el ahorro anual estimado y la comisión, y devuelve un JSON estructurado.
 */
class LlmAnalisisService
{
    private string $apiKey;
    private string $model;
    private PDO $db;
    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY', '');
        $this->model  = env('OPENAI_MODEL', 'gpt-4o');
        $this->db     = Database::getInstance();
    }
    /**
     * Analiza el texto de la factura y razona sobre las tarifas.
     */
    public function analizarFactura(string $textoFactura): array
    {
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'sk-xxx')) {
            throw new RuntimeException('API Key de OpenAI no configurada. Revisa el fichero .env');
        }
        $tarifas = $this->obtenerCatalogoTarifas();
        $prompt  = $this->construirPrompt($textoFactura, $tarifas);

        $payload = [
            'model'       => $this->model,
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.1,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'Eres un sistema de inteligencia artificial experto en el sector energético español. Tu tarea es extraer datos de una factura y compararlos con un catálogo de tarifas para encontrar la mejor oferta. Debes responder SIEMPRE con un único objeto JSON válido, sin texto adicional ni markdown.'
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 120, // Timeout más alto por el razonamiento
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("Error de conexión con OpenAI: {$error}");
        }

        $decoded = json_decode($raw, true);

        if ($status !== 200) {
            $msg = $decoded['error']['message'] ?? "Error HTTP {$status}";
            throw new RuntimeException("Error API OpenAI: {$msg}");
        }

        $contenido = $decoded['choices'][0]['message']['content'] ?? '{}';
        $datosJSON = json_decode($contenido, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('LlmAnalisisService', 'El modelo no devolvió un JSON válido', ['raw' => $contenido]);
            throw new RuntimeException('El modelo no devolvió un formato JSON válido.');
        }

        return $this->normalizarDatos($datosJSON);
    }

    private function obtenerCatalogoTarifas(): string
    {
        $sql = 'SELECT t.*, c.nombre AS comercializadora_nombre 
                FROM tarifas_oferta t
                JOIN comercializadoras c ON c.id = t.comercializadora_id
                WHERE t.activa = 1 AND t.vigente_desde <= CURDATE() 
                AND (t.vigente_hasta IS NULL OR t.vigente_hasta >= CURDATE())';

        $stmt = $this->db->query($sql);
        $tarifas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $jsonTarifas = [];
        foreach ($tarifas as $t) {
            $jsonTarifas[] = [
                'nombre_oferta' => $t['nombre_oferta'],
                'comercializadora' => $t['comercializadora_nombre'],
                'tipo_suministro' => $t['tipo_suministro'],
                'tarifa_acceso' => $t['tarifa_acceso'],
                'precios_energia' => [
                    'p1' => (float) $t['precio_energia_p1'],
                    'p2' => (float) $t['precio_energia_p2'],
                    'p3' => (float) $t['precio_energia_p3'],
                    'p4' => (float) $t['precio_energia_p4'],
                    'p5' => (float) $t['precio_energia_p5'],
                    'p6' => (float) $t['precio_energia_p6'],
                ],
                'precios_potencia_kwaño' => [
                    'p1' => (float) $t['precio_potencia_p1'],
                    'p2' => (float) $t['precio_potencia_p2'],
                    'p3' => (float) $t['precio_potencia_p3'],
                    'p4' => (float) $t['precio_potencia_p4'],
                    'p5' => (float) $t['precio_potencia_p5'],
                    'p6' => (float) $t['precio_potencia_p6'],
                ],
                'comision_tipo' => $t['comision_tipo'],
                'comision_valor' => (float) $t['comision_valor']
            ];
        }

        return json_encode($jsonTarifas, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function construirPrompt(string $textoFactura, string $catalogoTarifas): string
    {
        return <<<PROMPT
Analiza el siguiente texto de una factura de energía y el catálogo de tarifas disponibles.
Deberás extraer los datos de la factura, calcular el coste que tendría el cliente con las distintas tarifas aplicables (misma tarifa de acceso y tipo), identificar la mejor oferta que suponga el mayor ahorro, y listar OBLIGATORIAMENTE las siguientes 3 mejores alternativas del catálogo para realizar una comparativa completa, calculando ahorro y comisión para cada una.

Reglas para cálculos (estimación anual):
- Anualiza el consumo y la potencia según los días facturados (multiplica por 365 / dias_facturados).
- Calcula el coste de energía (consumo_pi * precio_energia_pi).
- Calcula el coste de potencia (potencia_pi * precio_potencia_pi_kwaño).
- Ahorro estimado = Coste actual anualizado - Coste nueva tarifa anualizado.
- Si la comision_tipo de la tarifa es 'fija', la comision_estimada es igual a comision_valor.
- Si la comision_tipo es 'porcentaje', la comision_estimada es (Ahorro estimado * (comision_valor / 100)).
- Genera una 'recomendacion' de 1 o 2 frases explicando por qué es mejor (ej: reduce el coste en valle, etc.).

CATÁLOGO DE TARIFAS (JSON):
{$catalogoTarifas}

TEXTO DE LA FACTURA:
{$textoFactura}

Devuelve EXCLUSIVAMENTE un JSON con la siguiente estructura exacta, sin texto adicional:
{
  "titular_nombre": "string",
  "titular_cif_nif": "string",
  "direccion_suministro": "string",
  "cups": "string",
  "comercializadora": "string",
  "tipo_suministro": "electricidad | gas | dual",
  "tarifa_acceso": "string",
  "consumo_total_kwh": número,
  "potencia_p1_kw": número o null,
  "potencia_p2_kw": número o null,
  "potencia_p3_kw": número o null,
  "potencia_p4_kw": número o null,
  "potencia_p5_kw": número o null,
  "potencia_p6_kw": número o null,
  "consumo_p1_kwh": número o null,
  "consumo_p2_kwh": número o null,
  "consumo_p3_kwh": número o null,
  "consumo_p4_kwh": número o null,
  "consumo_p5_kwh": número o null,
  "consumo_p6_kwh": número o null,
  "importe_potencia": número o null,
  "importe_energia": número o null,
  "importe_impuestos": número o null,
  "importe_total": número,
  "periodo_inicio": "YYYY-MM-DD",
  "periodo_fin": "YYYY-MM-DD",
  "dias_facturados": número,
  "mejor_oferta": "Nombre de la tarifa ganadora",
  "ahorro_estimado": número decimal,
  "recomendacion": "Texto recomendación",
  "comision_estimada": número decimal,
  "otras_opciones": [
    { "nombre_oferta": "string", "comercializadora": "string", "ahorro_estimado": número, "comision_estimada": número },
    { "nombre_oferta": "string", "comercializadora": "string", "ahorro_estimado": número, "comision_estimada": número },
    { "nombre_oferta": "string", "comercializadora": "string", "ahorro_estimado": número, "comision_estimada": número }
  ]
}
PROMPT;
    }

    /**
     * Mantiene una conversación interactiva sobre la factura.
     * 
     *  * Esta función envía el mensaje del usuario a la API de OpenAI junto con:
     * - Los datos de la factura previamente analizada
     * - El catálogo de tarifas energéticas disponibles
     * 
     * La IA responde como un consultor energético experto.
     */
    public function conversar(string $mensajeUsuario, array $datosFactura): string
    {
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'sk-xxx')) {
            throw new RuntimeException('API Key de OpenAI no configurada.');
        }

        $tarifas = $this->obtenerCatalogoTarifas();

        $payload = [
            'model'       => $this->model,
            'temperature' => 0.7,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'Eres un consultor energético experto. El usuario te hará preguntas sobre una factura específica que ya has analizado. Tienes acceso a los datos extraídos de la factura y al catálogo de tarifas disponibles para razonar tus respuestas. Sé conciso, profesional y ayuda al usuario a entender sus ahorros y opciones.'
                ],
                [
                    'role'    => 'system',
                    'content' => "DATOS DE LA FACTURA ANALIZADA:\n" . json_encode($datosFactura, JSON_UNESCAPED_UNICODE) .
                        "\n\nCATÁLOGO DE TARIFAS:\n" . $tarifas
                ],
                [
                    'role'    => 'user',
                    'content' => $mensajeUsuario
                ]
            ]
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200) {
            return "Lo siento, hubo un error al procesar tu consulta con la IA.";
        }

        $decoded = json_decode($raw, true);
        return $decoded['choices'][0]['message']['content'] ?? "No he podido generar una respuesta.";
    }

    private function normalizarDatos(array $datos): array
    {
        // Asegurar que los numéricos son floats
        $camposDecimales = [
            'consumo_total_kwh',
            'potencia_p1_kw',
            'potencia_p2_kw',
            'potencia_p3_kw',
            'potencia_p4_kw',
            'potencia_p5_kw',
            'potencia_p6_kw',
            'consumo_p1_kwh',
            'consumo_p2_kwh',
            'consumo_p3_kwh',
            'consumo_p4_kwh',
            'consumo_p5_kwh',
            'consumo_p6_kwh',
            'importe_potencia',
            'importe_energia',
            'importe_impuestos',
            'importe_total',
            'ahorro_estimado',
            'comision_estimada'
        ];

        foreach ($camposDecimales as $campo) {
            if (isset($datos[$campo])) {
                $datos[$campo] = is_numeric($datos[$campo]) ? round((float) $datos[$campo], 2) : null;
            } else {
                $datos[$campo] = null;
            }
        }

        if (isset($datos['dias_facturados'])) {
            $datos['dias_facturados'] = is_numeric($datos['dias_facturados']) ? (int) $datos['dias_facturados'] : null;
        }

        return $datos;
    }
}
