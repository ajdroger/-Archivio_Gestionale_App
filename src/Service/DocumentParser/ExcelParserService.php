<?php

namespace MCAG\Service\DocumentParser;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelParserService implements DocumentParserInterface
{
    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        if (!$this->supports($filePath)) {
            throw new \RuntimeException("Unsupported file type (expected Excel Spreadsheet)");
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $output = "";

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $output .= "### Sheet: " . $sheet->getTitle() . "\n";
                // Convert to CSV for structure preservation which LLMs understand well
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
                $writer->setSheetIndex($spreadsheet->getIndex($sheet));

                ob_start();
                $writer->save('php://output');
                $csvData = ob_get_clean();

                $output .= "```csv\n" . $csvData . "\n```\n\n";
            }

            return trim($output);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error parsing Excel document: " . $e->getMessage(), 0, $e);
        }
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, ['xlsx', 'xls', 'csv', 'ods']);
    }
}


