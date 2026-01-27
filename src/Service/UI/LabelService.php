<?php

namespace MCAG\Service\UI;

class LabelService
{
    private array $dictionary = [];
    private string $currentVertical;

    public function __construct(string $vertical = 'standard')
    {
        $this->currentVertical = $vertical;
        $this->loadDictionary($vertical);
    }

    private function loadDictionary(string $vertical): void
    {
        $path = __DIR__ . '/../../../config/verticals/' . $vertical . '.php';
        if (file_exists($path)) {
            $this->dictionary = require $path;
        } else {
            // Fallback to standard if specific vertical not found
            $this->dictionary = [];
        }
    }

    /**
     * Get the industry-specific term for a generic label.
     * Usage: $labelService->get('employee_single') -> 'Infermiere' (if healthcare)
     */
    public function get(string $key, string $default = ''): string
    {
        return $this->dictionary[$key] ?? $default;
    }

    /**
     * Helper for Mustache templates
     */
    public function getTemplateHelper(): \Closure
    {
        return function ($text) {
            // Parses syntax like {{#label}}employee_single{{/label}}
            return $this->get(trim($text), trim($text));
        };
    }
}
