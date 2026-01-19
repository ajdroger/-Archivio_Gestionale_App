<?php
/**
 * MCAG HYPER-GRID TOOLKIT v7.1
 * "QUANTUM ENGINEERING DECK"
 * 
 * @version 7.2.0-god-mode
 */

require_once __DIR__ . '/../../vendor/autoload.php';
date_default_timezone_set('Europe/Rome');

// --- BACKEND LOGIC ---

// 1. GIT INFO
function getGitInfo()
{
    $root = __DIR__ . '/../../';
    $branch = trim(shell_exec("cd \"$root\" && git rev-parse --abbrev-ref HEAD"));
    $hash = trim(shell_exec("cd \"$root\" && git rev-parse --short HEAD"));
    $status = trim(shell_exec("cd \"$root\" && git status --porcelain"));
    return ['branch' => $branch, 'hash' => $hash, 'dirty' => !empty($status)];
}

// 2. LOG READER (Tail)
function getLogTail($lines = 50)
{
    $logFile = __DIR__ . '/../../storage/logs/app.log';
    if (!file_exists($logFile))
        return ["Log file not found."];
    $data = file($logFile);
    return array_slice($data, -$lines);
}

// 3. CACHE PURGE
function purgeCache()
{
    $dirs = [__DIR__ . '/../../var/cache', __DIR__ . '/../../tmp'];
    $results = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir))
            continue;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        $results[] = "Purged: " . basename($dir);
    }
    return $results;
}

// 4. PRECISE TEST COUNTING
function countTestsInFile($path)
{
    if (!file_exists($path))
        return 0;
    $content = file_get_contents($path);

    // PHPUnit: "public function testMethods()"
    $phpunit = preg_match_all('/public\s+function\s+test\w+/i', $content);

    // Pest: "test('description', ...)" or "it('description', ...)"
    // Matches start of line or after space, followed by test/it, open paren, quote
    $pest = preg_match_all('/(?:^|\s)(?:test|it)\s*\([\'"]/', $content);

    // Attributes: "#[Test]" (PHPUnit 10+)
    $attrs = preg_match_all('/#\[Test\]/i', $content);

    return $phpunit + $pest + $attrs;
}

function scanTestsRecursive($dir)
{
    if (!is_dir($dir))
        return [];

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $files = [];
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
    $totalCount = 0;

    foreach ($iter as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), 'Test.php')) {
            $realPath = $f->getRealPath();
            // NORMALIZE PATHS FOR WINDOWS/UNIX CONSISTENCY
            $path = str_replace('\\', '/', $realPath);

            if (str_contains($path, '/vendor/') || str_contains($path, '/Archived/'))
                continue;

            $testCount = countTestsInFile($realPath);
            if ($testCount === 0)
                continue; // Skip files with no actual tests

            $totalCount += $testCount;

            // Calculate relative path for display/execution
            // $projectRoot ends with slash now? No, realpath usually strips trailing slash
            // Let's be safe
            $rootNormalized = str_replace('\\', '/', $projectRoot);
            $rel = str_replace($rootNormalized, '', $path);
            $rel = ltrim($rel, '/'); // Remove leading slash if any

            // Category Extraction
            $relFromTests = str_replace($rootNormalized . '/tests/', '', $path);
            $category = dirname($relFromTests);
            if ($category === '.')
                $category = 'Root';

            // Cleanup category display
            $category = str_replace('/', ' > ', $category);

            $files[] = [
                'name' => $f->getFilename(),
                'path' => $rel,
                'cat' => $category,
                'count' => $testCount
            ];
        }
    }

    usort($files, fn($a, $b) => $a['cat'] <=> $b['cat'] ?: $a['name'] <=> $b['name']);

    return ['files' => $files, 'total' => $totalCount];
}

