require __DIR__ . '/../../vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
die('Access Denied: CLI only.');
}

use FratellanzaMilitare\SecurityLayer\TotpProvider;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
// 1. Get Secret from DB
$pdo = DatabaseConnection::getConnection();
$stmt = $pdo->prepare("SELECT totp_secret FROM users WHERE username = ?");
$stmt->execute(['segreteria']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
die("User 'segreteria' not found.\n");
}

$secret = $user['totp_secret'];
echo "Secret in DB: $secret\n";

// 2. Generate Code using App's Provider
$provider = new TotpProvider();
$code = $provider->getCode($secret);

echo "\n=== LOGIN CODE for 'segreteria' ===\n";
echo "Current Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "CODE: $code\n";
echo "====================================\n";

} catch (Exception $e) {
echo "Error: " . $e->getMessage() . "\n";
}