<?php

namespace MCAG\Service\Audit;

use PDO;
use Exception;

class AIAuditLogger
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Log an AI Interaction for Regulatory Compliance (EU AI Act / GDPR 2.0)
     * 
     * @param string $driver The AI Driver used (e.g., 'ollama', 'openai')
     * @param string $context The business context (e.g., 'shift_optimization', 'translation')
     * @param string $inputSnapshot A snapshot or hash of the input data
     * @param string $outputSnapshot The generated output
     * @param float $latencyMs Execution time in milliseconds
     * @return int The ID of the inserted log record
     */
    public function logInteraction(string $driver, string $context, string $inputSnapshot, string $outputSnapshot, float $latencyMs): int
    {
        try {
            // Ensure table exists (Auto-Migration for Solo Dev speed)
            // In Production, this should be a Phinx migration
            $this->ensureTableExists();

            $stmt = $this->connection->prepare("
                INSERT INTO ai_decision_logs 
                (driver, context, input_snapshot, output_snapshot, latency_ms, created_at) 
                VALUES (:driver, :context, :input, :output, :latency, NOW())
            ");

            $stmt->execute([
                ':driver' => $driver,
                ':context' => $context,
                ':input' => substr($inputSnapshot, 0, 2000), // Truncate for storage sanity
                ':output' => substr($outputSnapshot, 0, 2000),
                ':latency' => $latencyMs
            ]);

            return (int) $this->connection->lastInsertId();

        } catch (Exception $e) {
            // Fallback to file log if DB fails (Crucial for Compliance)
            error_log("[AI AUDIT FAILURE] " . $e->getMessage());
            return 0;
        }
    }

    private function ensureTableExists(): void
    {
        // Check if table exists, if not create it (SQLite/MySQL compatibleish)
        $sql = "
            CREATE TABLE IF NOT EXISTS ai_decision_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                driver VARCHAR(50) NOT NULL,
                context VARCHAR(100) NOT NULL,
                input_snapshot TEXT,
                output_snapshot TEXT,
                latency_ms FLOAT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ";

        // Suppress errors if table exists logic is flaky across DB versions
        try {
            $this->connection->exec($sql);
        } catch (Exception $e) {
            // Ignore if exists
        }
    }
}
