<?php

arch('controllers')
    /** @phpstan-ignore-next-line */
    ->expect('MCAG\Controller')
    ->not->toUse('MCAG\InfrastrutturaIT\Persistence\DatabaseConnection')
    ->ignoring([
        // NEW: Auth Controllers (direct user table access for security)
        'MCAG\Controller\Auth\LoginFlowController',
        'MCAG\Controller\Auth\TwoFactorController',
        'MCAG\Controller\Auth\LogoutController',
        'MCAG\Controller\SettingsController',
        // NEW: Granular DevTools Controllers (use DatabaseConnection for admin operations)
        'MCAG\Controller\DevTools\DevToolsDashboardController',
        'MCAG\Controller\DevTools\DevToolsFileSystemController',
        'MCAG\Controller\DevTools\DevToolsDatabaseController',
        'MCAG\Controller\DevTools\DevToolsSecurityController',
        'MCAG\Controller\DevTools\DevToolsScriptController',
        'MCAG\Controller\DevTools\DevToolsSystemController',
        'MCAG\Controller\DevTools\DevToolsAuditController',
    ]);
// Example exception if strictly needed, but ideally should be clean.

arch('debug')
    /** @phpstan-ignore-next-line */
    ->expect(['dd', 'dump', 'die', 'var_dump'])
    ->not->toBeUsed();

arch('models')
    /** @phpstan-ignore-next-line */
    ->expect('MCAG\SecurityLayer')
    ->not->toUse('MCAG\Controller');

