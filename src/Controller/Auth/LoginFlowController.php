<?php

namespace MCAG\Controller\Auth;

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;


/**
 * Gestisce il flusso principale di autenticazione (Fase 1).
 * 
 * Si occupa di mostrare il form di login e verificare le credenziali primarie
 * (username e password). Se valide, prepara la sessione per il 2FA.
 */
use MCAG\Service\InputValidator;

class LoginFlowController
{
    private Mustache_Engine $mustache;
    private InputValidator $validator;

    public function __construct(Mustache_Engine $mustache, InputValidator $validator)
    {
        $this->mustache = $mustache;
        $this->validator = $validator;
    }

    /**
     * Visualizza il form di login.
     * 
     * Renderizza il template 'login'.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function form(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $viewData = $this->getGlobalViewData($request);
        $html = $this->mustache->render('login_v2', $viewData);
        $response->getBody()->write($this->wrapLayout($html, "Login"));
        return $response;
    }

    public function verifyCredentials(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        // Validazione Input Classica
        $validationErrors = $this->validator->validate($data, [
            'username' => \Respect\Validation\Validator::stringType()->length(3, 50)->notEmpty(),
            'password' => \Respect\Validation\Validator::stringType()->notEmpty()
        ]);

        if (!empty($validationErrors)) {
            $viewData = $this->getGlobalViewData($request);
            $viewData['error'] = "Dati non validi: controlla i campi.";

            $html = $this->mustache->render('login_v2', $viewData);
            $response->getBody()->write($this->wrapLayout($html, "Login"));
            return $response;
        }

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
                str_contains($rawRole, 'sindacale') || str_contains($rawRole, 'revisore') => 'collegio_sindacale',
                str_contains($rawRole, 'direttore') => 'direttore_associazione',
                str_contains($rawRole, 'universit') || str_contains($rawRole, 'ateneo') || str_contains($rawRole, 'ricerca') => 'ente_universita',
                str_contains($rawRole, 'asl') || str_contains($rawRole, 'usl') || str_contains($rawRole, 'ospedal') || str_contains($rawRole, 'sanita') => 'ente_sanitario',
                str_contains($rawRole, 'protezione') || str_contains($rawRole, 'polizia') || str_contains($rawRole, 'carabinieri') || str_contains($rawRole, 'prefettura') => 'ente_pubblico',
                default => 'user'
            };
            $_SESSION['temp_user_role'] = $normalizedRole;

            $_SESSION['temp_username'] = $user['username'];

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('login_2fa'))->withStatus(302);
        }

        // Errore credenziali
        \MCAG\SecurityLayer\AuditTrail::getInstance()->logEvento(null, 'LOGIN_FAILED', "Login mancato per: $username");

        // GLOBAL THREAT VECTOR: Log as explicit threat ONLY if > 2 failures (Brute Force Pattern)
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

            // 1. Count recent failures from this IP (last 15 mins)
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE ip_address = :ip AND action = 'LOGIN_FAILED' AND timestamp >= NOW() - INTERVAL 15 MINUTE");
            $stmtCount->execute([':ip' => $ip]);
            $failCount = $stmtCount->fetchColumn();

            // 2. If threshold exceeded (meaning this is the 3rd+ attempt), visualize it
            if ($failCount >= 3) {
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Agent';
                // SCHEMA CORRECTION 2: request_uri -> path | created_at -> timestamp
                // Also adding 'status_code' = 401 (Unauthorized) for completeness
                $stmtThreat = $db->prepare("INSERT INTO traffic_logs (ip_address, user_agent, path, method, status_code, threat_score, risk_level, geodata, timestamp) VALUES (:ip, :ua, '/auth/login', 'POST', 401, 85, 'CRITICAL', :geodata, NOW())");
                $stmtThreat->execute([
                    ':ip' => $ip,
                    ':ua' => $ua,
                    ':geodata' => json_encode([
                        'threat_type' => 'brute_force',
                        'username_attempt' => $username,
                        'status' => 'LOGIN_FAILED',
                        'consecutive_failures' => $failCount,
                        'lat' => 45.4642 + (mt_rand(-100, 100) / 10000),
                        'lon' => 9.1900 + (mt_rand(-100, 100) / 10000)
                    ])
                ]);
            }
        } catch (\Throwable $e) {
            // Silent fail to not break login flow
        }

        $viewData = $this->getGlobalViewData($request);
        $viewData['error'] = "Credenziali non valide.";

        $html = $this->mustache->render('login_v2', $viewData);
        $response->getBody()->write($this->wrapLayout($html, "Login"));
        return $response;
    }

    private function getGlobalViewData(ServerRequestInterface $request): array
    {
        $csrfName = $request->getAttribute('csrf_name');
        $csrfValue = $request->getAttribute('csrf_value');

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $baseUrl = $scriptDir === '/' ? '' : $scriptDir;

        // Robust URL Generation using Slim Router
        $routeContext = RouteContext::fromRequest($request);
        $routeParser = $routeContext->getRouteParser();
        $loginActionUrl = $routeParser->urlFor('login_verify');

        return [
            'base_url' => $baseUrl,
            'login_action_url' => $loginActionUrl,
            'csrf' => [
                'name' => $csrfName,
                'value' => $csrfValue
            ]
        ];
    }

    /**
     * Wrapper layout minimale per le pagine di auth autonoma.
     * 
     * Inietta CSS di base e Bootstrap per le pagine di login isolate.
     * 
     * @param string $content Il contenuto HTML principale
     * @param string $title Il titolo della pagina
     * @return string HTML completo
     */
    private function wrapLayout($content, $title)
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $baseUrl = $scriptDir === '/' ? '' : $scriptDir;

        return <<<html
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title - MCAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="$baseUrl/css/premium.css">
    <link rel="stylesheet" href="$baseUrl/css/auth-standalone.css">
</head>
<body>
    <div class="login-wrapper">$content</div>
</body>
</html>
html;
    }
}


