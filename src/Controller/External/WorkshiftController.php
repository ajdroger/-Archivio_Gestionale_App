<?php

declare(strict_types=1);

namespace MCAG\Controller\External;

use MCAG\InfrastrutturaIT\Persistence\PDOWorkshiftRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WorkshiftController
{
    private \Mustache_Engine $mustache;
    private PDOWorkshiftRepository $repository;
    private string $baseUrl;

    public function __construct(\Mustache_Engine $mustache, PDOWorkshiftRepository $repository)
    {
        $this->mustache = $mustache;
        $this->repository = $repository;

        // Determine base URL
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
            'user_initial' => isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : (isset($_SESSION['user']['username']) ? strtoupper(substr($_SESSION['user']['username'], 0, 1)) : 'U'),
            'username' => $_SESSION['username'] ?? ($_SESSION['user']['username'] ?? ($_SESSION['user'] ?? 'Ospite')),
            'is_immersive' => true,
            'body_class' => 'bg-mcag-slate h-screen',
            'extra_css' => [
                $this->baseUrl . '/css/tailwind-external.css',
                $this->baseUrl . '/assets/workshift/style.css',
                $this->baseUrl . '/assets/workshift/premium.css'
            ]
        ];
    }

    // --- Views ---

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->getCommonData('Workshift Dashboard');

        // Fetch real stats
        $data['stats'] = $this->repository->getWorkshiftStats();

        $html = $this->mustache->render('workshift/index.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function shiftManagement(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->getCommonData('Gestione Turni - Workshift');
        $data['employees'] = $this->repository->findAllEmployees(); // For dropdowns

        $html = $this->mustache->render('workshift/shift-management.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function teamManagement(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->getCommonData('Gestione Team - Workshift');
        $data['employees'] = $this->repository->findAllEmployees();

        $html = $this->mustache->render('workshift/team-management.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function showProfile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = $this->getCommonData('Profilo Operatore - Workshift');

        // Find employee by ID
        $employees = $this->repository->findAllEmployees();
        $employee = null;
        foreach ($employees as $emp) {
            if ($emp['id'] === $id) {
                $employee = $emp;
                break;
            }
        }

        if (!$employee) {
            $response->getBody()->write("Operatore non trovato");
            return $response->withStatus(404);
        }

        $data['employee'] = $employee;
        $html = $this->mustache->render('workshift/operator-profile.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function timeOff(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->getCommonData('Permessi - Workshift');
        $data['requests'] = $this->repository->getRequests();

        $html = $this->mustache->render('workshift/time-off.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    public function reports(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('workshift/reports.mustache', $this->getCommonData('Report - Workshift'));
        $response->getBody()->write($html);
        return $response;
    }

    public function settings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->getCommonData('Impostazioni - Workshift');
        $data['settings'] = $this->repository->getSettings();

        $html = $this->mustache->render('workshift/settings.mustache', $data);
        $response->getBody()->write($html);
        return $response;
    }

    // --- API Endpoints ---

    public function getSchedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = [
            'schedule' => $this->repository->getAllShifts(),
            'employees' => $this->repository->findAllEmployees()
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function saveShift(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        // Validation would be here
        $id = $this->repository->saveShift($data);

        $response->getBody()->write(json_encode(['success' => true, 'id' => $id]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function saveEmployee(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();

            if (!$data) {
                throw new \Exception("Invalid JSON payload or empty body.");
            }

            $id = $this->repository->saveEmployee($data);
            $response->getBody()->write(json_encode(['success' => true, 'id' => $id]));
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function searchCandidates(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $query = $queryParams['q'] ?? '';

        $candidates = $this->repository->searchEmployees($query);

        // Map fields if necessary, though repository returns matching columns
        // ensuring 'source' is set for UI consistency
        $mapped = array_map(function ($c) {
            $c['source'] = 'Database'; // Indicate these are real records
            return $c;
        }, $candidates);

        $response->getBody()->write(json_encode(array_values($mapped)));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getStats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $stats = $this->repository->getWorkshiftStats();
        $response->getBody()->write(json_encode($stats));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getReportsData(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->repository->getReportData();
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteEmployee(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = (int) $request->getAttribute('id'); // Assumes route /api/employees/{id}/delete or similar, handled via parsed body if POST
        $data = $request->getParsedBody();
        if (!$id && isset($data['id'])) {
            $id = (int) $data['id'];
        }

        $success = $this->repository->deleteEmployee($id);
        $response->getBody()->write(json_encode(['success' => $success]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteShift(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $id = isset($data['id']) ? (int) $data['id'] : 0;

        if ($id > 0) {
            $success = $this->repository->deleteShift($id);
            $response->getBody()->write(json_encode(['success' => $success]));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Invalid ID']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function resetSchedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $scope = $data['scope'] ?? 'week'; // 'week' or 'day'
        $deletedCount = 0;

        if ($scope === 'day') {
            $date = $data['date'] ?? null;
            if (!$date)
                return $response->withStatus(400);
            $deletedCount = $this->repository->deleteShiftsByDate($date);
        } else {
            // Week scope
            $start = $data['start_date'] ?? null;
            $end = $data['end_date'] ?? null;
            if (!$start || !$end)
                return $response->withStatus(400);
            $deletedCount = $this->repository->deleteShiftsByRange($start, $end);
        }

        $response->getBody()->write(json_encode(['success' => true, 'deleted' => $deletedCount, 'debug_scope' => $scope]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // AI Optimizer
    public function optimizeSchedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $employees = $this->repository->findAllEmployees();
        $currentSchedule = $this->repository->getSchedule(); // Retrieve existing shifts to avoid conflict

        $payload = [
            'employees' => $employees,
            'current_schedule' => $currentSchedule
        ];

        $jsonData = json_encode($payload);

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];

        $pythonScript = __DIR__ . '/../../../bin/python/schedule_optimizer.py';
        $cmd = "python \"{$pythonScript}\"";

        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], $jsonData);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            proc_close($process);

            if ($output) {
                // Save the optimized schedule to DB
                $result = json_decode($output, true);
                if (isset($result['schedule'])) {
                    // Python returns: { "Monday": { "Morning": ["emp_id_1", "emp_id_2"] } }
                    // We need to flatten this to save individual shifts
                    foreach ($result['schedule'] as $day => $shiftsByType) {
                        foreach ($shiftsByType as $type => $employeeIds) {
                            foreach ($employeeIds as $empId) {
                                // Simple time mapping
                                $start = '08:00';
                                $end = '16:00';
                                switch ($type) {
                                    case 'Morning':
                                        $start = '08:00';
                                        $end = '16:00';
                                        break;
                                    case 'Day':
                                        $start = '09:00';
                                        $end = '17:00';
                                        break;
                                    case 'Evening':
                                        $start = '16:00';
                                        $end = '00:00';
                                        break;
                                    case 'Night':
                                        $start = '00:00';
                                        $end = '08:00';
                                        break;
                                }

                                // Robust current week calculation
                                $today = new \DateTime();
                                // Ensure we align with "current week" starting Monday
                                $monday = clone $today;
                                if ($today->format('N') != 1) {
                                    $monday->modify('last Monday');
                                }

                                $dayOffsets = [
                                    'Monday' => 0,
                                    'Tuesday' => 1,
                                    'Wednesday' => 2,
                                    'Thursday' => 3,
                                    'Friday' => 4,
                                    'Saturday' => 5,
                                    'Sunday' => 6
                                ];

                                $offset = $dayOffsets[$day] ?? 0;
                                $targetDate = clone $monday;
                                $targetDate->modify("+$offset days");
                                $dateStr = $targetDate->format('Y-m-d');

                                $shiftData = [
                                    'employee_id' => $empId, // The Python script might return IDs or Names. It returns ID if provided, else "emp_X".
                                    // If "emp_X", we validly cannot save it if it's not in DB.
                                    // Assuming Python gets real IDs passed to it.
                                    'type' => $type,
                                    'day' => $day,
                                    'date' => $dateStr,
                                    'start_time' => "$dateStr $start:00",
                                    'end_time' => "$dateStr $end:00"
                                ];

                                // We might need to handle mock IDs if the input was empty/mocked
                                if (is_numeric($empId) || (is_string($empId) && !str_starts_with($empId, 'emp_'))) {
                                    $this->repository->saveShift($shiftData);
                                }
                            }
                        }
                    }
                }
                $response->getBody()->write($output);
            } else {
                $response->getBody()->write(json_encode(['error' => 'No output from AI optimizer']));
            }
        } else {
            $response->getBody()->write(json_encode(['error' => 'Failed to launch AI optimizer']));
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
}
