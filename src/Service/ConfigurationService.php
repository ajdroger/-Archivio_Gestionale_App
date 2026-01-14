<?php

namespace MCAG\Service;

class ConfigurationService
{
    private string $configPath;
    private array $config = [];

    public function __construct(string $storageDir)
    {
        $this->configPath = $storageDir . '/app_config.json';
        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->configPath)) {
            $content = file_get_contents($this->configPath);
            $this->config = json_decode($content, true) ?? [];
        }
    }

    private function save(): void
    {
        // Ensure directory exists
        $dir = dirname($this->configPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->configPath, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
        $this->save();
    }

    public function getAll(): array
    {
        return $this->config;
    }
}
