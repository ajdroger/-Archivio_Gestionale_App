<?php

namespace FratellanzaMilitare\Service;

use Dompdf\Dompdf;
use FratellanzaMilitare\GestioneSoci\Socio;

class PdfGenerationService
{
    public function generateRegistrationReceipt(Socio $socio, float $amount, int $year): string
    {
        // Template HTML (could be moved to an external .mustache file later)
        $html = "<h1>Modulo Iscrizione $year</h1>";
        $html .= "<p>Si attesta che il socio <strong>{$socio->DatiPersonali->Nome} {$socio->DatiPersonali->Cognome}</strong></p>";
        $html .= "<p>Codice Fiscale: <strong>{$socio->CodiceFiscale}</strong></p>";
        $html .= "<p>Ha versato regolarmente la quota associativa di <strong>&euro; " . number_format($amount, 2, ',', '.') . "</strong>.</p>";
        $html .= "<p>Data: " . date('d/m/Y') . "</p>";
        $html .= "<p style='margin-top: 50px; font-size: 0.8em; color: #666;'>Documento generato automaticamente dal sistema gestionale Fratellanza Militare.</p>";

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
