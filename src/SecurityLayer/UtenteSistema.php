<?php

namespace FratellanzaMilitare\SecurityLayer;

abstract class UtenteSistema
{
    public int $ID;
    public string $Username;
    protected string $PasswordHash;
    public string $Token2FA;

    /**
     * Autentica l'utente verificando username e password
     * @param string $password Password in chiaro da verificare
     * @param string|null $codice2FA Codice TOTP/2FA a 6 cifre (opzionale)
     * @return bool True se autenticazione riuscita
     */
    public function autentica(string $password, ?string $codice2FA = null): bool
    {
        // Verifica password usando password_verify
        if (!password_verify($password, $this->PasswordHash)) {
            return false;
        }

        // Se necessario, rehash della password con algoritmo più sicuro
        if (password_needs_rehash($this->PasswordHash, PASSWORD_DEFAULT)) {
            $this->PasswordHash = password_hash($password, PASSWORD_DEFAULT);
            // In produzione: salvare il nuovo hash nel database
        }

        // Verifica 2FA se presente
        if (!empty($this->Token2FA)) {
            // Se il token 2FA è configurato, è obbligatorio fornire il codice
            if ($codice2FA === null) {
                return false; // 2FA richiesto ma non fornito
            }

            // Verifica il codice TOTP
            if (!$this->verificaTOTP($codice2FA)) {
                return false; // Codice 2FA non valido
            }
        }

        // Log dell'accesso
        AuditTrail::getInstance()->logEvento(
            $this,
            'LOGIN',
            "user_{$this->ID}"
        );

        return true;
    }

    /**
     * Verifica un codice TOTP (Time-Based One-Time Password)
     * @param string $codice Codice a 6 cifre da verificare
     * @return bool True se il codice è valido
     */
    protected function verificaTOTP(string $codice): bool
    {
        $provider = new TotpProvider();
        return $provider->verifyCode($this->Token2FA, $codice);
    }


    /**
     * Cambia la password dell'utente
     * @param string $oldPassword Vecchia password
     * @param string $newPassword Nuova password
     * @throws \InvalidArgumentException
     */
    public function cambiaPassword(string $oldPassword, string $newPassword): void
    {
        // Verifica la vecchia password
        if (!password_verify($oldPassword, $this->PasswordHash)) {
            throw new \InvalidArgumentException('Vecchia password non corretta');
        }

        // Valida la nuova password (minimo 8 caratteri)
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('La nuova password deve essere di almeno 8 caratteri');
        }

        // Hash della nuova password
        $this->PasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Log del cambio password
        AuditTrail::getInstance()->logEvento(
            $this,
            'PASSWORD_CHANGE',
            "user_{$this->ID}"
        );

        // In produzione: salvare nel database
    }

    /**
     * Verifica se l'utente ha un determinato permesso
     * @param string $permesso Nome del permesso da verificare
     * @return bool
     */
    public function hasPermission(string $permesso): bool
    {
        $acl = new AccessControlList();
        return $acl->verificaPermesso($this, $permesso);
    }

    /**
     * Imposta la password iniziale dell'utente (per registrazione)
     * @param string $password Password in chiaro
     */
    public function impostaPassword(string $password): void
    {
        // Valida la password (minimo 8 caratteri)
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('La password deve essere di almeno 8 caratteri');
        }

        // Hash della password
        $this->PasswordHash = password_hash($password, PASSWORD_DEFAULT);
    }
}
