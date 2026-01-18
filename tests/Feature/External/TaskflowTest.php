<?php

use MCAG\InfrastrutturaIT\Persistence\PDOTaskflowRepository;

test('PDOTaskflowRepository can save and retrieve a task', function () {
    /** @var PDOTaskflowRepository $repo */
    $repo = $this->app->getContainer()->get(PDOTaskflowRepository::class);

    $taskData = [
        'text' => 'Complete the mission',
        'completed' => 0
    ];

    $id = $repo->save($taskData);
    expect($id)->toBeInt();

    $tasks = $repo->findAll();
    $found = false;
    foreach ($tasks as $task) {
        if ($task['id'] == $id && $task['text'] === 'Complete the mission') {
            $found = true;
            break;
        }
    }
    expect($found)->toBeTrue();

    // Cleanup
    $repo->delete($id);
});

test('Taskflow Dashboard loads correctly', function () {
    $this->loginAs();
    $request = $this->createRequest('GET', '/taskflow');
    $response = $this->app->handle($request);
    expect($response->getStatusCode())->toBe(200);
    expect((string) $response->getBody())->toContain('Taskflow Pro');
});

test('Taskflow API can create and update a task', function () {
    $this->loginAs();
    // 1. Create
    $request = $this->createRequest('POST', '/taskflow/api/tasks')
        ->withHeader('Content-Type', 'application/json')
        ->withParsedBody([
            'text' => 'API Task Test',
            'completed' => false
        ]);

    $response = $this->app->handle($request);
    expect($response->getStatusCode())->toBe(201);
    $body = json_decode((string) $response->getBody(), true);
    $taskId = $body['task']['id'];
    expect($taskId)->toBeInt();

    // 2. Update
    $requestUpdate = $this->createRequest('PUT', '/taskflow/api/tasks')
        ->withHeader('Content-Type', 'application/json')
        ->withParsedBody([
            'id' => $taskId,
            'completed' => true
        ]);

    $responseUpdate = $this->app->handle($requestUpdate);
    expect($responseUpdate->getStatusCode())->toBe(200);

    // Verify in repo
    /** @var PDOTaskflowRepository $repo */
    $repo = $this->app->getContainer()->get(PDOTaskflowRepository::class);
    $all = $repo->findAll();
    $updatedTask = null;
    foreach ($all as $t) {
        if ($t['id'] == $taskId) {
            $updatedTask = $t;
            break;
        }
    }
    // Note: Database returns boolean columns as 1/0 or true/false depending on driver. 
    // Ensuring truthy check.
    expect((bool) $updatedTask['completed'])->toBeTrue();

    // Cleanup
    $repo->delete($taskId);
});
