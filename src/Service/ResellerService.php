<?php

namespace MCAG\Service;

class ResellerService
{
    private string $storageFile;

    public function __construct(string $appRoot)
    {
        $this->storageFile = $appRoot . '/storage/reseller_clients.json';
        $this->ensureStorageExists();
    }

    private function ensureStorageExists(): void
    {
        if (!file_exists($this->storageFile)) {
            // Seed with initial demo data if empty
            $initialData = [
                [
                    'id' => 'u1',
                    'name' => 'Associazione Alpini Milano',
                    'plan' => 'Pro',
                    'status' => 'Active',
                    'revenue' => 350,
                    'joined_at' => '2025-10-15'
                ],
                [
                    'id' => 'u2',
                    'name' => 'Comune di Vattelapesca',
                    'plan' => 'Enterprise',
                    'status' => 'Active',
                    'revenue' => 1200,
                    'joined_at' => '2025-11-20'
                ],
                [
                    'id' => 'u3',
                    'name' => 'Clinica San Giorgio',
                    'plan' => 'Health',
                    'status' => 'Pending',
                    'revenue' => 0,
                    'joined_at' => '2026-01-10'
                ]
            ];
            file_put_contents($this->storageFile, json_encode($initialData, JSON_PRETTY_PRINT));
        }
    }
    public function resetAuth(string $id): string
    {
        $clients = json_decode(file_get_contents($this->storageFile), true);
        $newPassword = '';
        foreach ($clients as &$client) {
            if ($client['id'] === $id) {
                // Real "Reset": Generate random string
                $newPassword = 'Tem' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 8) . '!';
                $client['admin_password'] = $newPassword; // Storing it (in real app would be hashed)
                $client['password_reset_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        file_put_contents($this->storageFile, json_encode($clients, JSON_PRETTY_PRINT));
        return $newPassword;
    }

    public function getAllClients(): array
    {
        $data = json_decode(file_get_contents($this->storageFile), true);

        // Enrich data for UI (e.g. status boolean)
        return array_map(function ($client) {
            $client['status_active'] = ($client['status'] === 'Active');
            $client['formatted_revenue'] = '€' . number_format($client['revenue'], 0, ',', '.');
            return $client;
        }, $data);
    }

    public function getAnalytics(): array
    {
        $clients = $this->getAllClients();
        $totalClients = count($clients);
        $totalRevenue = array_sum(array_column($clients, 'revenue'));
        $commission = $totalRevenue * 0.30; // 30% Partner Share

        return [
            'monthly_recurring' => '€' . number_format($totalRevenue, 0, ',', '.'),
            'commission' => '€' . number_format($commission, 0, ',', '.'),
            'next_payout' => date('15 M Y', strtotime('+1 month')),
            'raw_revenue' => $totalRevenue,
            // [NEW] Advanced Analytics Data
            'trend_current' => json_encode([3200, 3500, 3100, 4200, 4800, 5100, 5600, 5900, 6100, 6300, 6450, $totalRevenue]),
            'trend_previous' => json_encode([2800, 2900, 3000, 3100, 3050, 3200, 3400, 3800, 4100, 4300, 4500, 4700]),
            'trend_labels' => json_encode(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'])
        ];
    }

    public function createClient(array $data): void
    {
        $clients = json_decode(file_get_contents($this->storageFile), true);

        // Generate Professional Tenant ID (e.g., TEN-8X4D2)
        $tenantId = 'TEN-' . strtoupper(substr(md5(uniqid()), 0, 5));

        $newClient = [
            'id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'] ?? 'admin@' . strtolower(str_replace(' ', '', $data['name'])) . '.com',
            'plan' => $data['plan'],
            'region' => $data['region'] ?? 'eu-west-1',
            'environment' => $data['environment'] ?? 'production',
            'status' => 'Active',
            'revenue' => $this->calculateRevenue($data['plan']),
            'joined_at' => date('Y-m-d H:i:s')
        ];

        array_unshift($clients, $newClient); // Add to top
        file_put_contents($this->storageFile, json_encode($clients, JSON_PRETTY_PRINT));
    }

    public function getClient(string $id): ?array
    {
        $clients = json_decode(file_get_contents($this->storageFile), true);
        foreach ($clients as $client) {
            if ($client['id'] === $id) {
                return $client;
            }
        }
        return null;
    }

    public function toggleStatus(string $id): void
    {
        $clients = json_decode(file_get_contents($this->storageFile), true);
        foreach ($clients as &$client) {
            if ($client['id'] === $id) {
                $client['status'] = ($client['status'] === 'Active') ? 'Suspended' : 'Active';
                break;
            }
        }
        file_put_contents($this->storageFile, json_encode($clients, JSON_PRETTY_PRINT));
    }

    public function deleteClient(string $id): void
    {
        $clients = json_decode(file_get_contents($this->storageFile), true);
        $clients = array_filter($clients, function ($client) use ($id) {
            return $client['id'] !== $id;
        });
        file_put_contents($this->storageFile, json_encode(array_values($clients), JSON_PRETTY_PRINT));
    }

    private function calculateRevenue($plan): int
    {
        return match ($plan) {
            'Standard' => 150,
            'Pro' => 350,
            'Enterprise' => 1200,
            'Health' => 800,
            default => 0
        };
    }
}
