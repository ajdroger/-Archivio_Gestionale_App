<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

$pdo = DatabaseConnection::getConnection();
$total = $pdo->query("SELECT COUNT(*) FROM soci")->fetchColumn();
echo "TOTAL SOCI: $total\n";

echo "STATUS DISTRIBUTION:\n";
$stmt = $pdo->query("SELECT stato_iscrizione, COUNT(*) as c FROM soci GROUP BY stato_iscrizione");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- {$row['stato_iscrizione']}: {$row['c']}\n";
}

$morosi = $pdo->query("SELECT COUNT(*) FROM soci s LEFT JOIN documenti d ON s.codice_fiscale = d.socio_cf AND d.tipo_documento = 'MODULO_ISCRIZIONE' AND d.anno_solare = 2025 WHERE d.id_univoco IS NULL")->fetchColumn();
echo "SOCI SENZA DOCUMENTO 2025 (MOROSI POTENZIALI): $morosi\n";
