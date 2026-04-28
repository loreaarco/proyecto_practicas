<?php

/**
 * Database - Singleton de conexión PDO
 *
 * Gestiona una única conexión reutilizable a la base de datos remota.
 * Todos los accesos a datos pasan por esta clase.
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Devuelve la instancia PDO, creándola si no existe.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    private static function createConnection(): PDO
    {
        $host    = env('DB_HOST', 'localhost');
        $port    = env('DB_PORT', '3306');
        $name    = env('DB_NAME');
        $user    = env('DB_USER');
        $pass    = env('DB_PASS');
        $charset = env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            // Timeout de conexión (útil para BD remota)
            PDO::ATTR_TIMEOUT            => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            return $pdo;
        } catch (PDOException $e) {
            // No exponemos credenciales ni detalles técnicos al exterior
            Logger::critical('Database', 'Error de conexión a la base de datos', [
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('No se pudo conectar con la base de datos.');
        }
    }
}
