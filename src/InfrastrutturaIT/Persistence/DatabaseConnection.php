<?php

namespace MCAG\InfrastrutturaIT\Persistence;

use PDO;
use PDOException;

/**
 * Gestore Singleton per la connessione al database MySQL tramite PDO.
 * 
 * Legge la configurazione dalle variabili d'ambiente (.env) e stabilisce
 * una connessione sicura e ottimizzata (UTF-8, ERRMODE_EXCEPTION).
 */
class DatabaseConnection
{
    private static ?PDO $connection = null;

    /**
     * Restituisce l'istanza singleton della connessione PDO.
     * 
     * Se la connessione non esiste, la crea. Se esiste, la riutilizza.
     * 
     * @return PDO Istanza di PDO connessa
     * @throws PDOException In caso di errore di connessione
     */
    /**
     * Force a reconnection to a specific Tenant Database.
     * Used by TenantMiddleware to switch context based on subdomain.
     *
     * @param string $tenantDbName The name of the tenant's database
     * @return void
     */
    public static function connectToTenant(string $tenantDbName): void
    {
        // Close existing connection
        self::$connection = null;

        // Re-read connection params but override DB Name
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';

        // Construct new DSN
        $dsn = "mysql:host=$host;port=$port;dbname=$tenantDbName;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            self::$connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Security: Generic message to avoid leaking internal info
            error_log("Tenant Connection Failed ($tenantDbName): " . $e->getMessage());
            throw new PDOException("Tenant Database Connection Error.");
        }
    }

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

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // SSL Configuration (Secure Connections)
            if (!empty($_ENV['DB_SSL_CA'])) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $_ENV['DB_SSL_CA'];
            }
            if (!empty($_ENV['DB_SSL_CERT'])) {
                $options[PDO::MYSQL_ATTR_SSL_CERT] = $_ENV['DB_SSL_CERT'];
            }
            if (!empty($_ENV['DB_SSL_KEY'])) {
                $options[PDO::MYSQL_ATTR_SSL_KEY] = $_ENV['DB_SSL_KEY'];
            }
            if (isset($_ENV['DB_SSL_VERIFY_SERVER_CERT']) && $_ENV['DB_SSL_VERIFY_SERVER_CERT'] === 'false') {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            try {
                self::$connection = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                throw new PDOException("Errore di connessione al Database (MySQL): " . $e->getMessage(), (int) $e->getCode(), $e);
            }
        }
        return self::$connection;
    }
}


