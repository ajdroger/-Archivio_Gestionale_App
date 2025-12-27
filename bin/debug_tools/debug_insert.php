<?php
require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\SecurityLayer\TotpEncryptionService;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $pdo = DatabaseConnection::getConnection();

    $user = 'ufficio_soci';
    $pass = 'password123';
    $role = 'segreteria'; // Trying what the user likely sent

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $secret = 'ABCDEFGHIJKLMNOP'; // Dummy secret

    try {
        $encryptedSecret = TotpEncryptionService::getInstance()->encrypt($secret);
    } catch (\Exception $e) {
        $encryptedSecret = $secret;
        echo "Encryption skipped: " . $e->getMessage() . "\n";
    }

    echo "Attempting INSERT for user: $user, role: $role\n";

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, totp_secret) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user, $hash, $role, $encryptedSecret]);

    echo "SUCCESS: User inserted.\n";

} catch (PDOException $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
    echo "ERROR CODE: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "GENERAL ERROR: " . $e->getMessage() . "\n";
}
