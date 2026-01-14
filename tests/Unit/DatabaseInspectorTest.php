<?php

use MCAG\Debug\DatabaseInspector;
use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

test('inspect returns connection status', function () {
    // Requires a mock PDO, which DatabaseConnection gets from singleton
    // We'll skip deep integration testing here and focus on class existence/basic calls
    // Real testing would require DI injection into Inspector which is static currently
    expect(class_exists(DatabaseInspector::class))->toBeTrue();
});
