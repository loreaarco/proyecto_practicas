<?php

namespace App\Services;

class LlamaAnalyst {
    private $endpoint = "http://localhost:11434/api/chat"; // URL de Ollama o tu API de Llama 3.1
    private $model = "llama3.1:8b";

    /**
     * Envía los datos para razonar sobre ahorro y comparativa
     */
    public function obtenerEstudioAhorro($datosFactura, $tarifasDisponibles) {
        $prompt = $this->prepararPrompt($datosFactura, $tarifasDisponibles);

        $payload = [
            "model" => $this->model,
            "messages" => [
                [
                    "role" => "system", 
                    "content" => "Eres un experto en consultoría energética de OSCISA. Tu objetivo es analizar facturas y encontrar el máximo ahorro. Responde SIEMPRE en formato JSON."
                ],
                ["role" => "user", "content" => $prompt]
            ],
            "stream" => false,
            "format" => "json"
        ];

        return $this->ejecutarConsulta($payload);
    }

    private function prepararPrompt($factura, $ofertas) {
        return "
            DATOS DE LA FACTURA ACTUAL:
            " . json_encode($factura) . "

            CATÁLOGO DE OFERTAS DISPONIBLES:
            " . json_encode($ofertas) . "

            TAREAS:
            1. Compara la tarifa actual vs las ofertas.
            2. Calcula el ahorro mensual y anual estimado.
            3. Genera un 'Argumento Comercial' corto para el vendedor.
            4. Si hay penalización por reactiva o potencia mal ajustada, indícalo.

            FORMATO DE SALIDA ESPERADO (JSON):
            {
                'ahorro_anual': 0.00,
                'mejor_oferta_id': '',
                'analisis_tecnico': '',
                'argumento_comercial': '',
                'alertas': []
            }
        ";
    }

    private function ejecutarConsulta($payload) {
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['error' => 'No se pudo conectar con Llama 3.1'];

        $resDecoded = json_decode($response, true);
        return json_decode($resDecoded['message']['content'], true);
    }
}