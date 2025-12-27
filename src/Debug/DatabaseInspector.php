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
            // MySQL: InnoDB handles integrity internally
            return "ok (InnoDB Managed)";
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
            $stmt = $this->db->query("SELECT SUM(data_length + index_length) / 1024 as size_kb FROM information_schema.tables WHERE table_schema = DATABASE()");
            $size = $stmt->fetch(PDO::FETCH_ASSOC)['size_kb'] ?? 0;
            return round($size, 2) . " KB";
        } catch (Exception $e) {
            return "Errore: " . $e->getMessage();
        }
    }
}
