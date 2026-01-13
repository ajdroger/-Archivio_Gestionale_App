<?php

namespace MCAG\SecurityLayer;

class SessionManager
{
    /**
     * Rigenera l'ID di sessione in modo sicuro per prevenire il Session Fixation.
     * Deve essere chiamato ad ogni cambio di stato (es. dopo il login).
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
        }
    }

    /**
     * Distrugge la sessione corrente in modo completo.
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
        }
    }
}


