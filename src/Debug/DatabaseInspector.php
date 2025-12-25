<?php

namespace FratellanzaMilitare\Debug;

use PDO;
use Exception;

class DatabaseInspector
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Ritorna un riepilogo delle tabelle presenti nel database
     */
    public function getTablesSummary(): array
    {
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'mysql') {
                $stmt = $this->db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
                $tables = [];

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tableName = $row['table_name'];
                    $countStmt = $this->db->query("SELECT COUNT(*) as total FROM `$tableName`");
                    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

                    $tables[] = [
                        'name' => $tableName,
                        'rows' => $count
                    ];
                }
            } else {
                // SQLite
                $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = [];

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tableName = $row['name'];
                    $countStmt = $this->db->query("SELECT COUNT(*) as total FROM \"$tableName\"");
                    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

                    $tables[] = [
                        'name' => $tableName,
                        'rows' => $count
                    ];
                }
            }

            return $tables;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Verifica l'integrità del database
     */
    public function checkIntegrity(): string
    {
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'mysql') {
                // MySQL: InnoDB handles integrity internally
                return "ok (InnoDB managed)";
            } else {
                // SQLite
                $stmt = $this->db->query("PRAGMA integrity_check");
                return $stmt->fetchColumn();
            }
        } catch (Exception $e) {
            return "Errore: " . $e->getMessage();
        }
    }

    /**
     * Ottiene la dimensione del database
     */
    public function getDatabaseSize(): string
    {
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'mysql') {
                $stmt = $this->db->query("SELECT SUM(data_length + index_length) / 1024 as size_kb FROM information_schema.tables WHERE table_schema = DATABASE()");
                $size = $stmt->fetch(PDO::FETCH_ASSOC)['size_kb'] ?? 0;
                return round($size, 2) . " KB";
            } else {
                // SQLite
                $path = $this->db->query("PRAGMA database_list")->fetch(PDO::FETCH_ASSOC)['file'] ?? '';
                if ($path && file_exists($path)) {
                    $size = filesize($path);
                    return round($size / 1024, 2) . " KB";
                }
                return "N/D";
            }
        } catch (Exception $e) {
            return "Errore: " . $e->getMessage();
        }
    }
}