// 5. NETWORK RECONNAISSANCE (Real Implementations)
function doPortScan($target)
{
    $ports = [21 => 'FTP', 22 => 'SSH', 23 => 'TELNET', 25 => 'SMTP', 53 => 'DNS', 80 => 'HTTP', 110 => 'POP3', 143 => 'IMAP', 443 => 'HTTPS', 3306 => 'MYSQL', 3389 => 'RDP', 8080 => 'HTTP-ALT'];
    $results = [];
    $target = filter_var($target, FILTER_VALIDATE_IP) ? $target : gethostbyname($target);

    if ($target === $target) { // Basic check if resolution worked
        foreach ($ports as $port => $service) {
            $connection = @fsockopen($target, $port, $errno, $errstr, 0.5); // 0.5s timeout
            if (is_resource($connection)) {
                $results[] = "[OPEN] Port $port ($service)";
                fclose($connection);
            } else {
                // $results[] = "[CLOSED] Port $port"; // Too noisy
            }
        }
    }
    return empty($results) ? ["No open ports found on $target (or firewall active)."] : $results;
}

function doWhois($domain)
{
    $server = 'whois.iana.org';
    if (!$domain)
        return ["Invalid Domain"];

    $fp = fsockopen($server, 43, $errno, $errstr, 5);
    if (!$fp)
        return ["WHOIS Connection Failed: $errstr"];

    fputs($fp, $domain . "\r\n");
    $out = "";
    while (!feof($fp)) {
        $out .= fgets($fp, 128);
    }
    fclose($fp);
    return explode("\n", $out);
}

function doDnsEnum($target)
{
    if (!filter_var($target, FILTER_VALIDATE_DOMAIN))
        return ["Invalid Domain for DNS Scan"];

    $records = dns_get_record($target, DNS_ALL);
    if (!$records)
        return ["No DNS records found."];

    $out = [];
    foreach ($records as $r) {
        $data = $r['data'] ?? $r['ip'] ?? $r['ipv6'] ?? $r['target'] ?? $r['mname'] ?? 'N/A';
        $out[] = strtoupper($r['type']) . ": " . $data;
    }
    return $out;
}

// 6. AI CODING CORE (OLLAMA CONNECTOR)
function doAiChat($prompt)
{
    $url = 'http://127.0.0.1:11434/api/generate';
    $data = [
        "model" => "llama3", // Default, can be configurable
        "prompt" => "You are an Ethical Hacking AI Assistant integrated into MCAG Toolkit. Keep answers concise, technical, and 'hacker' style.\n\nUser: $prompt",
        "stream" => false
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Fast timeout for responsiveness

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $json = json_decode($response, true);
        return $json['response'] ?? "AI Error: Invalid JSON.";
    }

    // FALLBACK (Offline Core)
    return fallbackAiResponse($prompt);
}

function fallbackAiResponse($prompt)
{
    $p = strtolower($prompt);
    if (str_contains($p, 'php'))
        return "Analyzed PHP structure. Suggest using `strict_types=1` and leveraging COMPOSER for dependency management. Check `vendor/autoload.php`.";
    if (str_contains($p, 'sql'))
        return "Detected SQL query. Ensure PDO prepared statements to prevent Injection. Use `security_token` for validation.";
    if (str_contains($p, 'hack') || str_contains($p, 'exploit'))
        return "Ethical parameters active. Authorization Code: 7-ALPHA-X. Proceed with white-hat auditing only.";

    // Greetings / General
    if (str_contains($p, 'ciao') || str_contains($p, 'hello') || str_contains($p, 'salve')) {
        return "Connessione stabilita. \nSono CORTEX AI v1.0 [OFFLINE MODE].\nOllama non rilevato sulla porta 11434.\nPosso assisterti con logica pre-programmata o analisi statica.";
    }

    return "OLLAMA LINK OFFLINE. \nUsing localized logic kernel v1.0.\nAnalyzing request: '$prompt'...\nResult: Pattern not recognized in offline database. Please launch Ollama.";
}

// Helper to prevent UTF-8 JSON crashes on Windows
function safe_utf8($str)
{
    // Force conversion from common Windows codepages if detection fails
    // Try to detect, fallback to CP850 (Western Europe DOS) which is common for "è" errors
    $encoding = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP850'], true);
    if (!$encoding)
        $encoding = 'CP850';
    return @mb_convert_encoding($str, 'UTF-8', $encoding);
}

