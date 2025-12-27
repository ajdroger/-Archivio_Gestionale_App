<?php

namespace FratellanzaMilitare\InfrastrutturaIT\Persistence;

use PDO;
use PDOException;

class DatabaseConnection
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            // Strict MySQL/MariaDB Connection
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $db = $_ENV['DB_DATABASE'] ?? 'fratellanza_db';
            $user = $_ENV['DB_USERNAME'] ?? 'root';
            $pass = $_ENV['DB_PASSWORD'] ?? '';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

            try {
                self::$connection = new PDO($dsn, $user, $pass);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                // Ensure strict mode if needed, or other MySQL specific settings
            } catch (PDOException $e) {
                throw new PDOException("Errore di connessione al Database (MySQL): " . $e->getMessage(), (int) $e->getCode(), $e);
            }
        }
        return self::$connection;
    }
}
