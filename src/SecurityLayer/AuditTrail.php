<?php

namespace MCAG\SecurityLayer;

use Psr\Log\LoggerInterface;

class AuditTrail
{
    private static ?AuditTrail $instance = null;
    private ?LoggerInterface $logger = null;
    private ?\PDO $pdo = null;

    public static function getInstance(): AuditTrail
    {
        if (self::$instance === null) {
            self::$instance = new AuditTrail();
        }
        return self::$instance;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setPdo(\PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    private function __construct()
    {
        // Singleton
    }

    /**
     * Registra un evento di sistema
     *
     * @param UtenteSistema|null $utente (null for system operations)
     * @param string $action
     * @param string $resourceId
     * @return void
     */
    public function logEvento(?UtenteSistema $utente, string $action, string $resourceId): void
    {
        $user_id = $utente?->ID ?? null;
        $username = $utente?->Username ?? 'SYSTEM';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';

        $username_masked = $this->pseudonimizza($username);
        $resource_id_masked = $this->pseudonimizza($resourceId);

        // 1. Log to Monolog (Filesystem)
        if ($this->logger) {
            $this->logger->info("AUDIT: $action", [
                'user' => $username_masked,
                'res' => $resource_id_masked,
                'action' => $action
            ]);
        }

        // 2. Log to Database (for queryability)
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("INSERT INTO audit_logs (user_id, username, action, resource_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $username_masked, $action, $resource_id_masked, $ip, $ua]);
            } catch (\Exception $e) {
                // Fallback if DB is down
                error_log("Audit DB failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Alias per logEvento per compatibilità.
     * 
     * @param string $action
     * @param string $resourceType (ignored/legacy)
     * @param string|int|null $resourceId
     * @param array $context (ignored/legacy)
     */
    public function log(string $action, string $resourceType, string|int|null $resourceId, array $context = []): void
    {
        // Adatta la vecchia firma (action, type, id, context) alla nuova (utente, action, id)
        // Poiché ApiKeyMiddleware non ha l'oggetto UtenteSistema, passiamo null per user.
        $this->logEvento(null, $action, (string) $resourceId);
    }

    public function ricercaAzioni(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        if (!$this->pdo) {
            return ['data' => [], 'total' => 0];
        }

        $sql = "SELECT * FROM audit_logs WHERE 1=1";
        $params = [];

        $allowedFilters = ['user_id', 'username', 'action', 'ip_address', 'resource_id'];

        foreach ($filters as $key => $value) {
            if (in_array($key, $allowedFilters)) {
                if ($key === 'resource_id') {
                    $value = $this->pseudonimizza($value);
                }
                $sql .= " AND $key = ?";
                $params[] = $value;
            }
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND timestamp >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND timestamp <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
        }

        // 1. Get Total Count (for Paginator)
        $countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
        $stmtCount = $this->pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        // 2. Get Data with Limit/Offset
        if ($perPage > 0) {
            $offset = ($page - 1) * $perPage;
            // Use integers directly, not as bound parameters for LIMIT/OFFSET (MySQL compatibility)
            $sql .= " ORDER BY timestamp DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;
        } else {
            $sql .= " ORDER BY timestamp DESC";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total' => (int) $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }

    public function generaReport(string $periodo = 'today'): array
    {
        if (!$this->pdo) {
            return ['totale_eventi' => 0, 'azioni_per_tipo' => [], 'utenti_attivi' => 0];
        }

        $interval = match ($periodo) {
            'today' => "-1 day",
            'week' => "-7 days",
            'month' => "-30 days",
            default => "-1 day"
        };

        // Build SQL compatible with MySQL
        $days = match ($periodo) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            default => 1
        };

        $sql = "SELECT * FROM audit_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL $days DAY)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([]);
        $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $report = [
            'periodo' => $periodo,
            'totale_eventi' => count($events),
            'azioni_per_tipo' => [],
            'utenti_attivi' => [],
            'data_generazione' => date('Y-m-d H:i:s')
        ];

        foreach ($events as $event) {
            $action = $event['action'];
            $report['azioni_per_tipo'][$action] = ($report['azioni_per_tipo'][$action] ?? 0) + 1;
            $report['utenti_attivi'][$event['username']] = true;
        }

        $report['utenti_attivi'] = count($report['utenti_attivi']);

        return $report;
    }

    private function pseudonimizza(string $dato): string
    {
        if (preg_match('/^[A-Z0-9]{16}$/i', $dato)) {
            return substr($dato, 0, 4) . '********' . substr($dato, -4);
        }
        if (strlen($dato) > 10) {
            return substr($dato, 0, 3) . '...' . substr($dato, -3);
        }
        return $dato;
    }

    public function esportaLog(string $formato = 'json'): string
    {
        // Get ALL logs
        $result = $this->ricercaAzioni([], 1, -1);
        $data = $result['data'];

        if ($formato === 'json') {
            return json_encode($data, JSON_PRETTY_PRINT);
        }

        if ($formato === 'csv') {
            $csv = "Timestamp,User ID,Username,Action,Resource ID,IP Address,User Agent\n";
            foreach ($data as $event) {
                $csv .= sprintf(
                    '"%s",%d,"%s","%s","%s","%s","%s"' . "\n",
                    $event['timestamp'],
                    $event['user_id'],
                    $event['username'],
                    $event['action'],
                    $event['resource_id'],
                    $event['ip_address'],
                    $event['user_agent']
                );
            }
            return $csv;
        }

        throw new \InvalidArgumentException("Formato $formato non supportato.");
    }
    public function getThreats(int $limit = 20): array
    {
        if (!$this->pdo) {
            return [];
        }

        // Fetch recent security-relevant events that are NOT resolved
        $sql = "SELECT * FROM audit_logs 
                WHERE action IN ('LOGIN_FAILED', 'ACCESS_DENIED', 'AUTH_ERROR', 'SUSPICIOUS_ACTIVITY', 'SYSTEM_ALERT') 
                AND resolved_at IS NULL
                ORDER BY timestamp DESC 
                LIMIT " . (int) $limit;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // If empty (e.g. fresh install), we return nothing now.
            // The simulation fallback is removed to comply with "Real Mode" 100%.

            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function resolveThreat(int $id): bool
    {
        if (!$this->pdo)
            return false;
        try {
            $stmt = $this->pdo->prepare("UPDATE audit_logs SET resolved_at = NOW() WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function resolveAll(): bool
    {
        if (!$this->pdo)
            return false;
        try {
            $stmt = $this->pdo->prepare("UPDATE audit_logs SET resolved_at = NOW() WHERE resolved_at IS NULL AND action IN ('LOGIN_FAILED', 'ACCESS_DENIED', 'AUTH_ERROR', 'SUSPICIOUS_ACTIVITY', 'SYSTEM_ALERT')");
            return $stmt->execute();
        } catch (\Exception $e) {
            return false;
        }
    }
}


