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
            $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';

            try {
                if ($driver === 'mysql') {
                    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
                    $port = $_ENV['DB_PORT'] ?? '3306';
                    $db = $_ENV['DB_DATABASE'] ?? 'fratellanza_db';
                    $user = $_ENV['DB_USERNAME'] ?? 'root';
                    $pass = $_ENV['DB_PASSWORD'] ?? '';

                    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
                    self::$connection = new PDO($dsn, $user, $pass);
                    self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } else {
                    // Fallback to SQLite
                    $dbName = $_ENV['DB_PATH'] ?? 'database.sqlite';
                    $dbPath = __DIR__ . '/../../../' . $dbName;

                    self::$connection = new PDO("sqlite:" . $dbPath);
                    self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$connection->exec("PRAGMA foreign_keys = ON;");
                }
            } catch (PDOException $e) {
                // Log and re-throw
                throw new PDOException("Connessione DB ($driver) fallita: " . $e->getMessage(), (int) $e->getCode(), $e);
            }
        }
        return self::$connection;
    }
}
