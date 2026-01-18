<?php

declare(strict_types=1);

namespace MCAG\Controller\External;

use MCAG\InfrastrutturaIT\Persistence\PDOTaskflowRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TaskflowController
{
    private \Mustache_Engine $mustache;
    private PDOTaskflowRepository $repository;
    private string $baseUrl;

    public function __construct(\Mustache_Engine $mustache, PDOTaskflowRepository $repository)
    {
        $this->mustache = $mustache;
        $this->repository = $repository;

        // Determine base URL
        $this->baseUrl = '';
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $this->baseUrl = dirname($_SERVER['SCRIPT_NAME']);
            if ($this->baseUrl === '/' || $this->baseUrl === '\\') {
                $this->baseUrl = '';
            }
        }
    }

    private function getCommonData(string $title): array
    {
        return [
            'base_url' => $this->baseUrl,
            'title' => $title,
            'user' => $_SESSION['user'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? 'GUEST',
            'user_initial' => isset($_SESSION['user']['username']) ? strtoupper(substr($_SESSION['user']['username'], 0, 1)) : 'U',
            'username' => $_SESSION['user']['username'] ?? 'Ospite',
            'is_immersive' => true,
            'body_class' => 'bg-mcag-slate text-mcag-text min-h-screen',
            'extra_css' => [
                $this->baseUrl . '/css/tailwind-external.css',
                $this->baseUrl . '/assets/taskflow/style.css'
            ]
        ];
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->getCommonData('Taskflow Pro');
        // Initial tasks data can be passed to view if needed, but usually fetched via API
        $html = $this->mustache->render('taskflow/index.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function about(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('taskflow/about.mustache', $this->getCommonData('About Taskflow'));
        $response->getBody()->write($html);
        return $response;
    }

    // API Methods

    public function getTasks(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $tasks = $this->repository->findAll();
        $response->getBody()->write(json_encode($tasks ?: [], JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function addTask(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (empty($data['text'])) {
            $response->getBody()->write(json_encode(['error' => 'Testo dell\'attività mancante'], JSON_THROW_ON_ERROR));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $task = [
            'text' => htmlspecialchars($data['text']),
            'completed' => (bool) ($data['completed'] ?? false)
        ];

        $id = $this->repository->save($task);
        $task['id'] = $id;

        $response->getBody()->write(json_encode(['message' => 'Attività aggiunta', 'task' => $task], JSON_THROW_ON_ERROR));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function updateTask(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (empty($data['id'])) {
            $response->getBody()->write(json_encode(['error' => 'ID dell\'attività mancante'], JSON_THROW_ON_ERROR));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Fetch existing task to merge data
        $existingTask = $this->repository->findById((int) $data['id']);
        if (!$existingTask) {
            $response->getBody()->write(json_encode(['error' => 'Attività non trovata'], JSON_THROW_ON_ERROR));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $updateData = [
            'id' => $data['id'],
            'text' => isset($data['text']) ? htmlspecialchars($data['text']) : $existingTask['text'],
            'completed' => isset($data['completed']) ? (int) $data['completed'] : (int) $existingTask['completed']
        ];

        // Improve: fetch existing to ensure we don't overwrite with empty if partial update
        // But for now, assuming frontend logic sends valid data.

        $this->repository->save($updateData);

        $response->getBody()->write(json_encode(['message' => 'Attività aggiornata'], JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteTask(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (empty($data['id']) && !isset($data['action'])) {
            $response->getBody()->write(json_encode(['error' => 'ID dell\'attività mancante'], JSON_THROW_ON_ERROR));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (isset($data['action']) && $data['action'] === 'clearCompleted') {
            $this->repository->clearCompleted();
            $response->getBody()->write(json_encode(['message' => 'Attività completate eliminate'], JSON_THROW_ON_ERROR));
        } else {
            $this->repository->delete((int) $data['id']);
            $response->getBody()->write(json_encode(['message' => 'Attività eliminata'], JSON_THROW_ON_ERROR));
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
}
