<?php

namespace FratellanzaMilitare\Controller;

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

/**
 * Controller per la gestione delle impostazioni utente.
 * 
 * Gestisce la visualizzazione del profilo, i dettagli account dell'utente corrente
 * e la funzionalità di cambio password sicuro.
 */
class SettingsController
{
    private Mustache_Engine $mustache;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    /**
     * Visualizza la pagina delle impostazioni utente.
     * 
     * Recupera i dati dell'utente loggato dal database e renderizza
     * il template 'impostazioni'. Gestisce i redirect se l'utente non è autenticato.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function view(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $loginUrl = $routeParser->urlFor('login');
        $logoutUrl = $routeParser->urlFor('logout');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->withHeader('Location', $loginUrl)->withStatus(302);
        }

        $db = DatabaseConnection::getConnection();
        $stmt = $db->prepare("SELECT username, role, created_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return $response->withHeader('Location', $logoutUrl)->withStatus(302);
        }

        // Add formatted fields for UI
        $user['last_login'] = date('d/m/Y H:i'); // Placeholder for session-based last login
        $user['email'] = "ajmeer03@gmail.com";

        $user['email'] = "ajmeer03@gmail.com";

        $html = $this->mustache->render('impostazioni', [ // Restored original template name
            'title' => 'Impostazioni Profilo',
            'user' => $user,
            'user_initial' => strtoupper(substr($user['username'] ?? 'U', 0, 1)),
            'success' => $request->getAttribute('flash_success'),
            'error' => $request->getAttribute('flash_error'),
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Gestisce l'aggiornamento della password utente.
     * 
     * Valida i dati in input (vecchia password, nuova, conferma),
     * verifica la correttezza della vecchia password e aggiorna l'hash nel DB.
     * Imposta messaggi flash di successo o errore.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updatePassword(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $data = $request->getParsedBody();
        $oldPass = $data['old_password'] ?? '';
        $newPass = $data['new_password'] ?? '';
        $confPass = $data['confirm_password'] ?? '';

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $redirectUrl = $routeParser->urlFor('settings');

        // Validation
        if (empty($oldPass) || empty($newPass) || empty($confPass)) {
            $_SESSION['flash_error'] = "Tutti i campi password sono obbligatori.";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        if ($newPass !== $confPass) {
            $_SESSION['flash_error'] = "La nuova password e la conferma non coincidono.";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        if (strlen($newPass) < 8) {
            $_SESSION['flash_error'] = "La nuova password deve essere di almeno 8 caratteri.";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        $db = DatabaseConnection::getConnection();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($oldPass, $user['password_hash'])) {
            $_SESSION['flash_error'] = "La password attuale non è corretta.";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        // Update
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $update = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $update->execute([':hash' => $newHash, ':id' => $userId]);

        $_SESSION['flash_success'] = "Password aggiornata con successo.";
        return $response->withHeader('Location', $redirectUrl)->withStatus(302);
    }
}
