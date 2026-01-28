<?php

namespace MCAG\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mustache_Engine;

class DocsController
{
    private $renderer;
    private $docsRoot;

    public function __construct(Mustache_Engine $renderer)
    {
        $this->renderer = $renderer;
        // Adjust path to point to the Documentazione folder relative to src/Controller
        $this->docsRoot = realpath(__DIR__ . '/../../Documentazione');
    }

    public function index(Request $request, Response $response): Response
    {
        if (!isset($_SESSION['username'])) {
            return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/auth/login')->withStatus(302);
        }

        $categories = [];
        if (is_dir($this->docsRoot)) {
            $dirs = array_filter(glob($this->docsRoot . '/*'), 'is_dir');
            foreach ($dirs as $dir) {
                $basename = basename($dir);
                $fileCount = count(glob($dir . '/*.*'));

                // Icon Mapping
                $icon = 'fa-folder';
                $color = 'secondary';

                if (stripos($basename, 'sicurezza') !== false) {
                    $icon = 'fa-shield-halved';
                    $color = 'danger';
                } elseif (stripos($basename, 'analisi') !== false) {
                    $icon = 'fa-magnifying-glass-chart';
                    $color = 'info';
                } elseif (stripos($basename, 'architettura') !== false) {
                    $icon = 'fa-sitemap';
                    $color = 'warning';
                } elseif (stripos($basename, 'commerciale') !== false) {
                    $icon = 'fa-briefcase';
                    $color = 'success';
                } elseif (stripos($basename, 'guide') !== false) {
                    $icon = 'fa-book-open';
                    $color = 'primary';
                } elseif (stripos($basename, 'manuali') !== false) {
                    $icon = 'fa-book';
                    $color = 'info';
                } elseif (stripos($basename, 'legal') !== false) {
                    $icon = 'fa-scale-balanced';
                    $color = 'secondary';
                } elseif (stripos($basename, 'report') !== false) {
                    $icon = 'fa-file-contract';
                    $color = 'white';
                } elseif (stripos($basename, 'sviluppo') !== false) {
                    $icon = 'fa-code';
                    $color = 'success';
                }

                $categories[] = [
                    'name' => $basename,
                    'count' => $fileCount,
                    'icon' => $icon,
                    'color' => $color,
                    'encoded_name' => urlencode($basename)
                ];
            }
        }

        $html = $this->renderer->render('docs/hub', [
            'page_title' => 'Documentation Hub',
            'categories' => $categories,
            'user' => $_SESSION['username'] ?? 'User',
            'username' => $_SESSION['username'] ?? 'User',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)),
            'base_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public',
            'real_is_admin' => true // Assume access for now, or match session usually
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function category(Request $request, Response $response, array $args): Response
    {
        if (!isset($_SESSION['username'])) {
            return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/auth/login')->withStatus(302);
        }

        $categoryName = urldecode($args['category']);
        $targetDir = $this->docsRoot . DIRECTORY_SEPARATOR . $categoryName;

        // Security traversal check
        if (strpos(realpath($targetDir), $this->docsRoot) !== 0 || !is_dir($targetDir)) {
            return $response->withStatus(404)->write("Category not found");
        }

        $files = [];
        foreach (glob($targetDir . '/*.*') as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $icon = 'fa-file';
            if ($ext === 'pdf')
                $icon = 'fa-file-pdf text-danger';
            elseif ($ext === 'md')
                $icon = 'fa-file-code text-info';
            elseif ($ext === 'txt')
                $icon = 'fa-file-lines text-secondary';
            elseif ($ext === 'jpg' || $ext === 'png')
                $icon = 'fa-file-image text-warning';

            $files[] = [
                'name' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('d M Y H:i', filemtime($file)),
                'icon' => $icon,
                'encoded_name' => urlencode(basename($file)),
                'category_encoded' => urlencode($categoryName)
            ];
        }

        $html = $this->renderer->render('docs/category', [
            'page_title' => $categoryName . ' - Docs',
            'category' => $categoryName,
            'files' => $files,
            'user' => $_SESSION['username'] ?? 'User',
            'username' => $_SESSION['username'] ?? 'User',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)),
            'base_url' => '/MCAG_Militare-Civile-Archivio-Gestionale/public',
            'real_is_admin' => true
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function download(Request $request, Response $response, array $args): Response
    {
        if (!isset($_SESSION['username'])) {
            return $response->withStatus(403);
        }

        $category = urldecode($args['category']);
        $filename = urldecode($args['file']);
        $path = $this->docsRoot . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $filename;

        // Security Check
        if (strpos(realpath($path), $this->docsRoot) !== 0 || !file_exists($path)) {
            return $response->withStatus(404)->write("File not found");
        }

        $mimeType = mime_content_type($path);

        return $response
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withHeader('Content-Length', filesize($path))
            ->withBody(new \Slim\Psr7\Stream(fopen($path, 'rb')));
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
