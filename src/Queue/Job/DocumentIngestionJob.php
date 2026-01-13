<?php

namespace MCAG\Queue\Job;

use MCAG\Service\AI\DocumentIngestionService;

class DocumentIngestionJob implements JobInterface
{
    private string $filePath;
    private string $filename;

    public function __construct(string $filePath, string $filename)
    {
        $this->filePath = $filePath;
        $this->filename = $filename;
    }

    public function handle($container): void
    {
        /** @var DocumentIngestionService $ingestionService */
        $ingestionService = $container->get(DocumentIngestionService::class);

        echo "Start processing: {$this->filename}\n";

        $chunksCreated = $ingestionService->ingest($this->filePath);

        echo "Finished: {$chunksCreated} chunks created for {$this->filename}\n";
    }
}


