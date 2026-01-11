<?php

namespace FratellanzaMilitare\Controller\Auth;

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\SecurityLayer\SessionManager;
use FratellanzaMilitare\SecurityLayer\TotpEncryptionService;
use FratellanzaMilitare\SecurityLayer\TotpProvider;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Controller dedicato alla gestione della Two-Factor Authentication (Fase 2).
 */
/**
 * Gestisce l'Autenticazione a Due Fattori (2FA).
 * 
 * Verifica il codice TOTP inserito dall'utente dopo il login primario.
 * Se valido, eleva la sessione a 'authenticated'.
 */
class TwoFactorController
{
    private Mustache_Engine $mustache;
    private TotpProvider $totp;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
        $this->totp = new TotpProvider();
    }

    /**
     * Visualizza il form per l'inserimento del codice 2FA.
     * 
     * Genera anche il QR Code se è il primo setup o se richiesto, basandosi
     * sul segreto TOTP dell'utente (decriptato).
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function form(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (empty($_SESSION['partial_auth'])) {
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('login'))->withStatus(302);
        }

        $userId = $_SESSION['temp_user_id'];
        $db = DatabaseConnection::getConnection();
        $stmt = $db->prepare("SELECT totp_secret, username FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $rawSecret = $user['totp_secret'] ?? $_ENV['TOTP_SECRET'];
        $secret = TotpEncryptionService::getInstance()->decrypt($rawSecret) ?? $_ENV['TOTP_SECRET'];

        $viewData = [
            'secret' => $secret,
            'qr_uri' => $this->totp->getProvisioningUri($secret, 'Fratellanza (' . $user['username'] . ')')
        ];

        $html = $this->mustache->render('login_2fa', $viewData);
        $response->getBody()->write($this->wrapLayout($html, "Verifica 2FA"));
        return $response;
    }

    /**
     * Verifica il codice TOTP fornito dall'utente.
     * 
     * Se il codice è valido, completa il login, rigenera l'ID di sessione
     * e reindirizza alla dashboard principale.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function verify(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (empty($_SESSION['partial_auth'])) {
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('login'))->withStatus(302);
        }

        $userId = $_SESSION['temp_user_id'];
        $db = DatabaseConnection::getConnection();
        $stmt = $db->prepare("SELECT totp_secret FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $rawSecret = $user['totp_secret'] ?? $_ENV['TOTP_SECRET'];
        // Fix: If decrypt fails (returns null), fallback to global secret, NOT the raw ciphertext.
        $secret = TotpEncryptionService::getInstance()->decrypt($rawSecret) ?? $_ENV['TOTP_SECRET'];

        $data = $request->getParsedBody();
        $code = $data['code'] ?? '';

        if ($this->totp->verifyCode($secret, $code)) {
            // Successo finale: Login completato
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['user_role'] = $_SESSION['temp_user_role'];
            $_SESSION['username'] = $_SESSION['temp_username'];

            unset($_SESSION['partial_auth'], $_SESSION['temp_user_id'], $_SESSION['temp_user_role'], $_SESSION['temp_username']);

            SessionManager::regenerate();

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('dashboard'))->withStatus(302);
        }

        $html = $this->mustache->render('login_2fa', ['error' => "Codice 2FA non valido."]);
        $response->getBody()->write($this->wrapLayout($html, "Verifica 2FA"));
        return $response;
    }

    private function wrapLayout($content, $title)
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $baseUrl = $scriptDir === '/' ? '' : $scriptDir;

        return <<<html
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>$title</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="$baseUrl/css/premium.css">
<link rel="stylesheet" href="$baseUrl/css/auth-standalone.css">
</head><body><div class="login-wrapper">$content</div></body></html>
html;
    }
}
