<?php

arch('controllers')
    ->expect('FratellanzaMilitare\Controller')
    ->not->toUse('FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection')
    ->ignoring([
        'FratellanzaMilitare\Controller\SocioController',
        'FratellanzaMilitare\Controller\LoginController',
        'FratellanzaMilitare\Controller\DevToolsController',
        // NEW: Split DevTools Controllers (use DatabaseConnection for admin operations)
        'FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsFileSystemController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsDatabaseController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsSecurityController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsScriptController',
    ]);
// Example exception if strictly needed, but ideally should be clean.

arch('debug')
    ->expect(['dd', 'dump', 'die', 'var_dump'])
    ->not->toBeUsed();

arch('models')
    ->expect('FratellanzaMilitare\SecurityLayer')
    ->not->toUse('FratellanzaMilitare\Controller');
