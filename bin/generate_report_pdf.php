<?php

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "Inizio generazione PDF...\n";

// Configuration
$inputFile = __DIR__ . '/../public/reports/MCAG_Benchmark_2026_v5.4.0.html';
$outputFile = __DIR__ . '/../public/reports/MCAG_Benchmark_2026_v5.4.0.pdf';

if (!file_exists($inputFile)) {
    die("Errore: I file HTML di input non esiste: $inputFile\n");
}

// Read HTML
$html = file_get_contents($inputFile);

// Improve CSS for PDF (Dompdf has limitations compared to browsers)
// We replace some classes or add specific styles for PDF
$pdfCss = '
<style>
    body { font-family: sans-serif; background-color: #fff; color: #000; }
    .glass-card { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; background: #fff; }
    .text-white { color: #000 !important; }
    .text-light { color: #333 !important; }
    .table-dark { color: #000; }
    .table-dark th, .table-dark td { border-color: #000; }
    .badge { border: 1px solid #000; color: #000; padding: 5px; }
    a { text-decoration: none; color: #000; }
    .btn { display: none; } /* Hide buttons in PDF */
    h1, h2, h3 { color: #000; }
    .bg-black { background-color: #eee !important; color: #000; }
</style>
';

// Inject PDF CSS
$html = str_replace('</head>', $pdfCss . '</head>', $html);
// Remove dark theme attribute
$html = str_replace('data-bs-theme="dark"', '', $html);

// Initialize Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // For remote CSS/Fonts if needed
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
echo "Rendering PDF (potrebbe richiedere qualche secondo)...\n";
$dompdf->render();

// Output the generated PDF to file
file_put_contents($outputFile, $dompdf->output());

echo "PDF generato con successo: $outputFile\n";
