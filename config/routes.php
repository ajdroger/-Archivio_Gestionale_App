<?php

use Slim\App;
use FratellanzaMilitare\Controller\LoginController;
use FratellanzaMilitare\Controller\HomeController;
use FratellanzaMilitare\Controller\SocioController;
use FratellanzaMilitare\Controller\StatisticsController;
use FratellanzaMilitare\Controller\SettingsController;
use FratellanzaMilitare\Controller\Auth\LoginFlowController;
use FratellanzaMilitare\Controller\Auth\TwoFactorController;
use FratellanzaMilitare\Controller\Auth\LogoutController;
use FratellanzaMilitare\Controller\Anagrafica\Soci\ListController as SocioList;
use FratellanzaMilitare\Controller\Anagrafica\Soci\DetailController as SocioDetail;
use FratellanzaMilitare\Controller\Anagrafica\Soci\PersistenceController as SocioPersistence;
use FratellanzaMilitare\Controller\Anagrafica\Soci\ActionController as SocioAction;
use FratellanzaMilitare\Controller\Anagrafica\Documenti\StorageController as SocioStorage;
use FratellanzaMilitare\Controller\Anagrafica\Servizi\SocioExportController as SocioExport;
use FratellanzaMilitare\Controller\Intelligence\StatsDashboardController as StatsDashboard;
use FratellanzaMilitare\Controller\Intelligence\ReportExportController as StatsExport;
use FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController;
use FratellanzaMilitare\Controller\DevTools\DevToolsFileSystemController;
use FratellanzaMilitare\Controller\DevTools\DevToolsDatabaseController;
use FratellanzaMilitare\Controller\DevTools\DevToolsSecurityController;
use FratellanzaMilitare\Controller\DevTools\DevToolsScriptController;
use FratellanzaMilitare\Middleware\AdminMiddleware;
use FratellanzaMilitare\Middleware\RateLimitMiddleware;
use FratellanzaMilitare\Middleware\RoleMiddleware;

return function (App $app) {
    // Auth Routes
    $loginLimit = new RateLimitMiddleware(5, 60);
    $app->get('/login', LoginFlowController::class . ':form')->setName('login')->add($loginLimit);
    $app->post('/login', LoginFlowController::class . ':verifyCredentials')->setName('login_verify')->add($loginLimit);
    $app->get('/login/2fa', TwoFactorController::class . ':form')->setName('login_2fa')->add($loginLimit);
    $app->post('/login/2fa', TwoFactorController::class . ':verify')->setName('login_2fa_verify')->add($loginLimit);
    $app->get('/logout', LogoutController::class . ':logout')->setName('logout');

    // Demo Mode
    $app->map(['GET', 'POST'], '/auth/start-demo', \FratellanzaMilitare\Controller\Auth\DemoModeController::class . ':startDemo')->setName('demo_launch');

    // Demo Request Public API
    $app->post('/api/public/demo-request', \FratellanzaMilitare\Controller\Public\DemoRequestController::class . ':submit')->setName('demo_request_submit');

    // Main
    $app->get('/', HomeController::class . ':dashboard')->setName('dashboard');

    // Anagrafica - Soci (Read-Only - SOLO LISTA, detail dopo le route specifiche)
    $app->get('/soci', SocioList::class . ':list')->setName('socio_list');

    // Anagrafica - Soci (Write) - SPECIFICHE PRIMA DI {cf}
    $writeRole = new RoleMiddleware(['segreteria', 'direttore_associazione']);
    $app->group('/soci', function ($group) {
        // ROUTES SPECIFICHE PRIMA
        $group->get('/nuovo', SocioPersistence::class . ':create')->setName('socio_create');
        $group->post('/salva', SocioPersistence::class . ':store')->setName('socio_store');
        $group->post('/calcola-cf', SocioAction::class . ':calculateFiscalCode')->setName('socio_calc_cf')->add(new RateLimitMiddleware(10, 60));

        // ROUTES GENERICHE DOPO
        $group->get('/{cf}/modifica', SocioPersistence::class . ':edit')->setName('socio_edit');
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
    $app->get('/privacy-policy', \FratellanzaMilitare\Controller\PolicyController::class . ':privacy')->setName('privacy_policy');
    $app->get('/cookie-policy', \FratellanzaMilitare\Controller\PolicyController::class . ':cookie')->setName('cookie_policy');

    // Intelligence (Stats & Reports)
    $statsRole = new RoleMiddleware(['presidente', 'segreteria', 'direttore_associazione', 'collegio_sindacale', 'ente_universita', 'ente_sanitario', 'ente_pubblico']);
    $exportLimit = new RateLimitMiddleware(30, 60);

    $app->group('/statistiche', function ($group) use ($exportLimit) {
        $group->get('', StatsDashboard::class . ':view')->setName('statistics');
        $group->get('/export/pdf', StatsExport::class . ':exportPdf')->setName('statistics_pdf')->add($exportLimit);
        $group->get('/export/excel', StatsExport::class . ':exportExcel')->setName('statistics_excel')->add($exportLimit);
    })->add($statsRole);

    // API Layer
    // Stricter Rate Limit for API: 60 req/min
    $container = $app->getContainer();
    $redis = $container->has(\FratellanzaMilitare\Service\RedisService::class) ? $container->get(\FratellanzaMilitare\Service\RedisService::class) : null;
    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    $apiLimit = new RateLimitMiddleware(60, 60, $redis, $logger);

    $app->group('/api/v1', function ($group) {
        $group->get('/soci', \FratellanzaMilitare\Controller\SociApiController::class . ':list');
        $group->get('/soci/{cf}', \FratellanzaMilitare\Controller\SociApiController::class . ':get');
        $group->post('/soci', \FratellanzaMilitare\Controller\SociApiController::class . ':create');
    })
        ->add(new \FratellanzaMilitare\Middleware\ApiKeyMiddleware(
            $container->get(PDO::class),
            $container->get(\FratellanzaMilitare\SecurityLayer\AuditTrail::class)
        ))
        ->add($apiLimit);
    // ->add(new \FratellanzaMilitare\Middleware\JwtAuthMiddleware()); // TODO: Enable when Auth0 is ready

    // GraphQL API
    $app->post('/api/graphql', \FratellanzaMilitare\Controller\GraphQLController::class . ':handle')->setName('graphql_api');
    // ->add(...) // Disabled for testing connectivity


    // API Documentation
    $app->get('/api/docs', \FratellanzaMilitare\Controller\Docs\DocumentationController::class . ':ui')->setName('api_docs');
    $app->get('/api/docs/json', \FratellanzaMilitare\Controller\Docs\DocumentationController::class . ':spec')->setName('api_docs_json');

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
