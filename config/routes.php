<?php

use Slim\App;
use MCAG\Controller\LoginController;
use MCAG\Controller\HomeController;
use MCAG\Controller\SocioController;
use MCAG\Controller\StatisticsController;
use MCAG\Controller\SettingsController;
use MCAG\Controller\Auth\LoginFlowController;
use MCAG\Controller\Auth\TwoFactorController;
use MCAG\Controller\Auth\LogoutController;
use MCAG\Controller\Anagrafica\Soci\ListController as SocioList;
use MCAG\Controller\Anagrafica\Soci\DetailController as SocioDetail;
use MCAG\Controller\Anagrafica\Soci\PersistenceController as SocioPersistence;
use MCAG\Controller\Anagrafica\Soci\ActionController as SocioAction;
use MCAG\Controller\Anagrafica\Soci\SettingsController as SocioSettings;
use MCAG\Controller\Anagrafica\Documenti\StorageController as SocioStorage;
use MCAG\Controller\Anagrafica\Servizi\SocioExportController as SocioExport;
use MCAG\Controller\Intelligence\StatsDashboardController as StatsDashboard;
use MCAG\Controller\Intelligence\ReportExportController as StatsExport;
use MCAG\Controller\DevTools\DevToolsDashboardController;
use MCAG\Controller\DevTools\DevToolsFileSystemController;
use MCAG\Controller\DevTools\DevToolsDatabaseController;
use MCAG\Controller\DevTools\DevToolsSecurityController;
use MCAG\Controller\DevTools\DevToolsScriptController;
use MCAG\Controller\AI\AssistantController;
use MCAG\Middleware\AdminMiddleware;
use MCAG\Middleware\RateLimitMiddleware;
use MCAG\Middleware\RoleMiddleware;

