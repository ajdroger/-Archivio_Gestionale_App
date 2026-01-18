<?php

declare(strict_types=1);

namespace MCAG\Controller\External;

use MCAG\InfrastrutturaIT\Persistence\PDOExpensebarRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExpensebarController
{
    private \Mustache_Engine $mustache;
    private PDOExpensebarRepository $repository;
    private string $baseUrl;

    public function __construct(\Mustache_Engine $mustache, PDOExpensebarRepository $repository)
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
            'body_class' => 'bg-mcag-slate text-mcag-text min-h-screen font-sans selection:bg-mcag-accent/30',
            'extra_css' => [
                $this->baseUrl . '/css/tailwind-external.css',
                $this->baseUrl . '/assets/expensebar/style.css'
            ]
        ];
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('expensebar/index.mustache', $this->getCommonData('Expensebar Dashboard'));
        $response->getBody()->write($html);
        return $response;
    }

    public function analytics(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('expensebar/analytics.mustache', $this->getCommonData('Expensebar Analytics'));
        $response->getBody()->write($html);
        return $response;
    }

    // API Methods

    public function getExpenses(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $expenses = $this->repository->findAll();
        $response->getBody()->write(json_encode($expenses));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function addExpense(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        $newExpense = [
            'description' => $data['description'] ?? 'Expense',
            'amount' => (float) ($data['amount'] ?? 0),
            'category' => $data['category'] ?? 'General',
            'date' => $data['date'] ?? date('Y-m-d')
        ];

        $id = $this->repository->save($newExpense);
        $newExpense['id'] = $id;

        $response->getBody()->write(json_encode(['status' => 'success', 'expense' => $newExpense]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getForecast(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $expenses = $this->repository->findAll();
        $jsonData = json_encode($expenses);

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];

        $pythonScript = __DIR__ . '/../../../bin/python/expense_forecast.py';
        $cmd = "python \"{$pythonScript}\"";

        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], $jsonData);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            proc_close($process);

            if ($output) {
                $response->getBody()->write($output);
            } else {
                $response->getBody()->write(json_encode(['error' => 'No output from python', 'debug' => $error]));
            }
        } else {
            $response->getBody()->write(json_encode(['error' => 'Failed to launch python process']));
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
}
