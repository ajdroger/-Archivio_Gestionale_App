<?php

// Script di test per Xdebug - Spostato in tests/xdebug_test.php
echo "<h1>Test Xdebug</h1>";
echo "<p>Controlla se VS Code si ferma qui se hai messo un breakpoint.</p>";

$saluto = "Ciao!";
$ora = date('H:i:s');

echo "$saluto Sono le $ora";

// Forza l'apertura della finestra info se Xdebug è carico
if (function_exists('xdebug_info')) {
    echo "<p>Xdebug è caricato correttamente nell'ambiente web!</p>";
    echo "<p><a href='../src/Debug/check_ini.php'>Verifica il file php.ini completo</a></p>";
} else {
    echo "<p style='color:red;'>Xdebug NON è caricato in questo contesto!</p>";
}
