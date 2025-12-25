require __DIR__ . '/../../vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
die('Access Denied: CLI only.');
}

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

try {
$pdo = DatabaseConnection::getConnection();
$stmt = $pdo->prepare("SELECT id, username, role, totp_secret FROM users WHERE username = ?");
$stmt->execute(['segreteria']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
echo "User 'segreteria' NOT FOUND.\n";
} else {
echo "User Found:\n";
print_r($user);

// Also show ENV secret for comparison
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
echo "ENV TOTP_SECRET: " . $_ENV['TOTP_SECRET'] . "\n";
}

} catch (Exception $e) {
echo "Error: " . $e->getMessage() . "\n";
}