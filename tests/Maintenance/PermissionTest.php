<?php

test('logs directory is writable', function () {
    $path = __DIR__ . '/../../logs';
    expect(is_dir($path))->toBeTrue();
    expect(is_writable($path))->toBeTrue();
});

test('database file is writable', function () {
    $path = __DIR__ . '/../../database.sqlite';
    if (file_exists($path)) {
        expect(is_writable($path))->toBeTrue();
    } else {
        test()->skip('Database file not found');
    }
});

test('temp directory is writable', function () {
    $path = sys_get_temp_dir();
    expect(is_writable($path))->toBeTrue();
});
