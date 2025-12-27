<?php

test('logs directory is writable', function () {
    $path = __DIR__ . '/../../logs';
    expect(is_dir($path))->toBeTrue();
    expect(is_writable($path))->toBeTrue();
});

test('backups directory is writable', function () {
    $path = __DIR__ . '/../../storage/backups';
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    expect(is_dir($path))->toBeTrue();
    expect(is_writable($path))->toBeTrue();
});

test('temp directory is writable', function () {
    $path = sys_get_temp_dir();
    expect(is_writable($path))->toBeTrue();
});
