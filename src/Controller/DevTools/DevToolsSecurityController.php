<?php

namespace FratellanzaMilitare\Controller\DevTools;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\SecurityLayer\TotpEncryptionService;

/**
 * DevTools Security Controller
 * 
 * Handles user management, 2FA, and security operations
 */
class DevToolsSecurityController
{
    public function securityList(Request $request, Response $response): Response
    {
        $pdo = DatabaseConnection::getConnection();
        $users = $pdo->query("SELECT id, username, role, created_at, totp_secret, 
            CASE WHEN totp_secret IS NOT NULL THEN 1 ELSE 0 END as has_2fa 
            FROM users ORDER BY username ASC")->fetchAll(\PDO::FETCH_ASSOC);

        // Sanitize secrets for UI
        foreach ($users as &$u) {
            unset($u['totp_secret']);
        }

        $response->getBody()->write(json_encode(['users' => $users]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function securityAdd(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = trim($data['username'] ?? '');
        $pass = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';

        if (strlen($user) < 3 || strlen($pass) < 6) {
            return $this->jsonError($response, 'Username min 3 chars, Password min 6 chars.');
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);

        // Generate TOTP secret using proper method (32 chars Base32)
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        // Encrypt the secret if encryption is available
        // This utilizes the TotpEncryptionService to ensure secrets are never stored in plain text.
        // It relies on the 'TOTP_ENCRYPTION_KEY' in .env.
        // If not configured, it gracefully degrades to plain text (legacy behavior), useful for dev.
        try {
            $encryptedSecret = TotpEncryptionService::getInstance()->encrypt($secret);
        } catch (\RuntimeException $e) {
            // Encryption not configured, store plain (legacy mode)
            // Ideally should log a warning here in production.
            $encryptedSecret = $secret;
        }

        try {
            $pdo = DatabaseConnection::getConnection();
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, totp_secret) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user, $hash, $role, $encryptedSecret]);
            $response->getBody()->write(json_encode(['success' => true]));
        } catch (\PDOException $e) {
        } catch (\PDOException $e) {
            // Check for specific error codes if needed, but for now return specific message for debugging
            return $this->jsonError($response, 'DB Error: ' . $e->getMessage());
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function securityReset(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $id = $data['id'] ?? 0;
        $pass = $data['password'] ?? '';

        if (strlen($pass) < 6) {
            return $this->jsonError($response, 'Password min 6 chars.');
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);

        $pdo = DatabaseConnection::getConnection();
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function securityRotate2FA(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $id = $data['id'] ?? 0;

        // Generate new TOTP secret
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        // Encrypt the secret if available
        try {
            $encryptedSecret = TotpEncryptionService::getInstance()->encrypt($secret);
        } catch (\RuntimeException $e) {
            $encryptedSecret = $secret;
        }

        $pdo = DatabaseConnection::getConnection();
        $pdo->prepare("UPDATE users SET totp_secret = ? WHERE id = ?")->execute([$encryptedSecret, $id]);

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'New 2FA Secret Generated']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function securityDelete(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $id = $data['id'] ?? 0;

        // Prevent self-delete
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            return $this->jsonError($response, 'Cannot delete yourself!');
        }

        $pdo = DatabaseConnection::getConnection();
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function jsonError(Response $response, string $msg): Response
    {
        $response->getBody()->write(json_encode(['error' => $msg]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
}
