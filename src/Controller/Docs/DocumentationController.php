<?php

namespace MCAG\Controller\Docs;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Generator;
use Mustache_Engine;

class DocumentationController
{
    private $renderer;
    private $projectRoot;

    public function __construct(Mustache_Engine $renderer)
    {
        $this->renderer = $renderer;
        $this->projectRoot = dirname(__DIR__, 3); // Leads to project root from src/Controller/Docs
    }

    /**
     * Serves the Swagger UI HTML page.
     */
    public function ui(Request $request, Response $response): Response
    {
        $template = $this->renderer->loadTemplate('docs/swagger');
        $html = $template->render([
            'specUrl' => '/api/docs/json'
        ]);

        $response->getBody()->write($html);
        return $response;
    }



    /**
     * Download or View File
     */
    public function viewFile(Request $request, Response $response, array $args): Response
    {
        $category = $args['category'];
        $filename = $args['file'];
        $path = $this->projectRoot . '/Documentazione/' . $category . '/' . $filename;

        if (!file_exists($path)) {
            $response->getBody()->write("File not found");
            return $response->withStatus(404);
        }

        // For now, force download or raw view
        $content = file_get_contents($path);

        // Basic Markdown render (could be improved with Parsedown)
        if ($request->getQueryParams()['mode'] === 'raw') {
            $response->getBody()->write($content);
            return $response->withHeader('Content-Type', 'text/plain');
        }

        // Just serve as download
        return $response
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withBody((new \Slim\Psr7\Stream(fopen($path, 'r'))));
    }

    private function getCategoryIcon(string $name): string
    {
        $map = [
            'Sicurezza' => 'shield-halved',       // More standard FA icon
            'Manuali' => 'book',
            'Analisi' => 'chart-line',
            'Architettura' => 'server',
            'Legislazione' => 'scale-balanced',   // Potential alias
            'Legal' => 'scale-balanced',
            'Legale' => 'scale-balanced',
            'Commerciale' => 'briefcase',
            'Guide' => 'compass',
            'Operations' => 'gears',
            'Presentazioni' => 'chalkboard-user', // Presentation screen
            'Report' => 'file-contract',
            'Sviluppo' => 'code',
            'Varia' => 'box-archive',
            '00_Index' => 'list'
        ];
        return $map[$name] ?? 'folder';
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Generates and returns the OpenAPI JSON specification.
     */
    public function spec(Request $request, Response $response): Response
    {
        $paths = [
            realpath($this->projectRoot . '/src/Controller'),
            realpath($this->projectRoot . '/src/DTO'),
        ];

        $paths = array_filter($paths);
        $openapi = Generator::scan($paths);

        $response->getBody()->write($openapi->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Renders the Documentation Hub Dashboard.
     */
    public function hub(Request $request, Response $response): Response
    {
        $docRoot = $this->projectRoot . '/Documentazione';
        $dirs = array_filter(glob($docRoot . '/*'), 'is_dir');

        $categories = [];
        $icons = [
            'Analisi' => 'fa-magnifying-glass-chart',
            'Architettura' => 'fa-sitemap',
            'Commerciale' => 'fa-briefcase',
            'Guide' => 'fa-book-open',
            'Legal' => 'fa-scale-balanced',
            'Manuali' => 'fa-book-atlas',
            'Operations' => 'fa-gears',
            'Sicurezza' => 'fa-shield-halved',
            'Sviluppo' => 'fa-code-branch',
            'default' => 'fa-folder'
        ];

        foreach ($dirs as $dir) {
            $name = basename($dir);
            // Skip system folders if any
            if ($name === '00_Index')
                continue;

            $fileCount = count(glob($dir . '/*.md'));

            $categories[] = [
                'name' => $name,
                'path' => '/MCAG_Militare-Civile-Archivio-Gestionale/public/docs/show/' . $name, // Route-based link
                'icon' => $icons[$name] ?? $icons['default'],
                'count' => $fileCount,
                'description' => "Documentation section for $name" // Placeholder
            ];
        }

        $template = $this->renderer->loadTemplate('docs/hub');
        $html = $template->render([
            'categories' => $categories,
            'title' => 'Documentation Hub',
            'base_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public',
            // Context for Layout
            'username' => $_SESSION['username'] ?? 'User',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)),
            'real_is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Lists files in a specific documentation category.
     */
    public function category(Request $request, Response $response, array $args): Response
    {
        $category = $args['category'];
        // Sanitize
        $category = preg_replace('/[^a-zA-Z0-9_-]/', '', $category);

        $targetDir = $this->projectRoot . '/Documentazione/' . $category;

        if (!is_dir($targetDir)) {
            // Fallback or 404
            $response->getBody()->write("Category not found.");
            return $response->withStatus(404);
        }

        $files = glob($targetDir . '/*.{md,txt,pdf}', GLOB_BRACE);
        $fileList = [];

        foreach ($files as $file) {
            $fileList[] = [
                'name' => basename($file),
                'size' => round(filesize($file) / 1024, 1) . ' KB',
                'updated' => date('d M Y', filemtime($file)),
                'url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public/docs/read/' . $category . '/' . basename($file)
            ];
        }

        $template = $this->renderer->loadTemplate('docs/category');
        $html = $template->render([
            'category_name' => $category,
            'files' => $fileList,
            'base_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public',
            'hub_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public/docs/hub'
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Reads and displays a specific documentation file.
     */
    public function read(Request $request, Response $response, array $args): Response
    {
        $category = preg_replace('/[^a-zA-Z0-9_-]/', '', $args['category']);
        $filename = basename($args['file']);

        $filePath = $this->projectRoot . '/Documentazione/' . $category . '/' . $filename;

        if (!file_exists($filePath)) {
            $response->getBody()->write("File not found.");
            return $response->withStatus(404);
        }

        $content = file_get_contents($filePath);
        $isMarkdown = str_ends_with(strtolower($filename), '.md');

        // Simple Markdown parse if needed, or raw text for now
        // For better experience, we should use a parser, but <pre> is safe for v1.

        $template = $this->renderer->loadTemplate('docs/viewer');
        $html = $template->render([
            'filename' => $filename,
            'category' => $category,
            'content' => $content,
            'is_markdown' => $isMarkdown,
            'base_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public',
            'back_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public/docs/show/' . $category
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Searches documentation files for a keyword.
     */
    public function search(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $query = trim($queryParams['q'] ?? '');

        if (strlen($query) < 3) {
            $response->getBody()->write(json_encode([]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $docRoot = $this->projectRoot . '/Documentazione';
        $results = [];

        // Recursive search would be better, but we have flat Category/File structure mostly
        $categories = array_filter(glob($docRoot . '/*'), 'is_dir');

        foreach ($categories as $catDir) {
            $category = basename($catDir);
            if ($category === '00_Index')
                continue;

            $files = glob($catDir . '/*.md');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (stripos($content, $query) !== false) {
                    // Found match
                    $filename = basename($file);

                    // Create a snippet
                    $pos = stripos($content, $query);
                    $start = max(0, $pos - 50);
                    $length = 150; // Context length
                    $snippet = substr($content, $start, $length);
                    $snippet = str_ireplace($query, "**$query**", $snippet); // Highlight

                    $results[] = [
                        'title' => $filename,
                        'category' => $category,
                        'snippet' => "...{$snippet}...",
                        'url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public/docs/read/' . $category . '/' . $filename
                    ];

                    if (count($results) >= 10)
                        break 2; // Limit to 10 results
                }
            }
        }

        $response->getBody()->write(json_encode($results));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
