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

            // Mock code if missing
            if (empty($emp['employee_code'])) {
                $emp['employee_code'] = 'EMP-' . str_pad((string) $emp['id'], 3, '0', STR_PAD_LEFT);
            }
        }
        unset($emp);

        $data['employees'] = $employees;

        $html = $this->mustache->render('workshift/team-management.mustache', $data);
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

    // === API ENDPOINTS ===

    public function getShifts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $start = $params['start'] ?? date('Y-m-d');
        $end = $params['end'] ?? date('Y-m-d');

        $shifts = $this->repository->findShiftsByRange($start, $end);

        $response->getBody()->write(json_encode(['schedule' => $shifts]));
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
        $scope = $data['scope'] ?? 'day';
        $dateOrStart = $data['date'] ?? $data['start_date'] ?? date('Y-m-d');
        $end = $data['end_date'] ?? null;

        $deleted = $this->repository->deleteAllShifts($scope, $dateOrStart, $end);

        $response->getBody()->write(json_encode(['success' => true, 'deleted' => $deleted]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function optimizeSchedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $mode = $data['mode'] ?? 'current_week';

        // 1. Get Real Employees Only
        $employees = $this->repository->findAllEmployees();

        // STRICT REAL MODE: No Dummies.
        if (empty($employees)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Nessun dipendente trovato nel database. Aggiungi il personale nella sezione "Team" prima di generare i turni.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // 2. Determine Date Range
        $start = new \DateTime();
        if ($mode === 'next_week') {
            $start->modify('+1 week');
        }
        // Align to Monday
        if ($start->format('N') != 1) {
            $start->modify('last monday');
        }

        $shiftsGenerated = 0;
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $shiftTypes = [
            ['type' => 'Morning', 'start' => '08:00', 'end' => '16:00'],
            ['type' => 'Day', 'start' => '09:00', 'end' => '17:00'],
            ['type' => 'Evening', 'start' => '16:00', 'end' => '00:00'],
            // Night shift logic simplifed for same-day storage, usually crosses midnight
            ['type' => 'Night', 'start' => '00:00', 'end' => '08:00']
        ];

        // Clear existing shifts for this week to avoid duplication/clutter (Optional, but cleaner for "Optimize")
        // $this->repository->deleteAllShifts('week', $start->format('Y-m-d'), (clone $start)->modify('+6 days')->format('Y-m-d'));

        // 3. Generate Shifts
        for ($i = 0; $i < 7; $i++) {
            $currentDate = clone $start;
            $currentDate->modify("+$i days");
            $dateStr = $currentDate->format('Y-m-d');
            $dayName = $days[$i];

            // Determine coverage based on headcount
            // If few employees (e.g. 3), schedule 1-2 shifts max per day to avoid burnout
            // If many, schedule more.
            $headCount = count($employees);
            $maxShifts = max(1, min($headCount, 3)); // Max 3 or headcount

            $numShifts = rand(1, $maxShifts);
            $dailyEmployees = $employees;
            shuffle($dailyEmployees);

            for ($j = 0; $j < $numShifts; $j++) {
                if (empty($dailyEmployees))
                    break;

                $emp = array_pop($dailyEmployees);
                $typeConfig = $shiftTypes[array_rand($shiftTypes)];

                $this->repository->save([
                    'employee_id' => $emp['id'],
                    'start_time' => "$dateStr " . $typeConfig['start'] . ":00",
                    'end_time' => "$dateStr " . $typeConfig['end'] . ":00",
                    'type' => $typeConfig['type'],
                    'day' => $dayName,
                    'date' => $dateStr
                ]);
                $shiftsGenerated++;
            }
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'status' => 'optimized',
            'generated' => $shiftsGenerated,
            'message' => "Pianificati $shiftsGenerated turni utilizzando " . count($employees) . " operatori attivi."
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
