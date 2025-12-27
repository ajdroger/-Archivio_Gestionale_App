<?php

namespace FratellanzaMilitare\Controller\Auth;

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Controller dedicato al flusso di autenticazione iniziale (Fase 1).
 */
class LoginFlowController
{
    private Mustache_Engine $mustache;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    /**
     * Visualizza il form di login.
     */
    public function form(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = $this->mustache->render('login', []);
        $response->getBody()->write($this->wrapLayout($html, "Login"));
        return $response;
    }

    /**
     * Verifica le credenziali dell'utente (Fase 1).
     */
    public function verifyCredentials(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $db = DatabaseConnection::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Autenticazione Parziale Successo
            $_SESSION['partial_auth'] = true;
            $_SESSION['temp_user_id'] = $user['id'];

            // Normalize Role (ACL)
            $rawRole = strtolower($user['role']);
            $normalizedRole = match (true) {
                str_contains($rawRole, 'amministratore') || $rawRole === 'admin' => 'admin',
                str_contains($rawRole, 'segreteria') => 'segreteria',
                str_contains($rawRole, 'presidente') => 'presidente',
                default => 'user'
            };
            $_SESSION['temp_user_role'] = $normalizedRole;

            $_SESSION['temp_username'] = $user['username'];

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('login_2fa'))->withStatus(302);
        }

        // Errore credenziali
        $html = $this->mustache->render('login', ['error' => "Credenziali non valide."]);
        $response->getBody()->write($this->wrapLayout($html, "Login"));
        return $response;
    }

    /**
     * Wrapper layout minimale per le pagine di auth autonoma.
     */
    private function wrapLayout($content, $title)
    {
        return <<<html
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title - Fratellanza Militare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/premium.css">
    <style>
        body { background: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .login-wrapper { width: 100%; max-width: 500px; }
    </style>
</head>
<body>
    <div class="login-wrapper">$content</div>
</body>
</html>
html;
    }
}
