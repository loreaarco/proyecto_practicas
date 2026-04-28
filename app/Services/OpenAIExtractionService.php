<?php

/**
 * OpenAIExtractionService - Extracción de datos de factura usando la API de OpenAI
 *
 * Estrategia por tipo de fichero:
 *  - Imagen (JPG, PNG, WEBP, TIFF) → Vision API con base64 (eficiente, ~1-2K tokens)
 *  - PDF con ImageMagick disponible → convierte página 1 a PNG → Vision API
 *  - PDF sin ImageMagick            → extrae texto del PDF en PHP puro → prompt de texto
 *
 * La IA solo se usa para extraer datos. La comparación se hace sin IA.
 */
class OpenAIExtractionService
{
    private string $apiKey;
    private string $model;
    private int    $maxTokens;
    private int    $timeout;
    private int    $maxReintentos = 3;

    // Límite de caracteres de texto PDF a enviar (evita exceder tokens)
    private const MAX_TEXTO_PDF = 6000;

    public function __construct()
    {
        $this->apiKey    = env('OPENAI_API_KEY');
        $this->model     = env('OPENAI_MODEL', 'gpt-4o');
        $this->maxTokens = (int) env('OPENAI_MAX_TOKENS', 2000);
        $this->timeout   = (int) env('OPENAI_TIMEOUT', 60);

        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'sk-xxx')) {
            throw new RuntimeException('API Key de OpenAI no configurada. Revisa el fichero .env');
        }
    }

    /**
     * Extrae los datos estructurados de una factura.
     *
     * @param  string $rutaAbsolutaFichero  Ruta al PDF o imagen en disco
     * @param  string $mimeType             MIME del fichero
     * @return array  Datos extraídos + metadatos de la llamada
     */
    public function extraer(string $rutaAbsolutaFichero, string $mimeType): array
    {
        if (!file_exists($rutaAbsolutaFichero)) {
            throw new RuntimeException("Fichero no encontrado: {$rutaAbsolutaFichero}");
        }

        Logger::info('OpenAI', 'Iniciando extracción', [
            'fichero' => basename($rutaAbsolutaFichero),
            'mime'    => $mimeType,
        ]);

        $esPdf  = ($mimeType === 'application/pdf');
        $fileId = null;

        if ($esPdf) {
            // Capturamos el file_id si se usó la Files API para poder borrarlo después
            $payload = $this->construirPayloadPdfConTracking($rutaAbsolutaFichero, $fileId);
        } else {
            $payload = $this->construirPayloadImagen($rutaAbsolutaFichero, $mimeType);
        }

        try {
            $respuesta = $this->llamarConReintentos($payload);
            $datos     = $this->parsearRespuesta($respuesta);
        } finally {
            // Limpiar el archivo de OpenAI si se subió uno
            if ($fileId !== null) {
                $this->eliminarArchivoOpenAI($fileId);
            }
        }

        Logger::info('OpenAI', 'Extracción completada', [
            'tokens' => $respuesta['usage']['total_tokens'] ?? 0,
            'modelo' => $this->model,
        ]);

        return $datos;
    }

    // ── Construcción de payloads ──────────────────────────────────

    /**
     * Payload para imágenes: Vision API con base64.
     * Eficiente — usa tokens de imagen, no de texto.
     */
    private function construirPayloadImagen(string $ruta, string $mimeType): array
    {
        $base64 = base64_encode(file_get_contents($ruta));

        return $this->buildChatPayload([
            ['type' => 'text', 'text' => $this->buildPrompt()],
            [
                'type'      => 'image_url',
                'image_url' => [
                    'url'    => "data:{$mimeType};base64,{$base64}",
                    'detail' => 'high',
                ],
            ],
        ]);
    }

    /**
     * Wrapper que llama a construirPayloadPdf y captura el file_id si se subió uno.
     */
    private function construirPayloadPdfConTracking(string $ruta, ?string &$fileId): array
    {
        return $this->construirPayloadPdf($ruta, $fileId);
    }

    /**
     * Payload para PDFs.
     *
     * Estrategia 1: pdftotext (Poppler) → texto plano
     * Estrategia 2: ImageMagick → PNG → Vision API
     * Estrategia 3: GhostScript → PNG → Vision API
     * Estrategia 4: PHP puro → texto (solo si parece legible)
     * Estrategia 5: Files API de OpenAI → PDF nativo (sin dependencias de servidor)
     */
    private function construirPayloadPdf(string $ruta, ?string &$fileId = null): array
    {
        // ── Intento 1: pdftotext (Poppler, muy común en Linux) ────
        $texto = $this->extraerConPdftotext($ruta);
        if (strlen(trim($texto)) >= 100 && $this->esTextoLegible($texto)) {
            Logger::info('OpenAI', 'PDF extraído con pdftotext', ['chars' => strlen($texto)]);
            return $this->payloadDesdeTexto($texto);
        }

        // ── Intento 2: ImageMagick → Vision API ──────────────────
        $imgTmp = $this->convertirPdfAImagenConImageMagick($ruta);
        if ($imgTmp !== null) {
            Logger::info('OpenAI', 'PDF convertido a imagen con ImageMagick');
            $payload = $this->construirPayloadImagen($imgTmp, 'image/png');
            @unlink($imgTmp);
            return $payload;
        }

        // ── Intento 3: GhostScript → PNG → Vision API ────────────
        $imgTmp = $this->convertirPdfAImagenConGhostScript($ruta);
        if ($imgTmp !== null) {
            Logger::info('OpenAI', 'PDF convertido a imagen con GhostScript');
            $payload = $this->construirPayloadImagen($imgTmp, 'image/png');
            @unlink($imgTmp);
            return $payload;
        }

        // ── Intento 4: PHP puro (solo si el texto parece legible) ─
        $texto = $this->extraerTextoPdf($ruta);
        if (strlen(trim($texto)) >= 100 && $this->esTextoLegible($texto)) {
            Logger::info('OpenAI', 'PDF extraído con PHP puro', ['chars' => strlen($texto)]);
            return $this->payloadDesdeTexto($texto);
        }

        Logger::info('OpenAI', 'Usando Files API de OpenAI para PDF nativo');

        // ── Intento 5: Files API → OpenAI parsea el PDF nativo ───
        $fileId = $this->subirPdfAOpenAI($ruta);
        return $this->construirPayloadPdfNativo($fileId);

    }

    private function payloadDesdeTexto(string $texto): array
    {
        if (strlen($texto) > self::MAX_TEXTO_PDF) {
            $texto = substr($texto, 0, self::MAX_TEXTO_PDF);
        }
        return $this->buildChatPayload([
            [
                'type' => 'text',
                'text' => $this->buildPrompt() . "\n\n--- TEXTO DE LA FACTURA ---\n" . $texto,
            ],
        ]);
    }

    /**
     * Extrae texto usando el comando pdftotext (Poppler).
     * Disponible en la mayoría de servidores Linux de hosting.
     */
    private function extraerConPdftotext(string $ruta): string
    {
        // Verificar disponibilidad
        exec('which pdftotext 2>/dev/null', $out, $code);
        if ($code !== 0) return '';

        $salida = tempnam(sys_get_temp_dir(), 'oscisa_txt_');
        $cmd    = sprintf('pdftotext -layout %s %s 2>/dev/null', escapeshellarg($ruta), escapeshellarg($salida));
        exec($cmd, $out, $code);

        $texto = '';
        if ($code === 0 && file_exists($salida)) {
            $texto = file_get_contents($salida);
        }
        @unlink($salida);

        return $this->limpiarTexto($texto);
    }

    /**
     * Intenta convertir la primera página del PDF a PNG usando ImageMagick.
     * Devuelve la ruta del PNG temporal, o null si ImageMagick no está disponible.
     */
    private function convertirPdfAImagenConImageMagick(string $rutaPdf): ?string
    {
        // Verificar que convert está disponible
        exec('which convert 2>/dev/null', $out, $code);
        if ($code !== 0) {
            exec('where convert 2>NUL', $out, $code); // Windows
            if ($code !== 0) return null;
        }

        $rutaSalida = sys_get_temp_dir() . '/oscisa_pdf_' . uniqid() . '.png';
        $cmd        = sprintf(
            'convert -density 150 -quality 85 %s[0] %s 2>/dev/null',
            escapeshellarg($rutaPdf),
            escapeshellarg($rutaSalida)
        );

        exec($cmd, $out, $code);

        if ($code === 0 && file_exists($rutaSalida) && filesize($rutaSalida) > 0) {
            return $rutaSalida;
        }

        @unlink($rutaSalida);
        return null;
    }

    /**
     * Intenta convertir la primera página del PDF a PNG usando GhostScript.
     */
    private function convertirPdfAImagenConGhostScript(string $rutaPdf): ?string
    {
        exec('which gs 2>/dev/null', $out, $code);
        if ($code !== 0) return null;

        $rutaSalida = sys_get_temp_dir() . '/oscisa_gs_' . uniqid() . '.png';
        $cmd = sprintf(
            'gs -dNOPAUSE -dBATCH -dFirstPage=1 -dLastPage=1 -sDEVICE=pnggray -r150 -sOutputFile=%s %s 2>/dev/null',
            escapeshellarg($rutaSalida),
            escapeshellarg($rutaPdf)
        );
        exec($cmd, $out, $code);

        if ($code === 0 && file_exists($rutaSalida) && filesize($rutaSalida) > 0) {
            return $rutaSalida;
        }

        @unlink($rutaSalida);
        return null;
    }

    /**
     * Sube el PDF a la Files API de OpenAI y devuelve el file_id.
     * El archivo se borra de OpenAI tras usarlo.
     */
    private function subirPdfAOpenAI(string $ruta): string
    {
        $tamano = filesize($ruta);
        // Límite conservador: 20 MB
        if ($tamano > 20 * 1024 * 1024) {
            throw new RuntimeException(
                'El PDF es demasiado grande para procesarlo sin herramientas de servidor. ' .
                'Sube la factura como JPG o PNG.'
            );
        }

        $ch = curl_init('https://api.openai.com/v1/files');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'purpose' => 'user_data',
                'file'    => new CURLFile($ruta, 'application/pdf', basename($ruta)),
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("Error cURL al subir PDF a OpenAI: {$error}");
        }

        $decoded = json_decode($raw, true);
        if ($status !== 200) {
            $msg = $decoded['error']['message'] ?? "HTTP {$status}";
            throw new RuntimeException("Error al subir PDF a OpenAI Files API: {$msg}");
        }

        Logger::info('OpenAI', 'PDF subido a Files API', [
            'file_id' => $decoded['id'],
            'bytes'   => $tamano,
        ]);

        return $decoded['id'];
    }

    /**
     * Elimina un archivo de la Files API de OpenAI (limpieza tras uso).
     */
    private function eliminarArchivoOpenAI(string $fileId): void
    {
        $ch = curl_init("https://api.openai.com/v1/files/{$fileId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_TIMEOUT        => 15,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Construye el payload usando un file_id de la Files API de OpenAI.
     */
    private function construirPayloadPdfNativo(string $fileId): array
    {
        return $this->buildChatPayload([
            ['type' => 'text', 'text' => $this->buildPrompt()],
            ['type' => 'file', 'file' => ['file_id' => $fileId]],
        ]);
    }

    /**
     * Detecta si el texto extraído es legible (no basura de codificación de fuente).
     * Un PDF con fuentes custom produce caracteres de 1 byte separados por espacios.
     */
    private function esTextoLegible(string $texto): bool
    {
        $palabras = preg_split('/\s+/', trim($texto), -1, PREG_SPLIT_NO_EMPTY);
        if (count($palabras) < 10) return false;

        $longTotal = array_sum(array_map('strlen', $palabras));
        $avgLen    = $longTotal / count($palabras);

        // Si la longitud media de palabra es < 2.5, es casi seguro basura
        return $avgLen >= 2.5;
    }

    /**
     * Extrae texto de un PDF en PHP puro sin dependencias externas.
     * Maneja texto literal (texto)Tj y hex <4869>Tj.
     */
    private function extraerTextoPdf(string $ruta): string
    {
        $contenido = @file_get_contents($ruta);
        if (!$contenido) return '';

        $texto = '';

        // ── Streams comprimidos (FlateDecode) ─────────────────────
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $contenido, $streams)) {
            foreach ($streams[1] as $stream) {
                $dec = @gzuncompress($stream);
                if ($dec === false) {
                    $dec = @gzinflate(substr($stream, 2, -4));
                }
                if ($dec !== false) {
                    $texto .= $this->extraerTextoDePdfStream($dec) . ' ';
                }
            }
        }

        // ── Texto sin comprimir ───────────────────────────────────
        $texto .= $this->extraerTextoDePdfStream($contenido);

        return $this->limpiarTexto($texto);
    }

    private function extraerTextoDePdfStream(string $stream): string
    {
        $texto = '';

        // Strings literales: (texto) Tj  y  [(texto)] TJ
        if (preg_match_all('/\(([^)]{1,400})\)\s*Tj/i', $stream, $m)) {
            $texto .= implode(' ', $m[1]) . ' ';
        }
        if (preg_match_all('/\[([^\]]+)\]\s*TJ/i', $stream, $m)) {
            foreach ($m[1] as $bloque) {
                if (preg_match_all('/\(([^)]{1,400})\)/', $bloque, $sub)) {
                    $texto .= implode('', $sub[1]) . ' ';
                }
            }
        }

        // Strings hex: <4869...> Tj  (frecuente en facturas españolas)
        if (preg_match_all('/<([0-9a-fA-F]{2,})>\s*Tj/i', $stream, $m)) {
            foreach ($m[1] as $hex) {
                // Decodificar pares de bytes hex como texto
                $decoded = '';
                for ($i = 0; $i + 1 < strlen($hex); $i += 2) {
                    $byte = hexdec(substr($hex, $i, 2));
                    if ($byte >= 32 && $byte <= 126) {
                        $decoded .= chr($byte);
                    } elseif ($byte > 126) {
                        $decoded .= chr($byte); // caracteres latinos extendidos
                    }
                }
                if (strlen($decoded) > 0) {
                    $texto .= $decoded . ' ';
                }
            }
        }

        // Strings hex en arrays: [<hex>...] TJ
        if (preg_match_all('/\[([^\]]+)\]\s*TJ/i', $stream, $m)) {
            foreach ($m[1] as $bloque) {
                if (preg_match_all('/<([0-9a-fA-F]{2,})>/', $bloque, $hexSub)) {
                    foreach ($hexSub[1] as $hex) {
                        $decoded = '';
                        for ($i = 0; $i + 1 < strlen($hex); $i += 2) {
                            $byte = hexdec(substr($hex, $i, 2));
                            if ($byte >= 32) $decoded .= chr($byte);
                        }
                        $texto .= $decoded;
                    }
                    $texto .= ' ';
                }
            }
        }

        return $texto;
    }

    private function limpiarTexto(string $texto): string
    {
        // Eliminar null bytes y caracteres de control
        $texto = str_replace("\0", '', $texto);
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);

        // Forzar UTF-8 válido (elimina secuencias inválidas)
        $texto = mb_convert_encoding($texto, 'UTF-8', 'UTF-8');

        // Mantener caracteres imprimibles, latinos y UTF-8
        $texto = preg_replace('/[^\x20-\x7E\xA0-\xFF]/u', ' ', $texto);
        $texto = preg_replace('/\s{2,}/', ' ', $texto);

        return trim($texto);
    }

    // ── Prompts ───────────────────────────────────────────────────

    private function buildChatPayload(array $userContent): array
    {
        return [
            'model'       => $this->model,
            'max_tokens'  => $this->maxTokens,
            'temperature' => 0,
            'messages'    => [
                ['role' => 'system', 'content' => $this->buildSystemPrompt()],
                ['role' => 'user',   'content' => $userContent],
            ],
        ];
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Eres un asistente especializado en extraer datos de facturas de energía (electricidad, gas o dual) del mercado español.
Tu única función es analizar la factura que se te proporciona y devolver un JSON estructurado con los datos solicitados.

Normas estrictas:
1. Responde SOLO con el JSON. Sin explicaciones, sin texto adicional, sin markdown.
2. Si un dato no aparece en la factura, devuelve null para ese campo.
3. Los importes siempre en euros (número decimal, sin símbolo €).
4. Las fechas en formato YYYY-MM-DD.
5. Los kW y kWh como números decimales.
6. Si aparecen varios periodos de consumo, rellena los campos P1 a P6 en orden.
PROMPT;
    }

    private function buildPrompt(): string
    {
        return <<<PROMPT
Extrae todos los datos relevantes de esta factura de energía y devuelve EXCLUSIVAMENTE el siguiente JSON:

{
  "titular_nombre": "nombre completo del titular o empresa",
  "titular_cif_nif": "CIF o NIF del titular",
  "direccion_suministro": "dirección del punto de suministro",
  "cups": "código CUPS del suministro",
  "comercializadora": "nombre de la comercializadora emisora de la factura",
  "tipo_suministro": "electricidad | gas | dual",
  "tarifa_acceso": "2.0TD | 3.0TD | 6.1TD | otro código de tarifa",
  "potencia_p1_kw": número_decimal_o_null,
  "potencia_p2_kw": número_decimal_o_null,
  "potencia_p3_kw": número_decimal_o_null,
  "potencia_p4_kw": número_decimal_o_null,
  "potencia_p5_kw": número_decimal_o_null,
  "potencia_p6_kw": número_decimal_o_null,
  "consumo_p1_kwh": número_decimal_o_null,
  "consumo_p2_kwh": número_decimal_o_null,
  "consumo_p3_kwh": número_decimal_o_null,
  "consumo_p4_kwh": número_decimal_o_null,
  "consumo_p5_kwh": número_decimal_o_null,
  "consumo_p6_kwh": número_decimal_o_null,
  "consumo_total_kwh": número_decimal_o_null,
  "periodo_inicio": "YYYY-MM-DD o null",
  "periodo_fin": "YYYY-MM-DD o null",
  "dias_facturados": número_entero_o_null,
  "importe_potencia": número_decimal_o_null,
  "importe_energia": número_decimal_o_null,
  "importe_impuestos": número_decimal_o_null,
  "importe_total": número_decimal_o_null,
  "consumo_gas_kwh": número_decimal_o_null,
  "importe_gas": número_decimal_o_null,
  "datos_extra": {}
}

En "datos_extra" incluye cualquier dato adicional relevante que no encaje en los campos anteriores.
PROMPT;
    }

    // ── Llamada HTTP con reintentos ───────────────────────────────

    private function llamarConReintentos(array $payload): array
    {
        $intento     = 0;
        $ultimoError = null;

        while ($intento < $this->maxReintentos) {
            $intento++;
            try {
                $respuesta = $this->llamarAPI($payload);
                if ($this->esRespuestaValida($respuesta)) {
                    return $respuesta;
                }
                $ultimoError = 'Respuesta incompleta o sin contenido válido';
                Logger::warning('OpenAI', "Intento {$intento}: respuesta inválida");

            } catch (RuntimeException $e) {
                $ultimoError = $e->getMessage();
                Logger::warning('OpenAI', "Intento {$intento} fallido", ['error' => $ultimoError]);

                // Rate limit: esperar antes de reintentar
                if (str_contains($ultimoError, '429') || str_contains($ultimoError, 'rate_limit')) {
                    sleep(min(2 ** $intento, 30));
                } elseif (str_contains($ultimoError, 'too large') || str_contains($ultimoError, 'tokens')) {
                    // Error de tokens: no tiene sentido reintentar igual
                    throw $e;
                }
            }
        }

        Logger::error('OpenAI', 'Extracción fallida tras todos los reintentos', ['error' => $ultimoError]);
        throw new RuntimeException("Error en la extracción con OpenAI: {$ultimoError}");
    }

    private function llamarAPI(array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            // Último recurso: limpiar recursivamente el payload de caracteres problemáticos
            $payload = $this->sanitizarPayload($payload);
            $json    = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($json === false) {
            throw new RuntimeException('Error al codificar el payload JSON: ' . json_last_error_msg());
        }

        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("Error cURL: {$error}");
        }

        $decoded = json_decode($raw, true);

        if ($status !== 200) {
            $msg = $decoded['error']['message'] ?? "HTTP {$status}";
            throw new RuntimeException("Error API OpenAI: {$msg}");
        }

        return $decoded;
    }

    private function esRespuestaValida(array $respuesta): bool
    {
        return isset($respuesta['choices'][0]['message']['content'])
            && !empty(trim($respuesta['choices'][0]['message']['content']));
    }

    // ── Parseo y validación ───────────────────────────────────────

    private function parsearRespuesta(array $respuesta): array
    {
        $contenido = trim($respuesta['choices'][0]['message']['content']);

        // Limpiar bloques markdown que el modelo añada por error
        $contenido = preg_replace('/^```(?:json)?\s*/m', '', $contenido);
        $contenido = preg_replace('/\s*```$/m', '', $contenido);
        $contenido = trim($contenido);

        $datos = json_decode($contenido, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('OpenAI', 'JSON inválido en respuesta', ['raw' => substr($contenido, 0, 500)]);
            throw new RuntimeException('La IA devolvió un JSON inválido. Se registró el error para revisión.');
        }

        $datos = $this->normalizarDatos($datos);

        $datos['tokens_usados'] = $respuesta['usage']['total_tokens'] ?? null;
        $datos['modelo_ia']     = $this->model;

        if (isset($datos['datos_extra']) && is_array($datos['datos_extra'])) {
            $datos['datos_extra'] = json_encode($datos['datos_extra'], JSON_UNESCAPED_UNICODE);
        }

        return $datos;
    }

    private function normalizarDatos(array $datos): array
    {
        $camposDecimales = [
            'potencia_p1_kw','potencia_p2_kw','potencia_p3_kw',
            'potencia_p4_kw','potencia_p5_kw','potencia_p6_kw',
            'consumo_p1_kwh','consumo_p2_kwh','consumo_p3_kwh',
            'consumo_p4_kwh','consumo_p5_kwh','consumo_p6_kwh',
            'consumo_total_kwh',
            'importe_potencia','importe_energia','importe_impuestos','importe_total',
            'consumo_gas_kwh','importe_gas',
        ];

        foreach ($camposDecimales as $campo) {
            if (isset($datos[$campo])) {
                $datos[$campo] = is_numeric($datos[$campo]) ? (float) $datos[$campo] : null;
            }
        }

        if (isset($datos['dias_facturados'])) {
            $datos['dias_facturados'] = is_numeric($datos['dias_facturados'])
                ? (int) $datos['dias_facturados'] : null;
        }

        foreach (['periodo_inicio', 'periodo_fin'] as $campo) {
            if (!empty($datos[$campo])) {
                $dt = \DateTime::createFromFormat('Y-m-d', $datos[$campo]);
                $datos[$campo] = ($dt && $dt->format('Y-m-d') === $datos[$campo])
                    ? $datos[$campo] : null;
            }
        }

        return $datos;
    }

    /**
     * Limpia recursivamente un array eliminando caracteres que rompen json_encode.
     */
    private function sanitizarPayload(mixed $value): mixed
    {
        if (is_string($value)) {
            // Eliminar bytes nulos y de control
            $value = str_replace("\0", '', $value);
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
            // Forzar UTF-8 válido con sustitución de caracteres inválidos
            $value = htmlspecialchars_decode(
                htmlspecialchars($value, ENT_SUBSTITUTE, 'UTF-8'),
                ENT_QUOTES
            );
            return $value;
        }
        if (is_array($value)) {
            return array_map([$this, 'sanitizarPayload'], $value);
        }
        return $value;
    }
}
