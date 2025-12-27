<?php

arch('controllers')
    /** @phpstan-ignore-next-line */
    ->expect('FratellanzaMilitare\Controller')
    ->not->toUse('FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection')
    ->ignoring([
        // NEW: Auth Controllers (direct user table access for security)
        'FratellanzaMilitare\Controller\Auth\LoginFlowController',
        'FratellanzaMilitare\Controller\Auth\TwoFactorController',
        'FratellanzaMilitare\Controller\Auth\LogoutController',
        'FratellanzaMilitare\Controller\SettingsController',
        // NEW: Granular DevTools Controllers (use DatabaseConnection for admin operations)
        'FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsFileSystemController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsDatabaseController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsSecurityController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsScriptController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsSystemController',
        'FratellanzaMilitare\Controller\DevTools\DevToolsAuditController',
    ]);
// Example exception if strictly needed, but ideally should be clean.

arch('debug')
    /** @phpstan-ignore-next-line */
    ->expect(['dd', 'dump', 'die', 'var_dump'])
    ->not->toBeUsed();

arch('models')
    /** @phpstan-ignore-next-line */
    ->expect('FratellanzaMilitare\SecurityLayer')
    ->not->toUse('FratellanzaMilitare\Controller');
