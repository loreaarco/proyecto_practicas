<?php

/**
 * FacturaUploadService - Gestión segura de subida de ficheros de factura
 *
 * Valida tipo, tamaño y nombre antes de mover al almacenamiento definitivo.
 * El fichero original NUNCA se ejecuta; se almacena con nombre aleatorio.
 */
class FacturaUploadService
{
    // Tipos MIME permitidos
    private const TIPOS_PERMITIDOS = [
        'application/pdf'  => 'pdf',
        'image/jpeg'       => 'jpg',
        'image/png'        => 'png',
        'image/webp'       => 'webp',
        'image/tiff'       => 'tif',
    ];

    private string $uploadPath;
    private int    $maxBytes;

    public function __construct()
    {
        $this->uploadPath = BASE_PATH . '/' . ltrim(env('UPLOAD_PATH', '../storage/facturas'), './');
        $this->maxBytes   = (int) env('UPLOAD_MAX_SIZE_MB', 10) * 1024 * 1024;
    }

    /**
     * Procesa $_FILES['factura'] y mueve el archivo al almacén.
     *
     * @return array  Datos listos para guardar en BD: nombre_fichero, ruta_almacen, mime_type, tamanio_bytes
     * @throws RuntimeException si la validación falla
     */
    public function procesar(array $file, int $clienteId): array
    {
        $this->validar($file);

        $mimeType  = $this->detectarMime($file['tmp_name']);
        $extension = self::TIPOS_PERMITIDOS[$mimeType];

        // Nombre único e impredecible
        $nombreFichero = sprintf('%s_%d_%s.%s',
            date('Ymd_His'),
            $clienteId,
            bin2hex(random_bytes(8)),
            $extension
        );

        // Subdirectorio por cliente para no saturar un solo directorio
        $subdirectorio = $this->uploadPath . '/' . $clienteId;
        if (!is_dir($subdirectorio)) {
            mkdir($subdirectorio, 0750, true);
        }

        $rutaCompleta = $subdirectorio . '/' . $nombreFichero;

        if (!move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            throw new RuntimeException('No se pudo mover el archivo. Verifica permisos del directorio.');
        }

        Logger::info('FacturaUpload', 'Factura subida', [
            'cliente_id' => $clienteId,
            'fichero'    => $nombreFichero,
            'mime'       => $mimeType,
            'bytes'      => $file['size'],
        ]);

        return [
            'nombre_original' => $file['name'],
            'nombre_fichero'  => $nombreFichero,
            'ruta_almacen'    => "facturas/{$clienteId}/{$nombreFichero}",
            'mime_type'       => $mimeType,
            'tamanio_bytes'   => $file['size'],
        ];
    }

    private function validar(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->mensajeErrorUpload($file['error']));
        }

        if ($file['size'] > $this->maxBytes) {
            throw new RuntimeException(
                'El archivo supera el tamaño máximo permitido (' . env('UPLOAD_MAX_SIZE_MB', 10) . ' MB).'
            );
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Error de seguridad: el archivo no es un upload válido.');
        }

        $mime = $this->detectarMime($file['tmp_name']);
        if (!isset(self::TIPOS_PERMITIDOS[$mime])) {
            throw new RuntimeException(
                'Tipo de archivo no permitido. Solo se aceptan PDF, JPEG, PNG, WEBP y TIFF.'
            );
        }
    }

    private function detectarMime(string $tmpPath): string
    {
        // Detectamos por contenido real, NO por extensión ni Content-Type del cliente
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($tmpPath);
    }

    private function mensajeErrorUpload(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo es demasiado grande.',
            UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta.',
            UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Error interno: directorio temporal no disponible.',
            UPLOAD_ERR_CANT_WRITE => 'Error interno: no se pudo escribir en disco.',
            default               => 'Error desconocido al subir el archivo.',
        };
    }

    /**
     * Devuelve la ruta absoluta a un fichero almacenado.
     * $rutaRelativa es el valor de la columna ruta_almacen, p.ej. "facturas/3/20250101_abc.pdf"
     */
    public function rutaAbsoluta(string $rutaRelativa): string
    {
        return BASE_PATH . '/storage/' . ltrim($rutaRelativa, '/');
    }
}
