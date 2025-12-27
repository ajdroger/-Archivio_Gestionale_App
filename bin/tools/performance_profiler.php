<?php

/**
 * Performance Profiler - Analyze application performance bottlenecks
 */

require __DIR__ . '/../../vendor/autoload.php';

// Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "⚡ PERFORMANCE PROFILER\n";
echo str_repeat('=', 60) . "\n\n";

$pdo = DatabaseConnection::getConnection();
$repo = new PDOSocioRepository($pdo);

$benchmarks = [];

// 1. Database Query Performance
echo "1️⃣  Database Query Benchmarks:\n";

// Test 1: Find by codice fiscale
$iterations = 100;
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $soci = $repo->findAll();
    if (!empty($soci)) {
        $repo->findByCodiceFiscale($soci[0]->CodiceFiscale);
    }
}
$end = microtime(true);
$avgTime = (($end - $start) / $iterations) * 1000; // ms
$benchmarks['find_by_cf'] = $avgTime;
echo sprintf("   - Find by CF: %.2fms avg (100 iterations)\n", $avgTime);

// Test 2: List all soci
$start = microtime(true);
for ($i = 0; $i < 10; $i++) {
    $repo->findAll();
}
$end = microtime(true);
$avgTime = (($end - $start) / 10) * 1000;
$benchmarks['find_all'] = $avgTime;
echo sprintf("   - Find all: %.2fms avg (10 iterations)\n", $avgTime);

// Test 3: Search query
$start = microtime(true);
for ($i = 0; $i < 50; $i++) {
    $repo->search('test');
}
$end = microtime(true);
$avgTime = (($end - $start) / 50) * 1000;
$benchmarks['search'] = $avgTime;
echo sprintf("   - Search: %.2fms avg (50 iterations)\n", $avgTime);

// 2. Memory Usage
echo "\n2️⃣  Memory Usage:\n";
$memStart = memory_get_usage();
$memPeakStart = memory_get_peak_usage();

// Load all soci
$allSoci = $repo->findAll();
$socioCount = count($allSoci);

$memEnd = memory_get_usage();
$memPeakEnd = memory_get_peak_usage();

$memUsed = ($memEnd - $memStart) / 1024; // KB
$memPeak = ($memPeakEnd - $memPeakStart) / 1024;

echo sprintf("   - Loaded %d soci: %.2f KB\n", $socioCount, $memUsed);
echo sprintf("   - Peak memory: %.2f KB\n", $memPeak);
echo sprintf("   - Memory per socio: %.2f KB\n", $socioCount > 0 ? $memUsed / $socioCount : 0);

$benchmarks['memory_per_socio'] = $socioCount > 0 ? $memUsed / $socioCount : 0;

// 3. Database Connection Pool
echo "\n3️⃣  Database Connection:\n";
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    DatabaseConnection::getConnection();
}
$end = microtime(true);
$connTime = (($end - $start) / 100) * 1000;
$benchmarks['connection'] = $connTime;
echo sprintf("   - Connection time: %.2fms avg (100 iterations)\n", $connTime);

// 4. File I/O Performance
echo "\n4️⃣  File I/O:\n";
$testFile = __DIR__ . '/../storage/perf_test_' . time() . '.tmp';
$data = str_repeat('x', 1024 * 100); // 100KB

$start = microtime(true);
for ($i = 0; $i < 10; $i++) {
    file_put_contents($testFile, $data);
}
$end = microtime(true);
$writeTime = (($end - $start) / 10) * 1000;
echo sprintf("   - Write 100KB: %.2fms avg (10 iterations)\n", $writeTime);

$start = microtime(true);
for ($i = 0; $i < 10; $i++) {
    file_get_contents($testFile);
}
$end = microtime(true);
$readTime = (($end - $start) / 10) * 1000;
echo sprintf("   - Read 100KB: %.2fms avg (10 iterations)\n", $readTime);

@unlink($testFile);

$benchmarks['file_write'] = $writeTime;
$benchmarks['file_read'] = $readTime;

// 5. Performance Analysis
echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 PERFORMANCE ANALYSIS\n";
echo str_repeat('=', 60) . "\n\n";

// Set thresholds
$thresholds = [
    'find_by_cf' => 10.0,  // Should be < 10ms
    'find_all' => 100.0,   // Should be < 100ms
    'search' => 50.0,      // Should be < 50ms
    'connection' => 1.0,   // Should be < 1ms
    'memory_per_socio' => 50.0, // Should be < 50KB per socio
];

$issues = [];
foreach ($benchmarks as $test => $value) {
    if (isset($thresholds[$test]) && $value > $thresholds[$test]) {
        $issues[] = "$test: {$value} (threshold: {$thresholds[$test]})";
    }
}

if (empty($issues)) {
    echo "✅ All performance metrics within acceptable thresholds\n";
    echo "\n🎉 PERFORMANCE STATUS: OPTIMAL\n";
} else {
    echo "⚠️  Performance issues detected:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    echo "\n💡 Recommendations:\n";
    if (isset($issues['find_all'])) {
        echo "   - Consider implementing pagination for findAll()\n";
        echo "   - Add database indices if not present\n";
    }
    if (isset($issues['memory_per_socio'])) {
        echo "   - Implement lazy loading for associations\n";
        echo "   - Use DTOs instead of full entities\n";
    }
}

echo "\n📈 Benchmark Results Summary:\n";
foreach ($benchmarks as $test => $value) {
    $unit = strpos($test, 'memory') !== false ? 'KB' : 'ms';
    echo sprintf("   - %-20s: %.2f %s\n", $test, $value, $unit);
}
