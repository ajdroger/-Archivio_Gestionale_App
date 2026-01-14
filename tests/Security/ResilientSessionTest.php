<?php

use MCAG\SecurityLayer\SessionManager;

test('SessionManager rigenera l\'ID di sessione correttamente', function () {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $oldId = session_id();
    SessionManager::regenerate();
    $newId = session_id();

    expect($newId)->not->toBe($oldId);
    expect(strlen($newId))->toBeGreaterThan(10);
});

test('verifica configurazione sicura dei cookie di sessione', function () {
    // Nota: ini_get ritorna le impostazioni attuali dell'ambiente PHP
    // Per un sistema Mission-critical, questi devono essere forzati in index.php

    expect(ini_get('session.cookie_httponly'))->toBe("1");
    // SameSite potrebbe essere espresso come stringa "Strict" o "Lax"
    expect(strtolower(ini_get('session.cookie_samesite')))->toBe("strict");
    expect(ini_get('session.use_only_cookies'))->toBe("1");
});
