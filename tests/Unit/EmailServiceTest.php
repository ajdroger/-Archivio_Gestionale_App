<?php

use FratellanzaMilitare\Service\FileEmailService;

test('email service writes to log file', function () {
    $logFile = sys_get_temp_dir() . '/test_email_log.txt';
    if (file_exists($logFile)) {
        unlink($logFile);
    }

    $service = new FileEmailService($logFile);
    $result = $service->send('test@example.com', 'Test Subject', 'Test Body');

    expect($result)->toBeTrue();
    expect($logFile)->toBeFile();

    $content = file_get_contents($logFile);
    expect($content)->toContain('TO: test@example.com')
        ->toContain('SUBJECT: Test Subject')
        ->toContain('BODY: Test Body');

    // Cleanup
    unlink($logFile);
});
