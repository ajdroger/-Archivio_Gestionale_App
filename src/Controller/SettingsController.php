<?php

namespace FratellanzaMilitare\Controller;

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

class SettingsController
{
    private Mustache_Engine $mustache;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    public function view(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $db = DatabaseConnection::getConnection();
        $stmt = $db->prepare("SELECT username, role, created_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return $response->withHeader('Location', '/logout')->withStatus(302);
        }

        // Add formatted fields for UI
        $user['last_login'] = date('d/m/Y H:i'); // Placeholder for session-based last login
        $user['email'] = "ajmeer03@gmail.com";

        $html = $this->mustache->render('settings', [
            'title' => 'Impostazioni Profilo',
            'user' => $user,
            'user_initial' => strtoupper(substr($user['username'] ?? 'U', 0, 1)),
            'success' => $request->getAttribute('flash_success'),
            'error' => $request->getAttribute('flash_error'),
        ]);

        $response->getBody()->write($html);
        return $response;
    }

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