// ACTION HANDLER
if (isset($_GET['action'])) {
    // Suppress HTML errors interfering with JSON
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ob_start(); // Buffer any stray output

    header('Content-Type: application/json');
    $act = $_GET['action'];
    $res = ['status' => 'ok', 'output' => ''];

    try {
        switch ($act) {
            case 'refresh_stats':
                $scan = scanTestsRecursive(__DIR__ . '/../../tests');
                $res['data'] = [
                    'total' => $scan['total'],
                    'timestamp' => date('H:i:s')
                ];
                break;

            case 'run_cmd':
                $input = json_decode(file_get_contents('php://input'), true);
                $cmd = $input['cmd'] ?? '';
                $mode = $input['mode'] ?? 'cmd';
                $root = realpath(__DIR__ . '/../../');
                chdir($root);

                if ($mode === 'ps') {
                    // PowerShell Execution Wrapper
                    // Use -NoProfile for speed, -ExecutionPolicy Bypass to run scripts
                    // Encode command in Base64 if needed, but direct execution is simpler for one-liners
                    $psCmd = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"$cmd\"";
                    $raw = shell_exec($psCmd . " 2>&1");
                } elseif ($mode === 'py') {
                    // Python Execution Wrapper
                    // Assumes 'python' is in PATH. Escapes double quotes for -c
                    $safeCmd = str_replace('"', '\"', $cmd);
                    $pyCmd = "python -c \"$safeCmd\"";
                    $raw = shell_exec($pyCmd . " 2>&1");
                } elseif ($mode === 'java') {
                    // JAVA SHELL SIMULATION
                    if ($cmd === 'java -version') {
                        $raw = shell_exec("java -version 2>&1");
                    } else {
                        // For generic java code, we need a class. 
                        // This shell mode is best for 'java -jar' or system checks.
                        // Interactive Java (JShell) is complex via non-interactive shell_exec.
                        $raw = shell_exec($cmd . " 2>&1");
                    }
                } else {
                    // Legacy CMD
                    $raw = shell_exec($cmd . " 2>&1");
                }

                $res['output'] = safe_utf8($raw ?? '');
                break;

            case 'fs_op':
                // FILESYSTEM OPERATIONS FOR OMNI-EDITOR
                $input = json_decode(file_get_contents('php://input'), true);
                $op = $input['op'] ?? '';
                $path = $input['path'] ?? '';
                $content = $input['content'] ?? '';
                $root = realpath(__DIR__ . '/../../');

                // Security Sanity Check (Prevent traversal outside root)
                $realPath = realpath($root . '/' . $path);
                if ($path && strpos($path, '..') !== false) {
                    $res['output'] = "Security Violation: Path Traversal detected.";
                    break;
                }

                if ($op === 'list') {
                    // scandir with file types
                    $dir = $realPath ?: $root;
                    if (is_dir($dir)) {
                        $files = scandir($dir);
                        $list = [];
                        foreach ($files as $f) {
                            if ($f === '.' || $f === '..')
                                continue;
                            $list[] = [
                                'name' => $f,
                                'type' => is_dir($dir . '/' . $f) ? 'dir' : 'file',
                                'ext' => pathinfo($f, PATHINFO_EXTENSION)
                            ];
                        }
                        $res['data'] = $list;
                    } else {
                        $res['status'] = 'error';
                        $res['output'] = "Not a directory.";
                    }
                } elseif ($op === 'read') {
                    if ($realPath && file_exists($realPath) && is_file($realPath)) {
                        $res['data'] = file_get_contents($realPath);
                    } else {
                        $res['status'] = 'error';
                        $res['output'] = "File not found.";
                    }
                } elseif ($op === 'write') {
                    // Allow creating new files
                    $target = $root . '/' . $path;
                    if (file_put_contents($target, $content) !== false) {
                        $res['output'] = "File saved successfully.";
                    } else {
                        $res['status'] = 'error';
                        $res['output'] = "Write failed.";
                    }
                }
                break;

            case 'git_status':
                $res['data'] = getGitInfo();
                break;

            case 'read_logs':
                $res['data'] = getLogTail();
                break;

            case 'purge_cache':
                $res['data'] = purgeCache();
                break;

            case 'run_test':
                $file = $_GET['file'] ?? '';
                $target = realpath(__DIR__ . '/../../' . $file);
                if ($target && file_exists($target)) {
                    $ext = pathinfo($target, PATHINFO_EXTENSION);
                    $cmd = ($ext === 'php') ? "php \"$target\"" : "\"$target\"";
                    $root = realpath(__DIR__ . '/../../');
                    chdir($root);
                    $raw = shell_exec($cmd . " 2>&1");
                    $res['output'] = safe_utf8($raw ?? '');
                } else {
                    $res['output'] = "Error: File not found.";
                }
                break;

            case 'nmap':
                $input = json_decode(file_get_contents('php://input'), true);
                $res['output'] = implode("\n", doPortScan($input['target'] ?? '127.0.0.1'));
                break;

            case 'whois':
                $input = json_decode(file_get_contents('php://input'), true);
                $res['output'] = implode("\n", doWhois($input['target'] ?? ''));
                break;

            case 'dns':
                $input = json_decode(file_get_contents('php://input'), true);
                $res['output'] = implode("\n", doDnsEnum($input['target'] ?? ''));
                break;

            case 'ping':
                $input = json_decode(file_get_contents('php://input'), true);
                $target = $input['target'] ?? '127.0.0.1';
                $target = escapeshellcmd($target);
                $res['output'] = safe_utf8(shell_exec("ping -n 4 $target 2>&1"));
                break;

            case 'traceroute':
                $input = json_decode(file_get_contents('php://input'), true);
                $target = $input['target'] ?? 'google.com';
                $target = escapeshellcmd($target);
                $res['output'] = safe_utf8(shell_exec("tracert -d $target 2>&1"));
                break;

            case 'netstat':
                $res['output'] = safe_utf8(shell_exec("netstat -an 2>&1"));
                break;

            case 'ifconfig':
                $res['output'] = safe_utf8(shell_exec("ipconfig /all 2>&1"));
                break;

            case 'ai_chat':
                $input = json_decode(file_get_contents('php://input'), true);
                $res['output'] = doAiChat($input['prompt'] ?? '');
                break;
        }
    } catch (Exception $e) {
        $res['status'] = 'error';
        $res['output'] = $e->getMessage();
    }

    // Final Safe Encode
    ob_end_clean(); // Discard any PHP warnings (HTML)
    $json = json_encode($res, JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        echo json_encode(['status' => 'error', 'output' => 'JSON ENCODING ERROR: ' . json_last_error_msg()]);
    } else {
        echo $json;
    }
    exit;
}

