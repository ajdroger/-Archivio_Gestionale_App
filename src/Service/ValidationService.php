<?php

namespace FratellanzaMilitare\Service;

class ValidationService
{
    /**
     * Valida la correttezza formale di un Codice Fiscale italiano
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
     * Valida un indirizzo email
     */
    public function isValidEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Valida un file caricato (estensione e MIME type)
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
}
