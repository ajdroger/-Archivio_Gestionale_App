<?php

use MCAG\InfrastrutturaIT\Persistence\PDOWorkshiftRepository;
use MCAG\Controller\External\WorkshiftController;

test('PDOWorkshiftRepository can save and retrieve an employee', function () {
    /** @var PDOWorkshiftRepository $repo */
    $repo = $this->app->getContainer()->get(PDOWorkshiftRepository::class);

    $employeeData = [
        'name' => 'John Wick',
        'role' => 'Specialist',
        'department' => 'Security',
        'email' => 'jw@continental.com'
    ];

    $id = $repo->saveEmployee($employeeData);
    expect($id)->toBeInt();

    $employees = $repo->findAllEmployees();
    $found = false;
    foreach ($employees as $emp) {
        if ($emp['name'] === 'John Wick') {
            $found = true;
            break;
        }
    }
    expect($found)->toBeTrue();

    // Cleanup
    $repo->deleteEmployee($id);
});

test('Workshift Dashboard loads correctly', function () {
    $request = $this->createRequest('GET', '/workshift');
    // Mock session if needed, but the controller handles missing session gracefully

    $response = $this->app->handle($request);
    expect($response->getStatusCode())->toBe(200);
    expect((string) $response->getBody())->toContain('Workshift Dashboard');
});

test('Workshift API can create a shift', function () {
    $this->loginAs();
    /** @var PDOWorkshiftRepository $repo */
    $repo = $this->app->getContainer()->get(PDOWorkshiftRepository::class);

    // Create a temp employee first
    $empId = $repo->saveEmployee(['name' => 'Shift Tester', 'role' => 'Temp', 'department' => 'Test', 'email' => 'test@test.com']);

    $request = $this->createRequest('POST', '/workshift/api/shifts/save')
        ->withHeader('Content-Type', 'application/json')
        ->withParsedBody([
            'employee_id' => $empId,
            'start_time' => date('Y-m-d H:i:s'),
            'end_time' => date('Y-m-d H:i:s', strtotime('+8 hours')),
            'type' => 'Morning',
            'day' => date('l'), // Current day name
            'date' => date('Y-m-d')
        ]);

    $response = $this->app->handle($request);
    expect($response->getStatusCode())->toBe(200);
    $body = json_decode((string) $response->getBody(), true);
    expect($body['success'])->toBeTrue();

    // Verify persistence
    $shifts = $repo->getAllShifts();
    expect(count($shifts))->toBeGreaterThan(0);

    // Cleanup
    $repo->deleteEmployee($empId);
    // Ideally delete shift too, but we haven't implemented deleteShift in repo test cleanup yet
    // Assuming test DB is reset or transaction rolled back if configured, or explicit cleanup:
    // $repo->deleteShift($body['id']); // If implemented
});