return function (App $app) {
    // Error Handling
    $errorMiddleware = $app->addErrorMiddleware(true, true, true);

    // Auth Routes
    $loginLimit = new RateLimitMiddleware(100, 60);
    $app->get('/login', LoginFlowController::class . ':form')->setName('login')->add($loginLimit);
    $app->post('/login', LoginFlowController::class . ':verifyCredentials')->setName('login_verify')->add($loginLimit);
    $app->get('/login/2fa', TwoFactorController::class . ':form')->setName('login_2fa')->add($loginLimit);
    $app->post('/login/2fa', TwoFactorController::class . ':verify')->setName('login_2fa_verify')->add($loginLimit);
    $app->get('/logout', LogoutController::class . ':logout')->setName('logout');

    // Demo Mode
    $app->map(['GET', 'POST'], '/auth/start-demo', \MCAG\Controller\Auth\DemoModeController::class . ':startDemo')->setName('demo_launch');

    // Demo Request Public API
    $app->post('/api/public/demo-request', \MCAG\Controller\Public\DemoRequestController::class . ':submit')->setName('demo_request_submit');

    // Main
    $app->get('/', HomeController::class . ':dashboard')->setName('dashboard');

    // Public Registration (Modulo Iscrizione Esterno)
    $app->get('/iscrizione', SocioPersistence::class . ':publicForm')->setName('public_registration_form');
    $app->post('/iscrizione', SocioPersistence::class . ':publicStore')->setName('public_registration_store');

    // Anagrafica - Soci (Read-Only - SOLO LISTA, detail dopo le route specifiche)
    $app->get('/soci', SocioList::class . ':list')->setName('socio_list');

    // Anagrafica - Soci (Write) - SPECIFICHE PRIMA DI {cf}
    $writeRole = new RoleMiddleware(['segreteria', 'segreteria_soci', 'direttore_associazione']);
    $app->group('/soci', function ($group) {
        // ROUTES SPECIFICHE PRIMA
        $group->get('/nuovo', SocioPersistence::class . ':create')->setName('socio_create');
        $group->post('/salva', SocioPersistence::class . ':store')->setName('socio_store');
        $group->post('/calcola-cf', SocioAction::class . ':calculateFiscalCode')->setName('socio_calc_cf')->add(new RateLimitMiddleware(10, 60));

        // ROUTES GENERICHE DOPO
        $group->get('/{cf}/edit', SocioPersistence::class . ':edit')->setName('socio_edit');
        $group->get('/{cf}/impostazioni', SocioSettings::class . ':view')->setName('socio_settings'); // [NEW] Page Settings
        $group->post('/{cf}/aggiorna', SocioPersistence::class . ':update')->setName('socio_update');
        $group->post('/{cf}/elimina', SocioPersistence::class . ':delete')->setName('socio_delete');

        // Documenti
        $group->post('/{cf}/upload', SocioStorage::class . ':upload')->setName('socio_upload_doc');
        $group->post('/{cf}/documenti/{id}/elimina', SocioStorage::class . ':delete')->setName('socio_delete_doc');
    })->add($writeRole);

    // Route dettaglio DOPO il gruppo (così non interferisce con /nuovo)
    $app->get('/soci/{cf}', SocioDetail::class . ':detail')->setName('socio_detail');

    // Servizi & Export
    $app->get('/soci/{cf}/documenti/{id}/download', SocioStorage::class . ':download')->setName('socio_download_doc');
    $app->get('/soci/export/csv', SocioExport::class . ':exportCsv')->setName('socio_export')->add(new RateLimitMiddleware(30, 60));

    // Compliance & Privacy
    $app->get('/privacy-policy', \MCAG\Controller\PolicyController::class . ':privacy')->setName('privacy_policy');
    $app->get('/cookie-policy', \MCAG\Controller\PolicyController::class . ':cookie')->setName('cookie_policy');
    $app->get('/terms-of-service', \MCAG\Controller\PolicyController::class . ':terms')->setName('terms_of_service');

    // Intelligence (Stats & Reports)
    $statsRole = new RoleMiddleware(['admin', 'presidente', 'segreteria', 'segreteria_soci', 'Segreteria_Soci', 'direttore_associazione', 'collegio_sindacale', 'ente_universita', 'ente_sanitario', 'ente_pubblico', 'user']);
    $exportLimit = new RateLimitMiddleware(30, 60);

    // AI Assistant (Placed here to access $statsRole)
    $app->group('/ai', function ($group) {
        $group->get('/assistant', AssistantController::class . ':chatWindow')->setName('ai_assistant_window');
        $group->post('/assistant/message', AssistantController::class . ':message')->setName('ai_assistant_message');
        $group->post('/assistant/upload', AssistantController::class . ':uploadDocument')->setName('ai_assistant_upload');
    })->add($statsRole);

    $app->group('/statistiche', function ($group) use ($exportLimit) {
        $group->get('', StatsDashboard::class . ':view')->setName('statistics');
        $group->get('/export/pdf', StatsExport::class . ':exportPdf')->setName('statistics_pdf')->add($exportLimit);
        $group->get('/export/excel', StatsExport::class . ':exportExcel')->setName('statistics_excel')->add($exportLimit);
    })->add($statsRole);

    // API Layer
    // Stricter Rate Limit for API: 60 req/min
    $container = $app->getContainer();
    $redis = $container->has(\MCAG\Service\RedisService::class) ? $container->get(\MCAG\Service\RedisService::class) : null;
    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    $apiLimit = new RateLimitMiddleware(60, 60, $redis, $logger);


    // Workshift
    $app->get('/workshift', \MCAG\Controller\External\WorkshiftController::class . ':index')->setName('workshift_dashboard');
    $app->get('/workshift/dashboard', \MCAG\Controller\External\WorkshiftController::class . ':index')->setName('workshift_home'); // Alias
    $app->get('/workshift/shift-management', \MCAG\Controller\External\WorkshiftController::class . ':shiftManagement')->setName('workshift_shifts');
    $app->get('/workshift/team-management', \MCAG\Controller\External\WorkshiftController::class . ':teamManagement')->setName('workshift_team');
    $app->get('/workshift/time-off', \MCAG\Controller\External\WorkshiftController::class . ':timeOff')->setName('workshift_timeoff');
    $app->get('/workshift/reports', \MCAG\Controller\External\WorkshiftController::class . ':reports')->setName('workshift_reports');
    $app->get('/workshift/info/{page}', \MCAG\Controller\External\WorkshiftController::class . ':info')->setName('workshift_info');
    $app->post('/workshift/api/shifts/save', \MCAG\Controller\External\WorkshiftController::class . ':saveShift')->setName('workshift_save');

    $app->group('/api/v1', function ($group) {
        $group->get('/soci', \MCAG\Controller\SociApiController::class . ':list');
        $group->get('/soci/{cf}', \MCAG\Controller\SociApiController::class . ':get');
        $group->post('/soci', \MCAG\Controller\SociApiController::class . ':create');
    })
        ->add(new \MCAG\Middleware\ApiKeyMiddleware(
            $container->get(PDO::class),
            $container->get(\MCAG\SecurityLayer\AuditTrail::class)
        ))
        ->add($apiLimit);
    // ->add(new \MCAG\Middleware\JwtAuthMiddleware()); // TODO: Enable when Auth0 is ready

    // GraphQL API
    $app->post('/api/graphql', \MCAG\Controller\GraphQLController::class . ':handle')->setName('graphql_api');
    // ->add(...) // Disabled for testing connectivity


    // API Documentation
    $app->get('/api/docs', \MCAG\Controller\Docs\DocumentationController::class . ':ui')->setName('api_docs');
    $app->get('/api/docs/json', \MCAG\Controller\Docs\DocumentationController::class . ':spec')->setName('api_docs_json');

    // Admin & DevTools
    $app->group('', function ($group) {
        $group->get('/impostazioni', SettingsController::class . ':view')->setName('settings');
        $group->post('/impostazioni', SettingsController::class . ':updatePassword')->setName('settings_update');
        $group->get('/devtools', DevToolsDashboardController::class . ':dashboard')->setName('devtools');
        $group->post('/devtools/run', DevToolsScriptController::class . ':runScript')->setName('devtools_run');
        $group->post('/devtools/trace', DevToolsScriptController::class . ':logTrace')->setName('devtools_trace');
        $group->post('/devtools/fs/list', DevToolsFileSystemController::class . ':fsList')->setName('devtools_fs_list');
        $group->post('/devtools/fs/read', DevToolsFileSystemController::class . ':fsRead')->setName('devtools_fs_read');
        $group->post('/devtools/fs/save', DevToolsFileSystemController::class . ':fsSave')->setName('devtools_fs_save');
        $group->post('/devtools/db/query', DevToolsDatabaseController::class . ':dbQuery')->setName('devtools_db_query');
        $group->post('/devtools/security/list', DevToolsSecurityController::class . ':securityList')->setName('devtools_sec_list');
        $group->post('/devtools/security/add', DevToolsSecurityController::class . ':securityAdd')->setName('devtools_sec_add');
        $group->post('/devtools/security/reset', DevToolsSecurityController::class . ':securityReset')->setName('devtools_sec_reset');
        $group->post('/devtools/security/rotate', DevToolsSecurityController::class . ':securityRotate2FA')->setName('devtools_sec_rotate');
        $group->post('/devtools/security/delete', DevToolsSecurityController::class . ':securityDelete')->setName('devtools_sec_delete');
        $group->post('/devtools/terminal', DevToolsScriptController::class . ':terminal')->setName('devtools_terminal');
        $group->get('/devtools/export/audit/pdf', DevToolsDatabaseController::class . ':exportAuditPdf')->setName('devtools_audit_pdf');
        $group->get('/devtools/export/audit/excel', DevToolsDatabaseController::class . ':exportAuditExcel')->setName('devtools_audit_excel');
        $group->post('/devtools/audit/list', DevToolsDashboardController::class . ':auditAjax')->setName('devtools_audit_list');
        $group->post('/devtools/demo-invite', DevToolsDashboardController::class . ':handleDemoInvite')->setName('devtools_demo_invite'); // [NEW] Demo Invite Action
        $group->post('/devtools/alive', DevToolsDashboardController::class . ':heartbeat')->setName('devtools_alive');
    })->add(new AdminMiddleware());

};


