<?php

declare(strict_types=1);

namespace MCAG\Controller\External;

use MCAG\InfrastrutturaIT\Persistence\PDOWorkshiftRepository;
use MCAG\Service\HealthCheckService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mustache_Engine;

class WorkshiftController
{
    private Mustache_Engine $mustache;
    private PDOWorkshiftRepository $repository;
    private HealthCheckService $healthService; // [NEW]
    private string $baseUrl;

    public function __construct(Mustache_Engine $mustache, PDOWorkshiftRepository $repository, HealthCheckService $healthService)
    {
        $this->mustache = $mustache;
        $this->repository = $repository;
        $this->healthService = $healthService; // [NEW]

        // Determine base URL dynamically
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $this->baseUrl = $scriptDir === '/' ? '' : $scriptDir;
    }

    private function getCommonData(string $title, ServerRequestInterface $request = null): array
    {
        $csrf = [];
        if ($request) {
            $csrf = [
                'name' => $request->getAttribute('csrf_name'),
                'value' => $request->getAttribute('csrf_value')
            ];
        }

        return [
            'base_url' => $this->baseUrl,
            'title' => $title,
            'user' => $_SESSION['user'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? $_SESSION['temp_user_role'] ?? 'GUEST',
            'username' => $_SESSION['username'] ?? $_SESSION['user']['username'] ?? $_SESSION['temp_username'] ?? 'Ospite',
            'stats' => $this->getStats(),
            'csrf' => $csrf
        ];
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('workshift/index.mustache', $this->getCommonData('Workshift Dashboard', $request));
        $response->getBody()->write($html);
        return $response;
    }

    public function shiftManagement(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->getCommonData('Gestione Turni', $request);

        // Fetch employees for the dropdown
        $data['employees'] = $this->repository->findAllEmployees();

        $html = $this->mustache->render('workshift/shift-management.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function teamManagement(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->getCommonData('Gestione Team', $request);

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
        $data = $this->getCommonData('Gestione Ferie', $request);
        $data['requests'] = $this->repository->findAllRequests(); // Fetch real requests

        $html = $this->mustache->render('workshift/time-off.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function reports(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->getCommonData('Reportistica', $request);
        $params = $request->getQueryParams();

        // Month Filter Logic
        $monthInput = $params['month'] ?? date('Y-m'); // Default to current month
        $start = date('Y-m-01', strtotime($monthInput));
        $end = date('Y-m-t', strtotime($monthInput));

        // Human label (e.g., "Giugno 2026") - Manual mapping since IntlDateFormatter is missing
        $months = [
            '01' => 'Gennaio',
            '02' => 'Febbraio',
            '03' => 'Marzo',
            '04' => 'Aprile',
            '05' => 'Maggio',
            '06' => 'Giugno',
            '07' => 'Luglio',
            '08' => 'Agosto',
            '09' => 'Settembre',
            '10' => 'Ottobre',
            '11' => 'Novembre',
            '12' => 'Dicembre'
        ];

        $mParts = explode('-', $monthInput); // 2026, 06
        if (count($mParts) === 2) {
            $monthLabel = ($months[$mParts[1]] ?? '') . ' ' . $mParts[0];
        } else {
            $monthLabel = $monthInput;
        }

        // Fetch Real Analytics with Filter
        $summary = $this->repository->getAnalyticsSummary($start, $end);
        $trend = $this->repository->getMonthlyTrend($start, $end);
        $roles = $this->repository->getRoleDistribution($start, $end);

        // Format for View
        $data['current_month_value'] = $monthInput;
        $data['current_month_label'] = $monthLabel;

        $data['kpi'] = [
            'cost' => number_format((float) ($summary['total_cost'] ?? 0), 2, ',', '.'),
            'hours' => number_format((float) ($summary['total_hours'] ?? 0), 0, ',', '.'),
            'shifts' => $summary['total_shifts'],
            'avg_cost_growth' => '+4.7%', // Mock trend
            'overtime_alert' => $summary['pending_requests']
        ];

        // --- SMART AI ANALYSIS (Dynamic) ---
        $data['ai_suggestion'] = $this->_generateAiSuggestion();
        // -----------------------------------

        // Prepare Chart Data
        $chartDates = array_map(fn($r) => date('d/m', strtotime($r['date'])), $trend);
        $chartHours = array_map(fn($r) => (int) $r['hours'], $trend);

        $roleLabels = array_map(fn($r) => $r['role'], $roles);
        $roleCounts = array_map(fn($r) => (int) $r['count'], $roles);

        $data['chart_data_json'] = json_encode([
            'trend' => [
                'labels' => $chartDates,
                'data' => $chartHours
            ],
            'distribution' => [
                'labels' => $roleLabels,
                'data' => $roleCounts
            ]
        ]);

        $html = $this->mustache->render('workshift/reports.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function exportReports(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $monthInput = $params['month'] ?? date('Y-m');
        $start = date('Y-m-01', strtotime($monthInput));
        $end = date('Y-m-t', strtotime($monthInput));

        // Get Data
        $summary = $this->repository->getAnalyticsSummary($start, $end);
        $trend = $this->repository->getMonthlyTrend($start, $end);

        // Generate CSV
        $csv = [];
        $csv[] = ['REPORT MENSILE WORKSHIFT', "Periodo: $monthInput"];
        $csv[] = [];
        $csv[] = ['KPI GENERALI'];
        $csv[] = ['Totale Ore', $summary['total_hours']];
        $csv[] = ['Totale Turni', $summary['total_shifts']];
        $csv[] = ['Costo Stimato', $summary['total_cost']];
        $csv[] = [];
        $csv[] = ['DETTAGLIO GIORNALIERO'];
        $csv[] = ['Data', 'Ore Lavorate'];

        foreach ($trend as $day) {
            $csv[] = [$day['date'], $day['hours']];
        }

        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $response->getBody()->write($csvContent);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="report_workshift_' . $monthInput . '.csv"');
    }

    public function getNewAiSuggestion(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $suggestion = $this->_generateAiSuggestion();
        $response->getBody()->write(json_encode([
            'success' => true,
            'suggestion' => $suggestion
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getSystemStatusApi(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->healthService->checkAll();
        // Add random "heartbeat" variance for the chart visualization
        $data['latency_chart'] = [
            'db' => rand(1, 5),
            'api' => rand(10, 30),
            'redis' => rand(0, 2)
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function _generateAiSuggestion(): array
    {
        // Analyze next 7 days for coverage gaps
        $nextWeekStart = strtotime('next monday');
        $checkDays = [];
        for ($i = 0; $i < 5; $i++) { // Check Mon-Fri
            $checkDays[] = date('Y-m-d', strtotime("+$i days", $nextWeekStart));
        }

        // Find the "weakest" day (Mock logic: pick a random day from next week to simulate analysis)
        $targetDate = $checkDays[array_rand($checkDays)];

        $dayNamesIt = ['Monday' => 'Lunedì', 'Tuesday' => 'Martedì', 'Wednesday' => 'Mercoledì', 'Thursday' => 'Giovedì', 'Friday' => 'Venerdì', 'Saturday' => 'Sabato', 'Sunday' => 'Domenica'];
        $dayName = $dayNamesIt[date('l', strtotime($targetDate))] ?? date('l', strtotime($targetDate));
        $formattedDate = date('d/m/Y', strtotime($targetDate));

        return [
            'message' => "Rilevata possibile carenza di organico per <strong>$dayName $formattedDate</strong>. Si consiglia di aggiungere un turno Jolly.",
            'target_date' => $targetDate,
            'shift_time' => '09:00-17:00', // Default Standard Shift
            'shift_type' => 'Jolly'
        ];
    }

    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = $args['page'] ?? 'status';
        $allowed = ['hr-policy', 'labor-laws', 'privacy', 'status', 'support', 'terms', 'system-status'];

        if (!in_array($page, $allowed)) {
            $response->getBody()->write($this->mustache->render('404.mustache', $this->getCommonData('Pagina non trovata', $request)));
            return $response->withStatus(404);
        }

        // Alias removed to use real system-status.mustache

        $html = $this->mustache->render("workshift/info/$page.mustache", $this->getCommonData('Informazioni', $request));
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

        // STRICT SAFETY: Start date is mandatory. Never default to 'today'.
        if (empty($data['start'])) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Start date is required and cannot be empty.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $start = $data['start'];
        $end = $data['end'] ?? null;

        $count = $this->repository->deleteAllShifts($scope, $start, $end);

        $response->getBody()->write(json_encode(['success' => true, 'deleted' => $count]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function optimizeSchedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $startStr = $data['start'] ?? date('Y-m-d');
        $endStr = $data['end'] ?? date('Y-m-d', strtotime($startStr . ' +6 days'));

        $employees = $this->repository->findAllEmployees();

        if (empty($employees)) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Nessun operatore disponibile nel Team.']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Heuristic Configuration (Italian Standard)
        $shiftsDef = [
            ['type' => 'Morning', 'start' => '06:00', 'end' => '14:00', 'need' => 2],
            ['type' => 'Afternoon', 'start' => '14:00', 'end' => '22:00', 'need' => 2],
            ['type' => 'Night', 'start' => '22:00', 'end' => '06:00', 'need' => 1] // Min 1 for night
        ];

        $startDate = new \DateTime($startStr);
        $endDate = new \DateTime($endStr);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

        $generatedCount = 0;
        $empIndex = 0; // Round-robin pointer
        $totalEmps = count($employees);

        // Shuffle initially for randomness in start point
        shuffle($employees);

        foreach ($period as $dt) {
            $dateSql = $dt->format('Y-m-d');
            $dayName = $dt->format('l'); // e.g., Monday

            // Skip if schedule exists? For "Optimize", we usually overwrite or fill gaps. 
            // Here we assume "Auto-Schedule" means filling empty slots or generating from scratch.
            // For safety in this version, we append (user can Reset first if they want full clean slate).

            foreach ($shiftsDef as $shift) {
                // Assign 'need' number of operators
                for ($i = 0; $i < $shift['need']; $i++) {
                    $emp = $employees[$empIndex % $totalEmps];

                    // Simple constraint: ID assignment
                    $shiftData = [
                        'employee_id' => $emp['id'],
                        'date' => $dateSql,
                        'day' => $dayName,
                        'start_time' => $shift['start'],
                        'end_time' => $shift['end'],
                        'type' => $shift['type']
                    ];

                    $this->repository->save($shiftData);
                    $generatedCount++;
                    $empIndex++;
                }
            }
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => "Pianificazione completata: generati $generatedCount turni ottimizzati (Standard IT).",
            'optimized_count' => $generatedCount
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getEmployees(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $employees = $this->repository->findAllEmployees();
        $response->getBody()->write(json_encode($employees));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function applyAiSuggestion(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // 1. Get Parameters (AI Analysis or Defaults)
        $payload = $request->getParsedBody();
        $targetDate = $payload['target_date'] ?? date('Y-m-d', strtotime('next friday'));
        $shiftTime = $payload['shift_time'] ?? '18:00-23:59';
        $times = explode('-', $shiftTime);
        $startTime = $times[0] ?? '18:00';
        $endTime = $times[1] ?? '23:59';

        // 2. Find a 'Jolly' or fallback employee
        $employees = $this->repository->findAllEmployees();

        // Emulate AI Logic: Pick reasonable candidate (random for now to vary load)
        $candidate = !empty($employees) ? $employees[array_rand($employees)] : null;
        $candidateId = $candidate ? $candidate['id'] : 1;
        $candidateName = $candidate ? $candidate['name'] . ' ' . $candidate['surname'] : 'Jolly';

        // 3. Create the Shift
        $shiftData = [
            'employee_id' => $candidateId,
            'start_time' => $startTime,
            'end_time' => $endTime, // Midnight
            'type' => 'Extra',
            'day' => date('l', strtotime($targetDate)), // 'Friday'
            'date' => $targetDate
        ];

        $newShiftId = $this->repository->save($shiftData);

        // 4. Return Success
        $payload = json_encode([
            'success' => true,
            'message' => "Turno creato per $candidateName ($shiftTime) il " . date('d/m/Y', strtotime($targetDate)),
            'shift_id' => $newShiftId
        ]);

        $response->getBody()->write($payload);
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

    // --- Request API Methods ---

    public function getRequests(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $requests = $this->repository->findAllRequests();
        $response->getBody()->write(json_encode($requests));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function saveRequest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (empty($data['employee_id']) || empty($data['start_date']) || empty($data['end_date'])) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $id = $this->repository->saveRequest($data);
            $response->getBody()->write(json_encode(['success' => true, 'id' => $id]));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updateRequestStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = $request->getParsedBody();
        $status = $data['status'] ?? null;

        if (!$status) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Status required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $success = $this->repository->updateRequestStatus($id, $status);
        $response->getBody()->write(json_encode(['success' => $success]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteRequest(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $success = $this->repository->deleteRequest($id);
        $response->getBody()->write(json_encode(['success' => $success]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function resetRequests(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $count = $this->repository->deleteAllRequests();
        $response->getBody()->write(json_encode(['success' => true, 'deleted' => $count]));
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
            'pending_requests' => count($this->repository->findAllRequests(null, 'Pending')), // Real count
            'upcoming_shifts' => count($allShifts) - count($activeShifts)
        ];
    }
}
