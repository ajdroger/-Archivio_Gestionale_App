<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', 'mysql');
    $stmt = $pdo->query('SHOW DATABASES');
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Databases found:\n";
    foreach ($dbs as $db) {
        echo "- $db\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

