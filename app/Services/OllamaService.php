<?php
class OllamaService {
    private $apiUrl = "http://localhost:11434/api/generate";
    private $model = "llama3.1:8b";

    public function analizarFactura($textoFactura) {
        $prompt = "Analiza el siguiente texto de una factura eléctrica y genera el JSON de datos: " . $textoFactura;

        $payload = [
            "model" => $this->model,
            "prompt" => $prompt,
            "system" => "Eres un analizador de facturas. Devuelve solo JSON. No hables, solo genera el objeto.",
            "stream" => false,
            "format" => "json" // Forzamos a Llama 3.1 a que use el modo JSON
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $decoded = json_decode($response, true);
        
        // Retornamos solo el campo 'response' que contiene nuestro JSON energético
        return json_decode($decoded['response'], true);
    }
}