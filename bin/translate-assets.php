<?php

require __DIR__ . '/../vendor/autoload.php';

use MCAG\Service\AI\AIService;
use Dotenv\Dotenv;

// Load Environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Initialize AI Service
try {
    $ai = new AIService();
    echo "[INFO] AI Service initialized using driver: " . $ai->getActiveDriverName() . "\n";
} catch (Exception $e) {
    die("[ERROR] Failed to init AI: " . $e->getMessage() . "\n");
}

// Configuration
$sourceDocs = __DIR__ . '/../docs';
$targetDocs = __DIR__ . '/../docs/en';
$langFile = __DIR__ . '/../lang/en.json';

// Ensure Target Directory Exists
if (!is_dir($targetDocs)) {
    mkdir($targetDocs, 0777, true);
}

// --- PART 1: Documentation Translation ---
echo "\n[1/2] Starting Documentation Translation...\n";
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDocs, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    if ($item->isDir()) {
        $relativePath = substr($item->getPathname(), strlen($sourceDocs));
        $newPath = $targetDocs . $relativePath;
        if (!is_dir($newPath)) {
            mkdir($newPath);
            echo " [DIR] Created $newPath\n";
        }
    } else {
        if ($item->getExtension() !== 'md')
            continue;

        $relativePath = substr($item->getPathname(), strlen($sourceDocs));
        $targetPath = $targetDocs . $relativePath;

        // Skip if exists (Incremental Build)
        if (file_exists($targetPath)) {
            echo " [SKIP] $relativePath already exists.\n";
            continue;
        }

        echo " [TRANS] Translating $relativePath... ";
        $content = file_get_contents($item->getPathname());

        // AI Translation Call
        $prompt = "Translate the following technical documentation from Italian to English. Keep all Markdown formatting, links, and code blocks intact. Do not add conversational filler.\n\n---\n\n" . substr($content, 0, 4000); // Chunk limit for prototype

        try {
            $translated = $ai->generate($prompt, "You are a professional technical translator.");
            if ($translated) {
                file_put_contents($targetPath, $translated);
                echo "DONE.\n";
            } else {
                echo "FAILED (Empty Response).\n";
            }
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

// --- PART 2: UI JSON Generation ---
echo "\n[2/2] Generating UI Language File...\n";

// In a real scenario, this would parse Mustache files. 
// For this "One-Man Army" sprint, we seed the essential keys.
$uiKeys = [
    "nav_dashboard" => "Dashboard",
    "nav_workshift" => "Turni",
    "nav_docs" => "Documenti",
    "nav_logout" => "Esci",
    "lbl_welcome" => "Benvenuto",
    "lbl_status" => "Stato Sistema"
];

$translatedUi = [];
foreach ($uiKeys as $key => $val) {
    echo " [UI] Translating '$key'...";
    $res = $ai->generate("Translate '$val' to English. Output only the translation.", "Professional Translator");
    $translatedUi[$key] = trim($res ?? $val, " \t\n\r\0\x0B\"'.");
    echo " -> " . $translatedUi[$key] . "\n";
}

// Ensure lang dir
if (!is_dir(dirname($langFile)))
    mkdir(dirname($langFile));

file_put_contents($langFile, json_encode($translatedUi, JSON_PRETTY_PRINT));
echo "\n[SUCCESS] Translation Pipeline Completed.\n";
echo " - Docs: $targetDocs\n";
echo " - Lang: $langFile\n";
