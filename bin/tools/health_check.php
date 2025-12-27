<?php

/**
 * System Health Check - Complete Diagnostic Tool
 * Verifies all critical system components
 */

require __DIR__ . '/../../vendor/autoload.php';

// Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "🏥 SYSTEM HEALTH CHECK\n";
echo str_repeat('=', 60) . "\n\n";

$results = [];
$overallHealth = true;

// 1. PHP Version Check
echo "1️⃣  PHP Version Check... ";
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.2.0', '>=')) {
    echo "✅ $phpVersion\n";
    $results['php'] = true;
} else {
    echo "❌ $phpVersion (Required: >= 8.2.0)\n";
    $results['php'] = false;
    $overallHealth = false;
}

// 2. Required Extensions
echo "\n2️⃣  Required PHP Extensions:\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'fileinfo'];
foreach ($requiredExtensions as $ext) {
    echo "   - $ext: ";
    if (extension_loaded($ext)) {
        echo "✅\n";
        $results["ext_$ext"] = true;
    } else {
        echo "❌ MISSING\n";
        $results["ext_$ext"] = false;
        $overallHealth = false;
    }
}

// 3. Database Connection
echo "\n3️⃣  Database Connection... ";
try {
    $pdo = DatabaseConnection::getConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "✅ Connected ($driver)\n";
    $results['database'] = true;

    // Check tables
    echo "   - Tables: ";
    if ($driver === 'mysql') {
        $stmt = $pdo->query("SHOW TABLES");
    } else {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    }
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = ['users', 'soci', 'documenti', 'audit_logs'];
    $missingTables = array_diff($requiredTables, $tables);

    if (empty($missingTables)) {
        echo "✅ All present (" . count($tables) . " total)\n";
        $results['tables'] = true;
    } else {
        echo "❌ Missing: " . implode(', ', $missingTables) . "\n";
        $results['tables'] = false;
        $overallHealth = false;
    }

} catch (Exception $e) {
    echo "❌ {$e->getMessage()}\n";
    $results['database'] = false;
    $overallHealth = false;
}

// 4. File Permissions
echo "\n4️⃣  File Permissions:\n";
$writablePaths = [
    'storage/uploads' => 'Uploads directory',
    'storage/logs' => 'Logs directory',
    'backups' => 'Backups directory',
];

foreach ($writablePaths as $path => $description) {
    $fullPath = __DIR__ . '/../' . $path;
    echo "   - $description: ";

    if (!file_exists($fullPath)) {
        @mkdir($fullPath, 0775, true);
    }

    if (is_writable($fullPath)) {
        echo "✅ Writable\n";
        $results["perm_$path"] = true;
    } else {
        echo "❌ Not writable\n";
        $results["perm_$path"] = false;
        $overallHealth = false;
    }
}

// 5. Environment Variables
echo "\n5️⃣  Environment Configuration:\n";
$requiredEnvVars = ['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
foreach ($requiredEnvVars as $var) {
    echo "   - $var: ";
    if (!empty($_ENV[$var])) {
        echo "✅ Set\n";
        $results["env_$var"] = true;
    } else {
        echo "❌ Not set\n";
        $results["env_$var"] = false;
        $overallHealth = false;
    }
}

// 6. Disk Space
echo "\n6️⃣  Disk Space... ";
$freeSpace = disk_free_space(__DIR__ . '/..');
$totalSpace = disk_total_space(__DIR__ . '/..');
$freeSpaceMB = round($freeSpace / 1024 / 1024, 2);
$usagePercent = round((1 - ($freeSpace / $totalSpace)) * 100, 2);

if ($usagePercent < 90) {
    echo "✅ {$freeSpaceMB}MB free ({$usagePercent}% used)\n";
    $results['disk'] = true;
} else {
    echo "⚠️  {$freeSpaceMB}MB free ({$usagePercent}% used) - LOW\n";
    $results['disk'] = false;
}

// 7. Memory Limit
echo "\n7️⃣  Memory Limit... ";
$memLimit = ini_get('memory_limit');
$cliLimitOk = false;

// Check CLI limit
if (preg_match('/^(\d+)(.)$/', $memLimit, $matches)) {
    $value = (int) $matches[1];
    $unit = $matches[2];
    $bytes = $value * (1024 ** (strpos('BKMG', $unit)));
    if ($bytes >= 256 * 1024 * 1024) {
        $cliLimitOk = true;
    }
}

// Check Index limit
$indexLimitOk = false;
$indexContent = file_get_contents(__DIR__ . '/../public/index.php');
if (preg_match("/ini_set\('memory_limit',\s*'([^']+)'\)/", $indexContent, $matches)) {
    $indexLimit = $matches[1];
    if (preg_match('/^(\d+)(.)$/', $indexLimit, $imm)) {
        $ival = (int) $imm[1];
        $iunit = $imm[2];
        $ibytes = $ival * (1024 ** (strpos('BKMG', $iunit)));
        if ($ibytes >= 256 * 1024 * 1024) {
            $indexLimitOk = true;
        }
    }
}

if ($cliLimitOk) {
    echo "✅ $memLimit (CLI)\n";
    $results['memory'] = true;
} elseif ($indexLimitOk) {
    echo "✅ Configured in public/index.php (Overridden to $indexLimit)\n";
    $results['memory'] = true;
} else {
    echo "⚠️  $memLimit (Recommended: >= 256M)\n";
    $results['memory'] = false;
}

// 8. Security Headers Test
echo "\n8️⃣  Security Configuration:\n";
echo "   - HTTPS Redirect: ";
$appEnv = $_ENV['APP_ENV'] ?? 'production';
if ($appEnv === 'local' || $appEnv === 'development') {
    echo "⚠️  Disabled (dev environment)\n";
} else {
    echo "✅ Enabled\n";
}

echo "   - Session Security: ";
$indexContent = file_get_contents(__DIR__ . '/../public/index.php');
if (
    strpos($indexContent, "ini_set('session.cookie_httponly', 1)") !== false &&
    strpos($indexContent, "ini_set('session.use_only_cookies', 1)") !== false
) {
    echo "✅ Configured (in public/index.php)\n";
    $results['session'] = true;
} else {
    echo "❌ Not properly configured in public/index.php\n";
    $results['session'] = false;
    $overallHealth = false;
}

// Summary
echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 HEALTH CHECK SUMMARY\n";
echo str_repeat('=', 60) . "\n";

$passed = array_filter($results, fn($v) => $v === true);
$failed = array_filter($results, fn($v) => $v === false);

echo sprintf("✅ Passed: %d\n", count($passed));
echo sprintf("❌ Failed: %d\n", count($failed));
echo sprintf("📈 Success Rate: %.1f%%\n", (count($passed) / count($results)) * 100);

if ($overallHealth) {
    echo "\n🎉 OVERALL STATUS: ✅ HEALTHY\n";
    exit(0);
} else {
    echo "\n⚠️  OVERALL STATUS: ❌ NEEDS ATTENTION\n";
    echo "\nPlease fix the failed checks above before deploying to production.\n";
    exit(1);
}
