<?php

namespace FratellanzaMilitare\Controller\DevTools;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * DevTools FileSystem Controller
 * 
 * Handles file system browsing, reading, and editing
 */
class DevToolsFileSystemController
{
    public function fsList(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $basePath = realpath(__DIR__ . '/../../../');
        $requestPath = $data['path'] ?? '/';

        $targetPath = realpath($basePath . '/' . $requestPath);
        if (!$targetPath || !str_starts_with($targetPath, $basePath)) {
            $targetPath = $basePath;
        }

        $items = [];
        if (is_dir($targetPath)) {
            $scanned = scandir($targetPath);
            foreach ($scanned as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $fullPath = $targetPath . '/' . $item;
                $isDir = is_dir($fullPath);
                $relPath = str_replace($basePath, '', $fullPath);
                $relPath = str_replace('\\', '/', $relPath);

                $items[] = [
                    'name' => $item,
                    'path' => $relPath,
                    'type' => $isDir ? 'dir' : 'file',
                    'ext' => pathinfo($item, PATHINFO_EXTENSION),
                    'size' => $isDir ? '-' : $this->formatBytes(filesize($fullPath))
                ];
            }
        }

        usort($items, fn($a, $b) => $b['type'] <=> $a['type']);

        $response->getBody()->write(json_encode([
            'current' => str_replace('\\', '/', str_replace($basePath, '', $targetPath)) ?: '/',
            'items' => $items
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fsRead(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $path = $data['path'] ?? '';
        $basePath = realpath(__DIR__ . '/../../../');
        $fullPath = realpath($basePath . '/' . $path);

        if (!$fullPath || !str_starts_with($fullPath, $basePath) || !is_file($fullPath)) {
            $response->getBody()->write(json_encode(['error' => 'File non trovato o accesso negato.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $content = file_get_contents($fullPath);
        $response->getBody()->write(json_encode(['content' => $content]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fsSave(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $path = $data['path'] ?? '';
        $content = $data['content'] ?? '';
        $basePath = realpath(__DIR__ . '/../../../');
        $fullPath = realpath($basePath . '/' . $path);

        if (!$fullPath || !str_starts_with($fullPath, $basePath) || !is_file($fullPath)) {
            $response->getBody()->write(json_encode(['error' => 'File non valido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        file_put_contents($fullPath, $content);
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
