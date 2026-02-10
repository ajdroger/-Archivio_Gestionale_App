<?php
/**
 * Fratellanza Militare - Debug Dashboard (Premium v2.1 Horizontal)
 * Interfaccia grafica aggregata con design moderno.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Debug/SystemCheck.php';
require_once __DIR__ . '/../../src/Debug/DatabaseInspector.php';
require_once __DIR__ . '/../../src/Debug/LogViewer.php';
require_once __DIR__ . '/../../src/Debug/QueryLogger.php';
require_once __DIR__ . '/../../src/InfrastrutturaIT/Persistence/DatabaseConnection.php';

use MCAG\Debug\SystemCheck;
use MCAG\Debug\DatabaseInspector;
use MCAG\Debug\LogViewer;
use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

$checker = new SystemCheck();
$diag = $checker->runDiagnostics();

$db = DatabaseConnection::getConnection();
$inspector = new DatabaseInspector($db);
$dbSummary = $inspector->getTablesSummary();

$logViewer = new LogViewer();
$logs = $logViewer->listLogs();

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Dashboard - MCAG_Militare-Civile-Archivio-Gestionale</title>
    <!-- Cache Buster -->
    <link rel="stylesheet"
        href="/MCAG_Militare-Civile-Archivio-Gestionale/public/css/premium.css?v=<?php echo time(); ?>">
    <script src="/MCAG_Militare-Civile-Archivio-Gestionale/public/script/app.js" defer></script>
    <style>
        .horizontal-wrapper {
            display: flex;
            overflow-x: auto;
            gap: 25px;
            padding-bottom: 15px;
        }

        .dash-card {
            min-width: 400px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 20px;
        }

        .btn-giant {
            display: block;
            width: 100%;
            text-align: center;
            background: linear-gradient(135deg, #4f46e5, #818cf8);
            color: white;
            padding: 20px;
            font-size: 1.5rem;
            font-weight: bold;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
            transition: transform 0.2s;
            text-decoration: none;
            margin-bottom: 30px;
        }

        .btn-giant:hover {
            transform: scale(1.02);
            filter: brightness(1.1);
        }
    </style>
</head>

<body>
    <div class="container" style="max-width: 95%;">
        <header>
            <h1>🛠️ Debug Operations Center v2.1</h1>
        </header>

        <!-- GIANT BUTTON TO TOOLKIT -->
        <a href="test_dashboard.php" class="btn-giant">
            🚀 VAI ALLA SESSIONE TOOLKIT (100+ TEST)
            <div style="font-size: 0.9rem; font-weight: normal; margin-top: 5px; opacity: 0.9;">
                Layout Orizzontale | Esecuzione One-Click | Verificati 106+ Test
            </div>
        </a>

        <h2 style="color: #94a3b8; margin-bottom: 15px;">Monitoraggio Sistema (Orizzontale)</h2>
        <div class="horizontal-wrapper">
            <!-- System Health -->
            <div class="dash-card">
                <h2>🩺 Stato Sistema</h2>
                <table>
                    <tr>
                        <td>Versione PHP</td>
                        <td><code><?php echo PHP_VERSION; ?></code></td>
                    </tr>
                    <?php foreach ($diag['extensions'] as $ext): ?>
                        <tr>
                            <td><?php echo str_replace('ext_', '', $ext['message']); ?></td>
                            <td><span
                                    class="<?php echo $ext['status'] ? 'status-ok' : 'status-fail'; ?>"><?php echo $ext['status'] ? '✔' : '✘'; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>Spazio Disco</td>
                        <td style="font-size: 0.85rem;"><?php echo $diag['disk_space']['message']; ?></td>
                    </tr>
                </table>
            </div>

            <!-- Database Info -->
            <div class="dash-card">
                <h2>🗄️ Database SQLite</h2>
                <div style="margin-bottom: 15px;">
                    <span class="badge">Integrità: <span
                            class="status-ok"><?php echo $inspector->checkIntegrity(); ?></span></span>
                    <span class="badge">Peso: <?php echo $inspector->getDatabaseSize(); ?></span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Tabella</th>
                            <th style="text-align: right;">Record</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbSummary as $table): ?>
                            <tr>
                                <td><?php echo $table['name']; ?></td>
                                <td style="text-align: right;"><?php echo $table['rows']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Logs -->
            <div class="dash-card">
                <h2>📄 Monitor Log</h2>
                <div style="margin-bottom: 15px;">
                    <?php
                    $auditLog = __DIR__ . '/../../logs/audit/audit.log';
                    $auditOk = file_exists($auditLog) && is_writable($auditLog);
                    ?>
                    <span class="badge">Audit Trail: <span
                            class="<?php echo $auditOk ? 'status-ok' : 'status-fail'; ?>"><?php echo $auditOk ? 'ATTIVO' : 'ERRORE'; ?></span></span>
                </div>
                <div class="log-container">
                    <table>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['name']; ?></td>
                                <td style="text-align: right;"><span class="badge"><?php echo $log['size']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <p
                    style="font-size: 0.8rem; color: #64748b; margin-top: 15px; background: #0f172a; padding: 10px; border-radius: 6px;">
                    💡 Root path: <code>/logs</code>
                </p>
            </div>
        </div>

        <div class="dash-card" style="margin-top: 25px; border-top: 4px solid #fbbf24;">
            <h2>⚡ Strumenti Rapidi</h2>
            <div style="display: flex; gap: 15px; margin-top: 10px; flex-wrap: wrap;">
                <a href="env_check.php" class="btn">Ambiente</a>
                <a href="verify_xdebug.php" class="btn">Xdebug JSON</a>
                <a href="../../bin/debug_tools/check_ini.php" class="btn">PHP Info</a>
                <a href="repair_tool.php" class="btn" style="background: #f87171; color: white;">Riparazione Sistema</a>
            </div>
        </div>
    </div>
</body>

</html>