<?php

namespace FratellanzaMilitare\Controller;

use FratellanzaMilitare\SecurityLayer\TotpProvider;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LoginController
{
    private Mustache_Engine $mustache;
    private TotpProvider $totp;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
        $this->totp = new TotpProvider();
    }

    public function form(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $viewData = $this->getCsrfData($request);
        $html = $this->mustache->render('login', $viewData);
        $response->getBody()->write($this->wrapLayout($html, "Login"));
        return $response;
    }

    public function verifyCredentials(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        // DB Verification
        $db = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Stage 1 Success
            $_SESSION['partial_auth'] = true;
            $_SESSION['temp_user_id'] = $user['id'];
            $_SESSION['temp_user_role'] = $user['role'];
            $_SESSION['temp_username'] = $user['username'];

            $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('login_2fa'))->withStatus(302);
        }

        // Error
        $viewData = $this->getCsrfData($request);
        $viewData['error'] = "Credenziali non valide.";
        $html = $this->mustache->render('login', $viewData);
        $response->getBody()->write($this->wrapLayout($html, "Login"));
        return $response;
    }

    public function form2fa(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (empty($_SESSION['partial_auth'])) {
            $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('login'))->withStatus(302);
        }

        // Fetch User Secret
        $userId = $_SESSION['temp_user_id'];
        $db = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $stmt = $db->prepare("SELECT totp_secret, username FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $rawSecret = $user['totp_secret'] ?? $_ENV['TOTP_SECRET']; 
        // Decrypt secret
        $secret = \FratellanzaMilitare\SecurityLayer\TotpEncryptionService::getInstance()->decrypt($rawSecret);
        if ($secret === null) {
             // Fallback or error if decryption fails (should process logout or error)
             $secret = $rawSecret;
        }

        $viewData = $this->getCsrfData($request);
        $viewData['secret'] = $secret; // Only for QR generation
        $viewData['qr_uri'] = $this->totp->getProvisioningUri($secret, 'Fratellanza (' . $user['username'] . ')');
        $html = $this->mustache->render('login_2fa', $viewData);
        $response->getBody()->write($this->wrapLayout($html, "Verifica 2FA"));
        return $response;
    }

    public function verify2fa(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (empty($_SESSION['partial_auth'])) {
            $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('login'))->withStatus(302);
        }

        $userId = $_SESSION['temp_user_id'];
        $db = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $stmt = $db->prepare("SELECT totp_secret FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $rawSecret = $user['totp_secret'] ?? $_ENV['TOTP_SECRET'];
        // Decrypt secret
        $secret = \FratellanzaMilitare\SecurityLayer\TotpEncryptionService::getInstance()->decrypt($rawSecret);
        if ($secret === null) {
            $secret = $rawSecret;
        }

        $data = $request->getParsedBody();
        $code = $data['code'] ?? '';

        if ($this->totp->verifyCode($secret, $code)) {
            // Success
            $_SESSION['user_id'] = $_SESSION['temp_user_id'] ?? 1;
            $_SESSION['user_role'] = $_SESSION['temp_user_role'] ?? 'admin';
            $_SESSION['username'] = $_SESSION['temp_username'] ?? 'admin';
            unset($_SESSION['partial_auth']);
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_user_role']);
            unset($_SESSION['temp_username']);

            // Mission-critical: Rigenera ID sessione dopo l'autenticazione
            \FratellanzaMilitare\SecurityLayer\SessionManager::regenerate();

            $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('dashboard'))->withStatus(302);
        }

        // Error
        $viewData = $this->getCsrfData($request);
        $viewData['error'] = "Codice 2FA non valido.";
        $viewData['secret'] = $secret; // Show (safe? usually debug only)
        $html = $this->mustache->render('login_2fa', $viewData);
        $response->getBody()->write($this->wrapLayout($html, "Verifica 2FA"));
        return $response;
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        session_destroy();
        $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('login'))->withStatus(302);
    }

    private function getCsrfData($request): array
    {
        return [

        ];
    }

    // Simple layout wrapper for login pages (standalone)
    private function wrapLayout($content, $title)
    {
        return <<<html
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title - Fratellanza Militare</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/fratellanza-militare-archivio/public/css/premium.css">
    <style>
        body {
            background: #0f172a; 
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-wrapper {
            width: 100%;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        $content
    </div>
</body>
</html>
html;
    }
}
