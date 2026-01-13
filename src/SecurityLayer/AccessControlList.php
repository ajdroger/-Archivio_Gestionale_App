<?php

namespace MCAG\SecurityLayer;

class AccessControlList
{
    private static array $permessi = [
        'Amministratore' => ['*'], // Accesso completo
        'Operatore' => [
            'soci.read',
            'soci.update',
            'documenti.read',
            'documenti.create',
            'documenti.delete',
            'report.generate'
        ],
        'Utente' => [
            'soci.read'
        ]
    ];

    /**
     * Verifica se un utente ha permesso per una specifica risorsa
     * @param UtenteSistema $utente Utente da verificare
     * @param string $risorsa Nome della risorsa (es: 'soci.update')
     * @return bool True se l'utente ha il permesso
     */
    public function verificaPermesso(UtenteSistema $utente, string $risorsa): bool
    {
        // Determina il ruolo dell'utente
        $ruolo = $this->getRuoloUtente($utente);

        // Se il ruolo non esiste, nega l'accesso
        if (!isset(self::$permessi[$ruolo])) {
            return false;
        }

        // Amministratore ha accesso completo
        if (in_array('*', self::$permessi[$ruolo])) {
            return true;
        }

        // Verifica se il permesso specifico esiste
        return in_array($risorsa, self::$permessi[$ruolo]);
    }

    /**
     * Concede un permesso a un ruolo
     * @param string $ruolo Nome del ruolo
     * @param string $permesso Nome del permesso da concedere
     */
    public function grant(string $ruolo, string $permesso): void
    {
        // Inizializza l'array se il ruolo non esiste
        if (!isset(self::$permessi[$ruolo])) {
            self::$permessi[$ruolo] = [];
        }

        // Aggiungi il permesso se non già presente
        if (!in_array($permesso, self::$permessi[$ruolo])) {
            self::$permessi[$ruolo][] = $permesso;
        }

        // In produzione: salvare nel database
    }

    /**
     * Determina il ruolo di un utente
     * @param UtenteSistema $utente
     * @return string
     */
    private function getRuoloUtente(UtenteSistema $utente): string
    {
        // Usa il nome della classe come ruolo
        $className = get_class($utente);
        return basename(str_replace('\\', '/', $className));
    }

    /**
     * Ottieni tutti i permessi di un ruolo
     * @param string $ruolo
     * @return array
     */
    public function getPermessi(string $ruolo): array
    {
        return self::$permessi[$ruolo] ?? [];
    }
}


