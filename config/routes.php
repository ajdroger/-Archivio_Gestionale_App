<?php

use Slim\App;
use FratellanzaMilitare\Controller\LoginController;
use FratellanzaMilitare\Controller\HomeController;
use FratellanzaMilitare\Controller\SocioController;
use FratellanzaMilitare\Controller\StatisticsController;
use FratellanzaMilitare\Controller\SettingsController;
use FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController;
use FratellanzaMilitare\Controller\DevTools\DevToolsFileSystemController;
use FratellanzaMilitare\Controller\DevTools\DevToolsDatabaseController;
use FratellanzaMilitare\Controller\DevTools\DevToolsSecurityController;
use FratellanzaMilitare\Controller\DevTools\DevToolsScriptController;
use FratellanzaMilitare\Middleware\AdminMiddleware;
use FratellanzaMilitare\Middleware\RateLimitMiddleware;

return function (App $app) {
    // Auth Routes (Strict Rate Limit: 5 per minute)
    $loginLimit = new RateLimitMiddleware(5, 60);
    $app->get('/login', LoginController::class . ':form')->setName('login')->add($loginLimit);
    $app->post('/login', LoginController::class . ':verifyCredentials')->setName('login_verify')->add($loginLimit);
    $app->get('/login/2fa', LoginController::class . ':form2fa')->setName('login_2fa')->add($loginLimit);
    $app->post('/login/2fa', LoginController::class . ':verify2fa')->setName('login_2fa_verify')->add($loginLimit);
    $app->get('/logout', LoginController::class . ':logout')->setName('logout');

    // Main Functionalities
    $app->get('/', HomeController::class . ':dashboard')->setName('dashboard');

    // Read-Only Routes (All Roles)
    $app->get('/soci', SocioController::class . ':list')->setName('socio_list');
    // Write Routes (Segreteria & Admin)
    $writeRole = new \FratellanzaMilitare\Middleware\RoleMiddleware(['segreteria']);

    $app->group('/soci', function ($group) {
        $group->get('/nuovo', SocioController::class . ':create')->setName('socio_create');
        $group->post('/salva', SocioController::class . ':store')->setName('socio_store');
        $group->post('/calcola-cf', SocioController::class . ':calculateFiscalCode')->setName('socio_calc_cf')->add(new RateLimitMiddleware(10, 60));
        $group->get('/{cf}/modifica', SocioController::class . ':edit')->setName('socio_edit');
        $group->post('/{cf}/aggiorna', SocioController::class . ':update')->setName('socio_update');
        $group->post('/{cf}/elimina', SocioController::class . ':delete')->setName('socio_delete');
        $group->post('/{cf}/upload', SocioController::class . ':uploadDocument')->setName('socio_upload_doc');
        $group->post('/{cf}/documenti/{id}/elimina', SocioController::class . ':deleteDocument')->setName('socio_delete_doc');
    })->add($writeRole);

    // Read-Only Routes (Must be after specific routes to avoid shadowing)
    $app->get('/soci/{cf}', SocioController::class . ':detail')->setName('socio_detail');
    $app->get('/soci/{cf}/documenti/{id}/download', SocioController::class . ':downloadDocument')->setName('socio_download_doc');

    $app->get('/soci/export/csv', SocioController::class . ':exportCsv')->setName('socio_export')->add(new RateLimitMiddleware(20, 60));

    // Backup Route
    // $app->post('/backup/db', \FratellanzaMilitare\Controller\DevTools\DevToolsDatabaseController::class . ':backupDb')->setName('backup_db')->add($adminLimit);

    // Monitoring
    $app->get('/api/health', \FratellanzaMilitare\Controller\HealthController::class . ':check')->setName('health_check');

    // Stats Exports (Presidente, Segreteria, Admin)
    $statsRole = new \FratellanzaMilitare\Middleware\RoleMiddleware(['presidente', 'segreteria']);
    $exportLimit = new RateLimitMiddleware(20, 60);

    $app->group('/statistiche', function ($group) use ($exportLimit) {
        $group->get('', StatisticsController::class . ':view')->setName('statistics');
        $group->get('/export/pdf', StatisticsController::class . ':exportPdf')->setName('statistics_pdf')->add($exportLimit);
        $group->get('/export/excel', StatisticsController::class . ':exportExcel')->setName('statistics_excel')->add($exportLimit);
    })->add($statsRole);

    // Protected Admin Routes - REFACTORED TO 5 CONTROLLERS
    $app->group('', function ($group) {
        // Settings
        $group->get('/impostazioni', SettingsController::class . ':view')->setName('settings');

        // Dashboard Controller
        $group->get('/devtools', DevToolsDashboardController::class . ':dashboard')->setName('devtools');

        // Script Controller
        $group->post('/devtools/run', DevToolsScriptController::class . ':runScript')->setName('devtools_run');
        $group->post('/devtools/trace', DevToolsScriptController::class . ':logTrace')->setName('devtools_trace');
        $group->post('/devtools/renamer', DevToolsScriptController::class . ':runRenamer')->setName('devtools_renamer');

        // FileSystem Controller
        $group->post('/devtools/fs/list', DevToolsFileSystemController::class . ':fsList')->setName('devtools_fs_list');
        $group->post('/devtools/fs/read', DevToolsFileSystemController::class . ':fsRead')->setName('devtools_fs_read');
        $group->post('/devtools/fs/save', DevToolsFileSystemController::class . ':fsSave')->setName('devtools_fs_save');

        // Database Controller
        $group->post('/devtools/db/query', DevToolsDatabaseController::class . ':dbQuery')->setName('devtools_db_query');

        // Security Controller
        $group->post('/devtools/security/list', DevToolsSecurityController::class . ':securityList')->setName('devtools_sec_list');
        $group->post('/devtools/security/add', DevToolsSecurityController::class . ':securityAdd')->setName('devtools_sec_add');
        $group->post('/devtools/security/reset', DevToolsSecurityController::class . ':securityReset')->setName('devtools_sec_reset');
        $group->post('/devtools/security/rotate', DevToolsSecurityController::class . ':securityRotate2FA')->setName('devtools_sec_rotate');
        $group->post('/devtools/security/delete', DevToolsSecurityController::class . ':securityDelete')->setName('devtools_sec_delete');

        // Audit Exports (Database Controller)
        $group->get('/devtools/export/audit/pdf', DevToolsDatabaseController::class . ':exportAuditPdf')->setName('devtools_audit_pdf');
        $group->get('/devtools/export/audit/excel', DevToolsDatabaseController::class . ':exportAuditExcel')->setName('devtools_audit_excel');
    })->add(new AdminMiddleware());
};
