<?php
/**
 * MCAG AI Translation Pipeline v2.0 (Professional Edition)
 * 
 * Usage: php bin/translate-assets.php [sdk]
 * 
 * Objectives:
 * 1. Discover translatable assets (`lang/it.json`, `Documentazione/*.md`).
 * 2. Connect to Local LLM API (Ollama/LocalAI) via OpenAI-compatible endpoint.
 * 3. Generate high-fidelity technical translations (EN, FR, DE).
 * 4. Save results maintaining directory structure.
 * 
 * Requires: Network connection to localhost:11434 (default) or config via .env
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// 1. Load Configuration
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$API_URL = $_ENV['AI_API_URL'] ?? 'http://localhost:11434/v1/chat/completions';
$MODEL_ID = $_ENV['AI_MODEL_ID'] ?? 'llama3:latest';
$TARGET_LANGS = ['en', 'fr', 'de'];

echo "------------------------------------------------------\n";
echo "   MCAG Professional Translation Engine               \n";
echo "   Target Model: $MODEL_ID                            \n";
echo "   Endpoint:     $API_URL                             \n";
echo "------------------------------------------------------\n";

if (!function_exists('curl_init')) {
    die("FATAL: PHP CURL extension is required.\n");
}

// 2. HTTP Client Wrapper (Robust CURL implementation)
function call_llm($prompt, $systemPrompt)
{
    global $API_URL, $MODEL_ID;

    $payload = [
        'model' => $MODEL_ID,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.1, // Low temp for factual accuracy
        'max_tokens' => 4000 // Allow long responses
    ];

    $ch = curl_init($API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer sk-mcag-local-token' // Dummy token logic for some local servers
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 mins timeout per chunk

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "   [ERROR] Connection failed: " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "   [ERROR] API returned $httpCode: $response\n";
        return null;
    }

    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

// 3. Translation Logic for JSON (Key-Value)
function translate_json($sourceFile, $langs)
{
    echo "[*] Processing JSON Asset: " . basename($sourceFile) . "\n";
    $content = json_decode(file_get_contents($sourceFile), true);

    foreach ($langs as $lang) {
        echo "   -> Translating to [" . strtoupper($lang) . "]... ";

        $prompt = "Translate the following JSON values to {$lang}. " .
            "Keep keys exactly as they are. Output ONLY valid JSON.\n\n" .
            json_encode($content, JSON_PRETTY_PRINT);

        $system = "You are a professional technical translator for Enterprise Software. " .
            "Translate values accurately. Do not translate technical keys like 'id', 'class'.";

        $translated = call_llm($prompt, $system);

        if ($translated) {
            // Cleanup Markdown code blocks if model adds them
            $cleanJson = preg_replace('/^```json\s*|\s*```$/', '', trim($translated));
            $targetPath = str_replace('it.json', "{$lang}.json", $sourceFile);

            // Validate JSON
            if (json_decode($cleanJson)) {
                file_put_contents($targetPath, $cleanJson);
                echo "DONE.\n";
            } else {
                echo "FAILED (Invalid JSON Output).\n";
            }
        } else {
            echo "SKIPPED (API Error).\n";
        }
    }
}

// 4. Translation Logic for Markdown (Document stream)
function translate_markdown($sourceFile, $langs)
{
    if (strpos($sourceFile, 'node_modules') !== false || strpos($sourceFile, 'vendor') !== false)
        return;

    echo "[*] Processing Doc: " . basename($sourceFile) . "\n";
    $content = file_get_contents($sourceFile);

    if (strlen($content) > 100) { // Skip empty files
        foreach ($langs as $lang) {
            echo "   -> Translating to [" . strtoupper($lang) . "]... ";

            $prompt = "Translate the following Technical Documentation to {$lang}. " .
                "Preserve all Markdown formatting, code blocks, and links exactly.\n\n" .
                $content;

            $system = "You are a senior technical writer. Translate the content to {$lang} maintaining professional tone. " .
                "Do not translate code blocks, variable names, or file paths.";

            $translated = call_llm($prompt, $system);

            if ($translated) {
                // Determine target path (e.g., Guide/en/FILE.md or just FILE_en.md)
                // For structure simplicity: FILE_en.md
                $info = pathinfo($sourceFile);
                $targetPath = $info['dirname'] . '/' . $info['filename'] . "_{$lang}." . $info['extension'];

                file_put_contents($targetPath, $translated);
                echo "DONE.\n";
            } else {
                echo "FAILED.\n";
            }
        }
    }
}

// 5. Execution Pipeline
// A. JSON Language Files
$jsonFiles = glob(__DIR__ . '/../lang/*.json');
foreach ($jsonFiles as $file) {
    if (basename($file) === 'it.json') {
        translate_json($file, $TARGET_LANGS);
    }
}

// B. Documentation (Limit to key files for demo speed)
// In prod: RecursiveIterator
$priorityDocs = [
    __DIR__ . '/../README.md',
    __DIR__ . '/../Documentazione/Analisi/ANALISI_SWOT_MCAG_v8.3.0_2026-01-27.md'
];

foreach ($priorityDocs as $doc) {
    if (file_exists($doc)) {
        translate_markdown($doc, $TARGET_LANGS);
    }
}

echo "------------------------------------------------------\n";
echo "   Pipeline Finalized. \n";
echo "------------------------------------------------------\n";
