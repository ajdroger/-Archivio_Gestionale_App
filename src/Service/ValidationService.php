<?php

namespace FratellanzaMilitare\Service;

/**
 * Servizio di validazione dati centralizzato.
 * 
 * Contiene regole per la verifica formale di Codici Fiscali, Email e File Upload.
 */
class ValidationService
{
    /**
     * Valida la correttezza formale di un Codice Fiscale italiano.
     * 
     * Utilizza una Regex rigorosa per verificare la struttura (es. 6 lettere, 2 numeri, 1 lettera, etc).
     * Non verifica l'esistenza reale della persona presso l'Agenzia delle Entrate, solo il formato.
     */
    public function isValidCodiceFiscale(string $cf): bool
    {
        // Regex rigorosa:
        // 6 char (Cognome+Nome)
        // 2 char (Anno - cifre o omocodia)
        // 1 char (Mese - solo [ABCDEHLMPRST])
        // 2 char (Giorno - cifre o omocodia)
        // 1 char (Inizio Belfiore - [A-Z])
        // 3 char (Fine Belfiore - cifre o omocodia)
        // 1 char (CIN - [A-Z])
        $regex = '/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/';

        if (!preg_match($regex, $cf)) {
            return false;
        }
        return true;
    }

    /**
     * Valida un indirizzo email.
     */
    public function isValidEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Valida un file caricato (estensione e MIME type).
     * 
     * @param string $mimeType Tipo MIME del file
     * @param int $size Dimensione in bytes
     * @return bool True se il file è accettabile (tipo supportato e < 5MB)
     */
    public function isValidFileUpload(string $mimeType, int $size): bool
    {
        $allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($mimeType, $allowedTypes)) {
            return false;
        }

        // Max 5MB
        if ($size > 5 * 1024 * 1024) {
            return false;
        }

        return true;
    }

    /**
     * Valida il MIME type reale del file analizzando i Magic Bytes (primi bytes del file).
     * Previene attacchi di spoofing dell'estensione (es. shell.php rinominata in shell.jpg).
     */
    public function validateRealMimeType(string $filePath): bool
    {
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip' // Aggiunto per compatibilità Word/Office
        ];

        // Usa finfo per determinare il MIME type dal contenuto
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($filePath);



        // Gestione caso speciale per file Office (docx, xlsx) che sono tecnicamente ZIP
        if ($realMime === 'application/zip' || $realMime === 'application/x-zip-compressed') {
            return true;
        }

        // FALLBACK: Se finfo ritorna octet-stream, verifichiamo manualmente i magic bytes per ZIP
        if ($realMime === 'application/octet-stream') {
            $handle = fopen($filePath, 'rb');
            if ($handle) {
                $header = fread($handle, 4);
                fclose($handle);
                if ($header === "\x50\x4B\x03\x04") { // PK..
                    return true;
                }
            }
        }

        return in_array($realMime, $allowedMimes);
    }

    /**
     * Scansione Malware (Placeholder per integrazione AV).
     * 
     * In futuro integrerà ClamAV o servizi esterni (VirusTotal API).
     */
    public function scanForMalware(string $filePath): bool
    {
        /* 
        // TODO: UNCOMMENT WHEN CLAMAV IS AVAILABLE
        // Requires: sudo apt-get install clamav && composer require appwrite/clamav

        try {
            $clam = new \Appwrite\ClamAV\Network('127.0.0.1', 3310);
            if (!$clam->ping()) {
                // AV non disponibile, fail-open o fail-close in base a policy
                error_log("ClamAV non raggiungibile");
                return true; 
            }

            // Scan
            $result = $clam->fileScan($filePath);
            return $result === true; // true = clean

        } catch (\Exception $e) {
            error_log("ClamAV Error: " . $e->getMessage());
            return true; // Fail-open per garantire servizio
        }
        */

        return true; // Placeholder: sempre pulito
    }
}
