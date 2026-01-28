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
    // Calcolo CF (Utility condivisa - Accessibile a ruoli amministrativi e operativi)
    $app->post('/soci/calcola-cf', SocioAction::class . ':calculateFiscalCode')
        ->setName('socio_calc_cf')
        ->add(new RateLimitMiddleware(20, 60))
        ->add(new RoleMiddleware(['admin', 'segreteria', 'segreteria_soci', 'direttore_associazione', 'sviluppo', 'comando', 'user'])); // Ampliato per Workshift

    // Anagrafica - Soci (Write) - SPECIFICHE PRIMA DI {cf}
    $writeRole = new RoleMiddleware(['segreteria', 'segreteria_soci', 'direttore_associazione']);
    $app->group('/soci', function ($group) {
        // ROUTES SPECIFICHE PRIMA
        $group->get('/nuovo', SocioPersistence::class . ':create')->setName('socio_create');
        $group->post('/salva', SocioPersistence::class . ':store')->setName('socio_store');

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
    $app->get('/workshift/api/shifts', \MCAG\Controller\External\WorkshiftController::class . ':getShifts');
    $app->delete('/workshift/api/shifts/{id}', \MCAG\Controller\External\WorkshiftController::class . ':deleteShift');
    $app->post('/workshift/api/shifts/reset', \MCAG\Controller\External\WorkshiftController::class . ':resetShifts');
    $app->post('/workshift/api/optimize', \MCAG\Controller\External\WorkshiftController::class . ':optimizeSchedule');
    $app->post('/workshift/api/apply-suggestion', \MCAG\Controller\External\WorkshiftController::class . ':applyAiSuggestion'); // [NEW] AI Action
    $app->get('/workshift/api/reports/export', \MCAG\Controller\External\WorkshiftController::class . ':exportReports'); // [NEW] Export Action
    $app->get('/workshift/api/ai-suggestion', \MCAG\Controller\External\WorkshiftController::class . ':getNewAiSuggestion'); // [NEW] Refresh AI
    $app->get('/workshift/api/system-status', \MCAG\Controller\External\WorkshiftController::class . ':getSystemStatusApi'); // [NEW] System Status API

    // Team API
    $app->get('/workshift/api/employees', \MCAG\Controller\External\WorkshiftController::class . ':getEmployees');
    $app->post('/workshift/api/employees/save', \MCAG\Controller\External\WorkshiftController::class . ':saveEmployee');
    $app->delete('/workshift/api/employees/{id}', \MCAG\Controller\External\WorkshiftController::class . ':deleteEmployee');
    $app->get('/workshift/api/candidates', \MCAG\Controller\External\WorkshiftController::class . ':searchCandidates');

    // Time Off API
    $app->get('/workshift/api/requests', \MCAG\Controller\External\WorkshiftController::class . ':getRequests');
    $app->post('/workshift/api/requests/save', \MCAG\Controller\External\WorkshiftController::class . ':saveRequest');
    $app->post('/workshift/api/requests/{id}/status', \MCAG\Controller\External\WorkshiftController::class . ':updateRequestStatus');
    $app->delete('/workshift/api/requests/{id}', \MCAG\Controller\External\WorkshiftController::class . ':deleteRequest');
    $app->post('/workshift/api/requests/reset', \MCAG\Controller\External\WorkshiftController::class . ':resetRequests');

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

    // Knowledge Hub (Internal Documentation)
    $app->group('/docs', function ($group) {
        $group->get('', \MCAG\Controller\Docs\DocumentationController::class . ':hub')->setName('docs_hub');
        $group->get('/{category}', \MCAG\Controller\Docs\DocumentationController::class . ':category')->setName('docs_category');
        $group->get('/{category}/{file}', \MCAG\Controller\Docs\DocumentationController::class . ':viewFile')->setName('docs_view_file');
    })->add(new AdminMiddleware()); // Protect Internal Docs

    // --- Taskflow Routes ---
    $app->group('/taskflow', function (\Slim\Routing\RouteCollectorProxy $group) {
        $group->get('', \MCAG\Controller\External\TaskflowController::class . ':index')->setName('taskflow_home');
        $group->get('/about', \MCAG\Controller\External\TaskflowController::class . ':about')->setName('taskflow_about');

        // API
        $group->get('/api/tasks', \MCAG\Controller\External\TaskflowController::class . ':getTasks');
        $group->post('/api/tasks', \MCAG\Controller\External\TaskflowController::class . ':addTask');  // Changed from /api/tasks/add
        $group->put('/api/tasks', \MCAG\Controller\External\TaskflowController::class . ':updateTask'); // Changed from POST /api/tasks/update
        $group->delete('/api/tasks', \MCAG\Controller\External\TaskflowController::class . ':deleteTask'); // Changed from standard POST to DELETE logic handling
    });

    // --- Expensebar Routes ---
    $app->group('/expensebar', function (\Slim\Routing\RouteCollectorProxy $group) {
        $group->get('', \MCAG\Controller\External\ExpensebarController::class . ':index')->setName('expensebar_home');
        $group->get('/analytics', \MCAG\Controller\External\ExpensebarController::class . ':analytics')->setName('expensebar_analytics');
        $group->get('/help', \MCAG\Controller\External\ExpensebarController::class . ':help')->setName('expensebar_help'); // [NEW] Help Center

        // API
        $group->get('/api/expenses', \MCAG\Controller\External\ExpensebarController::class . ':getExpenses');
        $group->post('/api/expenses/add', \MCAG\Controller\External\ExpensebarController::class . ':addExpense');
        $group->post('/api/expenses/{id}/delete', \MCAG\Controller\External\ExpensebarController::class . ':deleteExpense'); // Explicit DELETE via POST
        $group->post('/api/expenses/{id}/update', \MCAG\Controller\External\ExpensebarController::class . ':updateExpense');
        $group->get('/api/forecast', \MCAG\Controller\External\ExpensebarController::class . ':getForecast');
        $group->get('/api/stats/category', \MCAG\Controller\External\ExpensebarController::class . ':getCategoryStats'); // [NEW] Stats
        $group->get('/api/stats/trend', \MCAG\Controller\External\ExpensebarController::class . ':getTrend'); // [NEW] Stats
    });

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

        // SaaS Super Admin
        $group->get('/super-admin', \MCAG\Controller\Admin\SuperAdminController::class . ':dashboard')->setName('super_admin_dashboard');
        $group->post('/super-admin/create', \MCAG\Controller\Admin\SuperAdminController::class . ':createTenant')->setName('super_admin_create');
        $group->post('/super-admin/toggle/{id}', \MCAG\Controller\Admin\SuperAdminController::class . ':toggleStatus')->setName('super_admin_toggle');

        // Partner / Reseller Portal
        $group->get('/partner/dashboard', \MCAG\Controller\Partner\ResellerController::class . ':dashboard')->setName('partner_dashboard');
        $group->post('/partner/client/create', \MCAG\Controller\Partner\ResellerController::class . ':createClient')->setName('partner_client_create');
        $group->post('/partner/client/action', \MCAG\Controller\Partner\ResellerController::class . ':handleAction')->setName('partner_client_action');
        $group->get('/partner/client/access/{id}', \MCAG\Controller\Partner\ResellerController::class . ':accessTenant')->setName('partner_client_access');
        $group->get('/partner/client/exit', \MCAG\Controller\Partner\ResellerController::class . ':exitTenant')->setName('partner_client_exit');

    })->add(new AdminMiddleware());

    // API Routes (Authenticated)
    $app->group('/api', function ($group) {
        $group->post('/ai/chat', \MCAG\Controller\API\AIChatController::class . ':chat');
    }); // ->add(new ApiAuthMiddleware()); // Uncomment in prod

};


