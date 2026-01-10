<?php

namespace FratellanzaMilitare\Service;

use Dompdf\Dompdf;
use FratellanzaMilitare\GestioneSoci\Socio;

/**
 * Servizio wrapper per la generazione di PDF con Dompdf.
 * 
 * Fornisce metodi rapidi per generare documenti standard come ricevute e attestati.
 */
class PdfGenerationService
{
    /**
     * Genera la ricevuta di iscrizione in formato PDF.
     * 
     * @param Socio $socio
     * @param float $amount Importo versato
     * @param int $year Anno di riferimento
     * @return string Contenuto binario del PDF
     */
    public function generateRegistrationReceipt(Socio $socio, float $amount, int $year): string
    {
        // Template HTML (could be moved to an external .mustache file later)
        $html = "<h1>Modulo Iscrizione $year</h1>";
        $html .= "<p>Si attesta che il socio <strong>{$socio->DatiPersonali->Nome} {$socio->DatiPersonali->Cognome}</strong></p>";
        $html .= "<p>Codice Fiscale: <strong>{$socio->CodiceFiscale}</strong></p>";
        $html .= "<p>Ha versato regolarmente la quota associativa di <strong>&euro; " . number_format($amount, 2, ',', '.') . "</strong>.</p>";
        $html .= "<p>Data: " . date('d/m/Y') . "</p>";
        $html .= "<p style='margin-top: 50px; font-size: 0.8em; color: #666;'>Documento generato automaticamente dal sistema gestionale MCAG (Militare Civile Archivio Gestionale).</p>";

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
