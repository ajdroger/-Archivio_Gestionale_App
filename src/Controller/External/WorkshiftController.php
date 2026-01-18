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
            'stats' => $this->getStats()
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
        $data = $this->getCommonData('Gestione Team');

        // Fetch employees
        $employees = $this->repository->findAllEmployees();

        // Decorate for template
        foreach ($employees as &$emp) {
            // Initials for avatar fallback
            $parts = explode(' ', $emp['name']);
            $initials = '';
            foreach ($parts as $part) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
            $emp['user_initial'] = substr($initials, 0, 2);
        }
        unset($emp);

        $data['employees'] = $employees;

        $html = $this->mustache->render('workshift/team-management.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function timeOff(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('workshift/time-off.mustache', $this->getCommonData('Gestione Ferie'));
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
        $page = $args['page'] ?? 'status';
        $allowed = ['hr-policy', 'labor-laws', 'privacy', 'status', 'support', 'terms'];

        if (!in_array($page, $allowed)) {
            $response->getBody()->write($this->mustache->render('404.mustache', $this->getCommonData('Pagina non trovata')));
            return $response->withStatus(404);
        }

        $html = $this->mustache->render("workshift/info/$page.mustache", $this->getCommonData('Informazioni'));
        $response->getBody()->write($html);
        return $response;
    }

    // --- API Methods ---

    public function getShifts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $start = $params['start'] ?? date('Y-m-d');
        $end = $params['end'] ?? date('Y-m-d');

        $shifts = $this->repository->findShiftsByRange($start, $end);

        $response->getBody()->write(json_encode($shifts));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function saveShift(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        // Basic Validation
        if (empty($data['employee_id']) || empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $id = $this->repository->save($data);
            $response->getBody()->write(json_encode(['success' => true, 'id' => $id]));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withStatus(500);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteShift(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $success = $this->repository->delete($id);

        $response->getBody()->write(json_encode(['success' => $success]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function resetShifts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $scope = $data['scope'] ?? 'week'; // 'day' or 'week'
        $start = $data['start'] ?? date('Y-m-d');
        $end = $data['end'] ?? null;

        $count = $this->repository->deleteAllShifts($scope, $start, $end);

        $response->getBody()->write(json_encode(['success' => true, 'deleted' => $count]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function optimizeSchedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Placeholder for AI Optimization Logic
        // In a real scenario, this would call an AI service or algorithm

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Ottimizzazione non ancora implementata (Placeholder)',
            'optimized_count' => 0
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getEmployees(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $employees = $this->repository->findAllEmployees();
        $response->getBody()->write(json_encode($employees));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function saveEmployee(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (empty($data['name']) || empty($data['role'])) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Name and Role are required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Pass all data including codes to repository
        $id = $this->repository->saveEmployee($data);

        $response->getBody()->write(json_encode(['success' => true, 'id' => $id]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteEmployee(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $success = $this->repository->deleteEmployee($id);

        $response->getBody()->write(json_encode(['success' => $success]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function searchCandidates(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $query = $queryParams['q'] ?? '';

        // Allow empty query to fetch "All" (limit handled in Repo)
        $results = $this->repository->findCandidates($query);

        $response->getBody()->write(json_encode($results));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getStats(): array
    {
        // Fetch raw data from repository
        $allShifts = $this->repository->getAllShifts();
        $allEmployees = $this->repository->findAllEmployees();

        // Calculate basic stats
        // Logic: Active shifts = shifts for today. Pending requests = (dummy logic or future implementation)
        $today = date('Y-m-d');
        $activeShifts = array_filter($allShifts, function ($shift) use ($today) {
            return ($shift['date'] ?? '') === $today;
        });

        return [
            'active_shifts' => count($activeShifts),
            'employees_count' => count($allEmployees),
            'pending_requests' => 3, // Mock value as per requirement ("Hai richieste in attesa")
            'upcoming_shifts' => count($allShifts) - count($activeShifts)
        ];
    }
}