// INITIAL LOAD
$scan = scanTestsRecursive(__DIR__ . '/../../tests');
$tests = $scan['files'];
$totalTests = $scan['total'];
$groupedTests = [];
foreach ($tests as $t) {
    $groupedTests[$t['cat']][] = $t;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYPER-GRID // MCAG TOOLKIT</title>
    <!-- FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/all.min.css">

    <link rel="stylesheet" href="../../public/css/debug_console.css?v=<?= time() ?>">
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="brand glitch">
            <i class="fa-solid fa-microchip"></i> HYPER-GRID
            <div style="font-size: 10px; color: var(--neon-blue); letter-spacing: 1px; margin-top: 5px;">
                DIAGNOSTIC SYSTEM v7.1
            </div>
        </div>

        <div class="module-list">
            <div class="module-group-title">
                <span>TOTAL TESTS:</span>
                <span id="live-count" style="color: var(--neon-green)">
                    <?= $totalTests ?>
                </span>
            </div>

            <?php foreach ($groupedTests as $category => $modules): ?>
                <div class="module-group-title"
                    style="margin-top: 15px; border-bottom: 1px solid rgba(0,243,255,0.1); padding-bottom: 2px;">
                    <?= htmlspecialchars($category) ?>
                    <span style="font-size: 10px; opacity: 0.5">
                        [
                        <?= array_sum(array_column($modules, 'count')) ?>]
                    </span>
                </div>
                <?php foreach ($modules as $test): ?>
                    <div class="module-item" onclick="runTest('<?= $test['path'] ?>')">
                        <div style="display:flex; align-items:center; gap:8px">
                            <i class="fa-solid fa-vial"></i>
                            <span>
                                <?= str_replace('Test.php', '', $test['name']) ?>
                            </span>
                        </div>
                        <span class="badge-count">
                            <?= $test['count'] ?>t
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="module-group-title"
                style="margin-top: 30px; border-top: 1px dashed var(--neon-pink); padding-top: 10px;">System Core</div>
            <div class="module-item" onclick="fetchGitStatus()">
                <i class="fa-brands fa-git-alt"></i> GIT STATUS
            </div>
            <div class="module-item" onclick="fetchLogs()">
                <i class="fa-solid fa-scroll"></i> LOG INTERCEPTOR
            </div>
        </div>
    </div>
    <!-- MOBILE OVERLAY -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- MAIN DECK -->
    <div class="main-deck">
        <!-- TOOLBAR (New Features) -->
        <div class="top-bar">
            <!-- MOBILE TOGGLE -->
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>

            <!-- PARROT ARSENAL MENUS -->
            <div class="nav-menu">

                <?php
                // KALI & PARROT COMPLETE TOOL LIBRARY
                $toolLibrary = [
                    'INFORMATION GATHERING' => [
                        'icon' => 'fa-solid fa-radar',
                        'subcategories' => [
                            'DNS Analysis' => ['dnsenum', 'dnsmap', 'dnsrecon', 'fierce', 'dnswalk', 'dnstracer'],
                            'IDS/IPS Identification' => ['lbd', 'wafw00f', 'fragroute', 'fragrouter'],
                            'Live Host Identification' => ['arping', 'fping', 'hping3', 'masscan', 'nmap', 'netdiscover', 'thcping6'],
                            'Network & Port Scanners' => ['masscan', 'nmap', 'zenmap', 'unicornscan', 'angryip'],
                            'OSINT Analysis' => ['maltego', 'spiderfoot', 'theharvester', 'recon-ng', 'dmitry', 'creepy', 'twofi'],
                            'Route Analysis' => ['netmask', 'cutecom', 'miranda', '0trace', 'tctrace', 'traceroute'],
                            'SMB Analysis' => ['enum4linux', 'nbtscan', 'smbmap', 'samrdump'],
                            'SMTP Analysis' => ['smtp-user-enum', 'swaks'],
                            'SNMP Analysis' => ['braa', 'onesixtyone', 'snmpcheck', 'snmpwalk'],
                            'SSL Analysis' => ['sslyze', 'sslscan', 'tlssled'],
                        ]
                    ],
                    'VULNERABILITY ANALYSIS' => [
                        'icon' => 'fa-solid fa-shield-halved',
                        'subcategories' => [
                            'Fuzzing Tools' => ['bed', 'fuzz_ipv6', 'ohrwurm', 'powerfuzzer', 'sfuzz', 'spike-generic'],
                            'VoIP Tools' => ['siparmyknife', 'sipp', 'siproxd'],
                            'Web Vulnerability Scanners' => ['nikto', 'skipfish', 'wpscan', 'joomscan', 'uniscan', 'wapti', 'whatweb'],
                            'Database Assessment' => ['sqlmap', 'sqlninja', 'tnscmd10g', 'hexorbase', 'mdk3'],
                            'Cisco Tools' => ['cisco-auditing-tool', 'cisco-global-exploiter', 'cisco-ocs', 'cisco-torch'],
                            'OpenVAS/Greenbone' => ['gvm-cli', 'openvas-nasl', 'openvas-scanner']
                        ]
                    ],
                    'WEB APPLICATION ANALYSIS' => [
                        'icon' => 'fa-solid fa-globe',
                        'subcategories' => [
                            'CMS & Framework Identification' => ['blindelephant', 'plecost', 'wpscan', 'joomscan'],
                            'Web Crawlers' => ['apache-users', 'cutycapt', 'dirb', 'dirbuster', 'gobuster', 'webslayer'],
                            'Web Proxies' => ['burpsuite', 'owasp-zap', 'paros', 'proxystrike', 'vega', 'webscarab'],
                        ]
                    ],
                    'DATABASE ASSESSMENT' => [
                        'icon' => 'fa-solid fa-database',
                        'subcategories' => [
                            'SQL Injection' => ['sqlmap', 'sqlninja', 'bbqsql', 'jsql', 'sqlus'],
                            'Oracle' => ['oscanner', 'tnscmd10g'],
                            'MySQL' => ['mysql-audit'],
                        ]
                    ],
                    'PASSWORD ATTACKS' => [
                        'icon' => 'fa-solid fa-key',
                        'subcategories' => [
                            'Online Attacks' => ['hydra', 'medusa', 'ncrack', 'patator', 'thc-pptp-bruter'],
                            'Offline Attacks' => ['john', 'hashcat', 'ophcrack', 'rainbowcrack', 'chntpw', 'fcrackzip', 'hashid'],
                            'Wordlists' => ['cewl', 'crunch', 'wordlists', 'rsmangler']
                        ]
                    ],
                    'WIRELESS ATTACKS' => [
                        'icon' => 'fa-solid fa-wifi',
                        'subcategories' => [
                            '802.11 Wireless' => ['aircrack-ng', 'kismet', 'wifite', 'pixiewps', 'reaver', 'fern-wifi-cracker'],
                            'Bluetooth' => ['bluelog', 'bluemaho', 'btscanner', 'redfang', 'spooftooph'],
                            'RFID/NFC' => ['mfcuk', 'mfoc', 'mifare-classic-format'],
                            'SDR' => ['gnuradio', 'gqrx', 'kalibrate-rtl']
                        ]
                    ],
                    'REVERSE ENGINEERING' => [
                        'icon' => 'fa-solid fa-microchip',
                        'subcategories' => [
                            'Debuggers' => ['edb-debugger', 'ollydbg', 'valgrind'],
                            'Disassemblers' => ['ida-pro', 'radare2', 'capstone', 'jadx'],
                            'Decompilers' => ['apktool', 'dex2jar', 'jd-gui']
                        ]
                    ],
                    'EXPLOITATION TOOLS' => [
                        'icon' => 'fa-solid fa-skull',
                        'subcategories' => [
                            'Exploit Frameworks' => ['metasploit-framework', 'armitage', 'beef-xss', 'routersploit'],
                            'Social Engineering' => ['setoolkit', 'maltego', 'msfpc'],
                            'Payload Generators' => ['msfvenom', 'veil', 'shellter']
                        ]
                    ],
                    'SNIFFING & SPOOFING' => [
                        'icon' => 'fa-solid fa-mask',
                        'subcategories' => [
                            'Network Sniffers' => ['wireshark', 'tcpdump', 'dSniff', 'hamster-sidejack', 'netsniff-ng'],
                            'Spoofing' => ['ettercap', 'arpspoof', 'macchanger', 'mitmproxy', 'responder', 'yersinia']
                        ]
                    ],
                    'POST EXPLOITATION' => [
                        'icon' => 'fa-solid fa-ghost',
                        'subcategories' => [
                            'OS Backdoors' => ['backdoor-factory', 'cymothoa', 'dbc', 'powersploit'],
                            'Tunneling' => ['dns2tcp', 'iodine', 'miredo', 'proxychains', 'ptunnel', 'socat', 'sslh', 'stunnel4'],
                            'Web Backdoors' => ['weevely', 'webacoo', 'laudanum']
                        ]
                    ],
                    'FORENSICS' => [
                        'icon' => 'fa-solid fa-magnifying-glass',
                        'subcategories' => [
                            'Disk Analysis' => ['autopsy', 'sleuthkit', 'dc3dd', 'guymager'],
                            'Memory Forensics' => ['volatility', 'rekall'],
                            'PDF Forensics' => ['pdfid', 'pdf-parser', 'peepdf'],
                            'Carving' => ['binwalk', 'foremost', 'magicrescue', 'scalpel']
                        ]
                    ],
                    'REPORTING TOOLS' => [
                        'icon' => 'fa-solid fa-file-contract',
                        'subcategories' => [
                            'Documentation' => ['cutycapt', 'dradis', 'faraday', 'keepnote', 'magictree', 'pipal']
                        ]
                    ]
                ];
                ?>

                <!-- SYSTEM (Always Visible) -->
                <div class="dropdown">
                    <div class="dropdown-btn"><i class="fa-solid fa-server"></i> SYSTEM</div>
                    <div class="dropdown-content">
                        <div class="menu-item" onclick="fetchGitStatus()"><i class="fa-brands fa-git-alt"></i> Git
                            Status</div>
                        <div class="menu-item" onclick="fetchLogs()"><i class="fa-solid fa-scroll"></i> System Logs
                        </div>
                        <div class="menu-item" onclick="window.location.reload()"><i class="fa-solid fa-rotate"></i>
                            Reboot Deck</div>
                        <div class="menu-item danger" onclick="purgeCache()"><i class="fa-solid fa-trash-can"></i> PURGE
                            CACHE</div>
                    </div>
                </div>

                <!-- DYNAMIC KALI/PARROT MENU GENERATION -->
                <?php foreach ($toolLibrary as $category => $data): ?>
                    <div class="dropdown">
                        <div class="dropdown-btn"><i class="<?= $data['icon'] ?>"></i> <?= substr($category, 0, 4) ?>..
                        </div>
                        <div class="dropdown-content" style="width: 250px; left:0;">
                            <!-- Header for Category -->
                            <div class="menu-header"
                                style="padding: 5px 10px; color: var(--neon-blue); font-size: 10px; border-bottom: 1px solid rgba(0,243,255,0.2); margin-bottom: 5px;">
                                <?= $category ?>
                            </div>

                            <?php foreach ($data['subcategories'] as $subCat => $tools): ?>
                                <div class="dropdown-submenu">
                                    <div class="menu-item" style="font-size: 0.85em;">
                                        <?= $subCat ?> <i class="fa-solid fa-caret-right"
                                            style="margin-left:auto; opacity: 0.5;"></i>
                                    </div>
                                    <div class="dropdown-content"
                                        style="top:0; left:100%; width: 200px; max-height: 400px; overflow-y: auto;">
                                        <?php foreach ($tools as $tool): ?>
                                            <div class="menu-item"
                                                onclick="runSim('<?= $tool ?>', '<?= strtoupper($tool) ?> SEQUENCE')">
                                                <i class="fa-solid fa-terminal"
                                                    style="font-size: 0.8em; margin-right: 5px; opacity: 0.7;"></i> <?= $tool ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <div style="flex-grow: 1"></div>

            <!-- OMNI-EDITOR BTN -->
            <button class="tool-btn" id="editor-btn" style="border-color: #ffd700; color: #ffd700; margin-right: 10px;"
                onclick="toggleEditor()">
                <i class="fa-solid fa-code"></i> OMNI-EDITOR
            </button>

            <!-- SHELL SWITCHERS -->
            <button class="tool-btn shell-toggle" onclick="toggleShell('cmd')"
                style="border-color: #888; color: #888; margin-right: 5px;">CMD</button>
            <button class="tool-btn shell-toggle" onclick="toggleShell('ps')"
                style="border-color: #00f3ff; color: #00f3ff; margin-right: 5px;">PS</button>
            <button class="tool-btn shell-toggle" onclick="toggleShell('py')"
                style="border-color: #ffd700; color: #ffd700; margin-right: 10px;">PY</button>

            <!-- AI MODE TOGGLE -->
            <button class="tool-btn shell-toggle" id="ai-toggle-btn"
                style="border-color: var(--neon-pink); color: var(--neon-pink); margin-right: 10px;"
                onclick="toggleShell('ai')">
                <i class="fa-solid fa-robot"></i> AI DEV
            </button>

            <!-- GOD MODE TOGGLE -->
            <button class="tool-btn"
                style="border-color: var(--neon-green); color: var(--neon-green); margin-right: 10px;"
                onclick="toggleMode()">
                <i class="fa-solid fa-brain"></i> NEURAL LINK
            </button>
        </div>

        <!-- OMNI-EDITOR MODAL -->
        <div id="editor-modal">
            <div id="editor-header">
                <div style="color:#ffd700; font-family:'Courier New'; font-weight:bold;">
                    <i class="fa-solid fa-file-code"></i> OMNI-EDITOR v1.0
                </div>
                <div style="display:flex; gap:10px;">
                    <!-- NEW PAGE DROPDOWN -->
                    <div class="dropdown" style="display:inline-block;">
                        <button class="tool-btn new">
                            <i class="fa-solid fa-plus"></i> NEW PAGE
                        </button>
                        <div class="dropdown-content" style="min-width:150px;">
                            <a class="menu-item" onclick="createNewFile('php')"><i class="fa-brands fa-php"></i> PHP
                                File</a>
                            <a class="menu-item" onclick="createNewFile('html')"><i class="fa-brands fa-html5"></i> HTML
                                File</a>
                            <a class="menu-item" onclick="createNewFile('js')"><i class="fa-brands fa-js"></i> JS
                                File</a>
                            <a class="menu-item" onclick="createNewFile('css')"><i class="fa-brands fa-css3"></i> CSS
                                File</a>
                            <a class="menu-item" onclick="createNewFile('py')"><i class="fa-brands fa-python"></i>
                                Python</a>
                            <a class="menu-item" onclick="createNewFile('java')"><i class="fa-brands fa-java"></i>
                                Java</a>
                            <a class="menu-item" onclick="createNewFile('txt')"><i class="fa-solid fa-file-lines"></i>
                                Text</a>
                        </div>
                    </div>

                    <button onclick="saveFile()" class="tool-btn save">SAVE [Ctrl+S]</button>
                    <button onclick="runScript()" class="tool-btn run">RUN [F5]</button>
                    <button onclick="toggleEditor()" class="tool-btn danger">CLOSE</button>
                </div>
            </div>

            <div id="editor-body">
                <!-- File Browser -->
                <div id="file-browser">
                    <div onclick="loadDir('')" style="cursor:pointer; color:#ffd700;">[ROOT]</div>
                    <div id="file-list"></div>
                </div>
                <!-- Editor Area -->
                <div id="editor-main">
                    <input type="text" id="editor-filename" placeholder="/path/to/file.php">
                    <textarea id="code-area" spellcheck="false"></textarea>
                </div>
            </div>
        </div>

        <!-- TERMINAL -->
        <div class="terminal-container" id="terminal">
            <div class="terminal-output" id="output">
                SooBaDuR MoHaMmAd AjMeEr © AjDRoger © tutti diritti riservati.
                > HYPER-GRID SYSTEM INITIALIZED...
                > SCANNING FOR TESTS... DETECTED [
                <?= $totalTests ?>].
                > READY FOR INPUT.
                > _
            </div>
            <div class="terminal-input-line">
                <span>admin@hypergrid:~$</span>
                <input type="text" class="terminal-input" id="cmdInput" autofocus placeholder="Enter command...">
            </div>
        </div>
    </div>

    <!-- NEURAL CANVAS -->
    <canvas id="neural-canvas"></canvas>

    <script src="../../public/js/debug_console.js?v=<?= time() ?>"></script>
</body>

</html>