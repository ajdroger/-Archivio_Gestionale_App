<?php

use MCAG\InfrastrutturaIT\Persistence\PDOExpensebarRepository;

test('PDOExpensebarRepository can save and retrieve an expense', function () {
    /** @var PDOExpensebarRepository $repo */
    $repo = $this->app->getContainer()->get(PDOExpensebarRepository::class);

    $expenseData = [
        'description' => 'Server Costs',
        'amount' => 150.50,
        'category' => 'Infrastructure',
        'date' => date('Y-m-d')
    ];

    $id = $repo->save($expenseData);
    expect($id)->toBeInt();

    $expenses = $repo->findAll();
    $found = false;
    foreach ($expenses as $exp) {
        if ($exp['id'] == $id && $exp['description'] === 'Server Costs') {
            $found = true;
            break;
        }
    }
    expect($found)->toBeTrue();

    // Cleanup
    $repo->delete($id);
});

test('Expensebar Dashboard loads correctly', function () {
    $this->loginAs();
    $request = $this->createRequest('GET', '/expensebar');
    $response = $this->app->handle($request);
    expect($response->getStatusCode())->toBe(200);
    expect((string) $response->getBody())->toContain('Expensebar Dashboard');
});

test('Expensebar API can add an expense', function () {
    $this->loginAs();
    $request = $this->createRequest('POST', '/expensebar/api/expenses/add')
        ->withHeader('Content-Type', 'application/json')
        ->withParsedBody([
            'description' => 'API Expense Test',
            'amount' => 99.99,
            'category' => 'Test',
            'date' => date('Y-m-d')
        ]);

    $response = $this->app->handle($request);
    expect($response->getStatusCode())->toBe(200);
    $body = json_decode((string) $response->getBody(), true);
    expect($body['status'])->toBe('success');
    expect($body['expense']['id'])->toBeInt();

    // Cleanup
    /** @var PDOExpensebarRepository $repo */
    $repo = $this->app->getContainer()->get(PDOExpensebarRepository::class);
    $repo->delete($body['expense']['id']);
});
