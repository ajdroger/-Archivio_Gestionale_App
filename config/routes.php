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

    // Main
    $app->get('/', HomeController::class . ':dashboard')->setName('dashboard');

    // Anagrafica - Soci (Read-Only - SOLO LISTA, detail dopo le route specifiche)
    $app->get('/soci', SocioList::class . ':list')->setName('socio_list');

    // Anagrafica - Soci (Write) - SPECIFICHE PRIMA DI {cf}
    $writeRole = new RoleMiddleware(['segreteria']);
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

    // Intelligence (Stats & Reports)
    $statsRole = new RoleMiddleware(['presidente', 'segreteria']);
    $exportLimit = new RateLimitMiddleware(30, 60);

    $app->group('/statistiche', function ($group) use ($exportLimit) {
        $group->get('', StatsDashboard::class . ':view')->setName('statistics');
        $group->get('/export/pdf', StatsExport::class . ':exportPdf')->setName('statistics_pdf')->add($exportLimit);
        $group->get('/export/excel', StatsExport::class . ':exportExcel')->setName('statistics_excel')->add($exportLimit);
    })->add($statsRole);

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
        $group->post('/devtools/alive', DevToolsDashboardController::class . ':heartbeat')->setName('devtools_alive');
    })->add(new AdminMiddleware());

};
