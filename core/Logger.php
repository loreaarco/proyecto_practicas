<?php

/**
 * Logger - Sistema de logs en fichero y opcionalmente en BD
 *
 * Niveles: debug < info < warning < error < critical
 */
class Logger
{
    private static array $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3, 'critical' => 4];

    public static function debug(string $context, string $message, array $data = []): void
    {
        self::write('debug', $context, $message, $data);
    }

    public static function info(string $context, string $message, array $data = []): void
    {
        self::write('info', $context, $message, $data);
    }

    public static function warning(string $context, string $message, array $data = []): void
    {
        self::write('warning', $context, $message, $data);
    }

    public static function error(string $context, string $message, array $data = []): void
    {
        self::write('error', $context, $message, $data);
    }

    public static function critical(string $context, string $message, array $data = []): void
    {
        self::write('critical', $context, $message, $data);
    }

    private static function write(string $level, string $context, string $message, array $data): void
    {
        $configLevel = env('LOG_LEVEL', 'debug');
        if (self::$levels[$level] < self::$levels[$configLevel]) {
            return; // Nivel insuficiente, no registrar
        }

        $timestamp = date('Y-m-d H:i:s');
        $dataStr   = empty($data) ? '' : ' | ' . json_encode($data, JSON_UNESCAPED_UNICODE);
        $line      = "[{$timestamp}] [{$level}] [{$context}] {$message}{$dataStr}" . PHP_EOL;

        // Log a fichero (siempre)
        $logPath = env('LOG_PATH', '../storage/logs');
        $logFile = rtrim($logPath, '/') . '/' . date('Y-m-d') . '.log';

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

        // Log a BD (solo warning o superior, y si la BD ya está disponible)
        if (self::$levels[$level] >= self::$levels['warning']) {
            self::writeToDB($level, $context, $message, $data);
        }
    }

    private static function writeToDB(string $level, string $context, string $message, array $data): void
    {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare(
                'INSERT INTO logs_sistema (usuario_id, nivel, contexto, mensaje, datos_extra, ip)
                 VALUES (:uid, :nivel, :ctx, :msg, :datos, :ip)'
            );
            $stmt->execute([
                ':uid'   => $_SESSION['usuario_id'] ?? null,
                ':nivel' => $level,
                ':ctx'   => $context,
                ':msg'   => $message,
                ':datos' => empty($data) ? null : json_encode($data),
                ':ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Throwable $e) {
            // Si falla el log en BD, al menos está en fichero
        }
    }
}
