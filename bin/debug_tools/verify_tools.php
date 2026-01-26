<?php
// Verify Hyper Grid Tools Installation Status
require __DIR__ . '/../../vendor/autoload.php';

// Tool Library (Copied from test_dashboard.php)
$toolLibrary = [
    'INFORMATION GATHERING' => [
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
    ],
    'VULNERABILITY ANALYSIS' => [
        'Fuzzing Tools' => ['bed', 'fuzz_ipv6', 'ohrwurm', 'powerfuzzer', 'sfuzz', 'spike-generic'],
        'VoIP Tools' => ['siparmyknife', 'sipp', 'siproxd'],
        'Web Vulnerability Scanners' => ['nikto', 'skipfish', 'wpscan', 'joomscan', 'uniscan', 'wapti', 'whatweb'],
        'Database Assessment' => ['sqlmap', 'sqlninja', 'tnscmd10g', 'hexorbase', 'mdk3'],
        'Cisco Tools' => ['cisco-auditing-tool', 'cisco-global-exploiter', 'cisco-ocs', 'cisco-torch'],
        'OpenVAS/Greenbone' => ['gvm-cli', 'openvas-nasl', 'openvas-scanner']
    ],
    'WEB APPLICATION ANALYSIS' => [
        'CMS & Framework Identification' => ['blindelephant', 'plecost', 'wpscan', 'joomscan'],
        'Web Crawlers' => ['apache-users', 'cutycapt', 'dirb', 'dirbuster', 'gobuster', 'webslayer'],
        'Web Proxies' => ['burpsuite', 'owasp-zap', 'paros', 'proxystrike', 'vega', 'webscarab'],
    ],
    'DATABASE ASSESSMENT' => [
        'SQL Injection' => ['sqlmap', 'sqlninja', 'bbqsql', 'jsql', 'sqlus'],
        'Oracle' => ['oscanner', 'tnscmd10g'],
        'MySQL' => ['mysql-audit'],
    ],
    'PASSWORD ATTACKS' => [
        'Online Attacks' => ['hydra', 'medusa', 'ncrack', 'patator', 'thc-pptp-bruter'],
        'Offline Attacks' => ['john', 'hashcat', 'ophcrack', 'rainbowcrack', 'chntpw', 'fcrackzip', 'hashid'],
        'Wordlists' => ['cewl', 'crunch', 'wordlists', 'rsmangler']
    ],
    'WIRELESS ATTACKS' => [
        '802.11 Wireless' => ['aircrack-ng', 'kismet', 'wifite', 'pixiewps', 'reaver', 'fern-wifi-cracker'],
        'Bluetooth' => ['bluelog', 'bluemaho', 'btscanner', 'redfang', 'spooftooph'],
        'RFID/NFC' => ['mfcuk', 'mfoc', 'mifare-classic-format'],
        'SDR' => ['gnuradio', 'gqrx', 'kalibrate-rtl']
    ],
    'REVERSE ENGINEERING' => [
        'Debuggers' => ['edb-debugger', 'ollydbg', 'valgrind'],
        'Disassemblers' => ['ida-pro', 'radare2', 'capstone', 'jadx'],
        'Decompilers' => ['apktool', 'dex2jar', 'jd-gui']
    ],
    'EXPLOITATION TOOLS' => [
        'Exploit Frameworks' => ['metasploit-framework', 'armitage', 'beef-xss', 'routersploit'],
        'Social Engineering' => ['setoolkit', 'maltego', 'msfpc'],
        'Payload Generators' => ['msfvenom', 'veil', 'shellter']
    ],
    'SNIFFING & SPOOFING' => [
        'Network Sniffers' => ['wireshark', 'tcpdump', 'dSniff', 'hamster-sidejack', 'netsniff-ng'],
        'Spoofing' => ['ettercap', 'arpspoof', 'macchanger', 'mitmproxy', 'responder', 'yersinia']
    ],
    'POST EXPLOITATION' => [
        'OS Backdoors' => ['backdoor-factory', 'cymothoa', 'dbc', 'powersploit'],
        'Tunneling' => ['dns2tcp', 'iodine', 'miredo', 'proxychains', 'ptunnel', 'socat', 'sslh', 'stunnel4'],
        'Web Backdoors' => ['weevely', 'webacoo', 'laudanum']
    ],
    'FORENSICS' => [
        'Disk Analysis' => ['autopsy', 'sleuthkit', 'dc3dd', 'guymager'],
        'Memory Forensics' => ['volatility', 'rekall'],
        'PDF Forensics' => ['pdfid', 'pdf-parser', 'peepdf'],
        'Carving' => ['binwalk', 'foremost', 'magicrescue', 'scalpel']
    ],
    'REPORTING TOOLS' => [
        'Documentation' => ['cutycapt', 'dradis', 'faraday', 'keepnote', 'magictree', 'pipal']
    ]
];

// Prerequisites
$prereqs = ['git', 'python', 'java', 'perl', 'pip', 'choco', 'winget'];
$status = [];

echo "### PREREQUISITES ###\n";
foreach ($prereqs as $prog) {
    if (PHP_OS_FAMILY === 'Windows') {
        $check = shell_exec("where $prog 2>nul");
    } else {
        $check = shell_exec("which $prog 2>/dev/null");
    }
    $exists = !empty($check); // Using boolean for simplicity, empty string means not found
    $status['prereqs'][$prog] = $exists;
    echo sprintf("%-15s: %s\n", $prog, $exists ? "INSTALLED" : "MISSING");
}

echo "\n### HYPER GRID TOOLS ###\n";
foreach ($toolLibrary as $cat => $subcats) {
    foreach ($subcats as $sub => $tools) {
        foreach ($tools as $tool) {
            if (isset($status['tools'][$tool]))
                continue; // Skip duplicates

            if (PHP_OS_FAMILY === 'Windows') {
                $check = shell_exec("where $tool 2>nul");
            } else {
                $check = shell_exec("which $tool 2>/dev/null");
            }
            $exists = !empty($check);
            $status['tools'][$tool] = $exists;

            if ($exists) {
                echo sprintf("%-25s: [ OK ]\n", $tool);
            }
        }
    }
}

// Summary
$installed = count(array_filter($status['tools']));
$total = count($status['tools']);
echo "\n### SUMMARY ###\n";
echo "Total Tools Defined: $total\n";
echo "Installed: $installed\n";
echo "Missing: " . ($total - $installed) . "\n";
echo "\n";
