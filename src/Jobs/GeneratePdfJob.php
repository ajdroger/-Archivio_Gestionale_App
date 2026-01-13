<?php

declare(strict_types=1);

namespace MCAG\Jobs;

use MCAG\Service\PdfGenerationService;

/**
 * Background Job per Generazione PDF
 */
class GeneratePdfJob extends AbstractJob
{
    protected string $queue = 'reports';

    private PdfGenerationService $pdfService;
    private string $template;
    private array $data;
    private string $outputPath;

    public function __construct(
        PdfGenerationService $pdfService,
        string $template,
        array $data,
        string $outputPath
    ) {
        $this->pdfService = $pdfService;
        $this->template = $template;
        $this->data = $data;
        $this->outputPath = $outputPath;
    }

    public function handle(): void
    {
        // Assuming $this->data holds the necessary parameters
        $pdf = $this->pdfService->generateRegistrationReceipt(
            $this->data['socio'],
            $this->data['amount'],
            $this->data['year']
        );
        file_put_contents($this->outputPath, $pdf);
    }

    protected function getJobData(): array
    {
        return [
            'type' => 'generate_pdf',
            'template' => $this->template,
            'output_path' => $this->outputPath,
        ];
    }
}


