require __DIR__ . '/../../vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
die('Access Denied: CLI only.');
}

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
$pdo = DatabaseConnection::getConnection();

// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['segreteria']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
echo "User 'segreteria' found. Resetting...\n";
$id = $user['id'];
} else {
echo "User 'segreteria' NOT found. Creating...\n";
$id = null;
}

// New Credentials
$newPass = 'segreteria';
$hash = password_hash($newPass, PASSWORD_BCRYPT);

// Generate valid Base32 secret (16 chars)
$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
$newSecret = '';
for ($i = 0; $i < 16; $i++) $newSecret .=$chars[rand(0, 31)]; // Force specific secret for reproducability if needed?
    No, let's print it. // Actually, to make it easy for the user, let's use a KNOWN secret for now so I can give them
    the code if asked? // No, I'll print the QR URI or Secret. if ($id) { $pdo->prepare("UPDATE users SET password_hash
    = ?, role = 'operator', totp_secret = ? WHERE id = ?")
    ->execute([$hash, $newSecret, $id]);
    } else {
    $pdo->prepare("INSERT INTO users (username, password_hash, role, totp_secret, created_at) VALUES (?, ?, 'operator',
    ?, datetime('now'))")
    ->execute(['segreteria', $hash, $newSecret]);
    }

    echo "DONE.\n";
    echo "Username: segreteria\n";
    echo "Password: $newPass\n";
    echo "Role: operator\n";
    echo "TOTP Secret: $newSecret\n";

    // Verify it saved
    $stmt->execute(['segreteria']);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

    } catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    }
