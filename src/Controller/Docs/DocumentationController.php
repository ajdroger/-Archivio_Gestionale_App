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
     * Documentation Hub (Knowledge Base)
     * Lists categories (folders) in the Documentazione directory.
     */
    public function hub(Request $request, Response $response): Response
    {
        $docsPath = $this->projectRoot . '/Documentazione';
        $categories = [];

        if (is_dir($docsPath)) {
            $items = scandir($docsPath);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..')
                    continue;

                $path = $docsPath . '/' . $item;
                if (is_dir($path)) {
                    // It's a category
                    $categories[] = [
                        'name' => $item,
                        'slug' => $item, // Simple slug for now
                        'count' => count(glob($path . '/*.md')),
                        'icon' => $this->getCategoryIcon($item)
                    ];
                }
            }
        }

        $html = $this->renderer->render('docs/hub', [
            'categories' => $categories,
            'page_title' => 'Documentation Hub',
            'user' => $_SESSION['username'] ?? 'User',
            'username' => $_SESSION['username'] ?? 'User',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)),
            'real_is_admin' => ($_SESSION['real_is_admin'] ?? false) || (strcasecmp($_SESSION['username'] ?? '', 'aj_godmode') === 0),
            'base_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public'
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * View specific category files
     */
    public function category(Request $request, Response $response, array $args): Response
    {
        $category = $args['category'];
        $docsPath = $this->projectRoot . '/Documentazione/' . $category;
        $files = [];

        if (is_dir($docsPath)) {
            $items = glob($docsPath . '/*.md');
            foreach ($items as $file) {
                $files[] = [
                    'name' => basename($file, '.md'),
                    'filename' => basename($file),
                    'size' => $this->formatBytes(filesize($file)),
                    'updated' => date('d M Y', filemtime($file))
                ];
            }
        }

        $html = $this->renderer->render('docs/category', [
            'category' => $category,
            'files' => $files,
            'page_title' => $category . ' - Docs',
            'user' => $_SESSION['username'] ?? 'User',
            'username' => $_SESSION['username'] ?? 'User',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)),
            'real_is_admin' => ($_SESSION['real_is_admin'] ?? false) || (strcasecmp($_SESSION['username'] ?? '', 'aj_godmode') === 0),
            'base_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public'
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
}


