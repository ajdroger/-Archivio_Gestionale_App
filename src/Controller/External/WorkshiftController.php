<?php

declare(strict_types=1);

namespace MCAG\Controller\External;

use MCAG\InfrastrutturaIT\Persistence\PDOWorkshiftRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mustache_Engine;

class WorkshiftController
{
    private Mustache_Engine $mustache;
    private PDOWorkshiftRepository $repository;
    private string $baseUrl;

    public function __construct(Mustache_Engine $mustache, PDOWorkshiftRepository $repository)
    {
        $this->mustache = $mustache;
        $this->repository = $repository;

        // Determine base URL dynamically
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $this->baseUrl = $scriptDir === '/' ? '' : $scriptDir;
    }

    private function getCommonData(string $title): array
    {
        return [
            'base_url' => $this->baseUrl,
            'title' => $title,
            'user' => $_SESSION['user'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? $_SESSION['temp_user_role'] ?? 'GUEST',
            'username' => $_SESSION['username'] ?? $_SESSION['user']['username'] ?? $_SESSION['temp_username'] ?? 'Ospite',
        ];
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('workshift/index.mustache', $this->getCommonData('Workshift Dashboard'));
        $response->getBody()->write($html);
        return $response;
    }

    public function shiftManagement(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('workshift/shift-management.mustache', $this->getCommonData('Gestione Turni'));
        $response->getBody()->write($html);
        return $response;
    }

    public function teamManagement(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('workshift/team-management.mustache', $this->getCommonData('Gestione Team'));
        $response->getBody()->write($html);
        return $response;
    }

    public function timeOff(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('workshift/time-off.mustache', $this->getCommonData('Richiesta Ferie'));
        $response->getBody()->write($html);
        return $response;
    }

    public function reports(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('workshift/reports.mustache', $this->getCommonData('Reportistica'));
        $response->getBody()->write($html);
        return $response;
    }

    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = $args['page'];
        $templateMap = [
            'support' => 'info/support',
            'hr-policy' => 'info/hr-policy',
            'system-status' => 'info/status',
            'labor-laws' => 'info/labor-laws',
            'privacy' => 'info/privacy',
            'terms' => 'info/terms',
        ];

        if (!array_key_exists($page, $templateMap)) {
            // Simple 404 handling if not found
            $response->getBody()->write("Page not found");
            return $response->withStatus(404);
        }

        $titleMap = [
            'support' => 'Supporto',
            'hr-policy' => 'HR Policy',
            'system-status' => 'Stato del Sistema',
            'labor-laws' => 'Normative Lavoro',
            'privacy' => 'Privacy Policy',
            'terms' => 'Termini di Servizio',
        ];

        $html = $this->mustache->render('workshift/' . $templateMap[$page] . '.mustache', $this->getCommonData($titleMap[$page]));
        $response->getBody()->write($html);
        return $response;
    }

    public function saveShift(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        // Validate required fields
        if (empty($data['employee_id']) || empty($data['start_time']) || empty($data['end_time'])) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Save using repository
        $id = $this->repository->save([
            'employee_id' => $data['employee_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'type' => $data['type'] ?? 'Standard',
            'day' => $data['day'] ?? '',
            'date' => $data['date'] ?? date('Y-m-d')
        ]);

        $response->getBody()->write(json_encode([
            'success' => true,
            'id' => $id,
            'message' => 'Shift saved successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
