<?php

require __DIR__ . '/../../vendor/autoload.php';

// Registra la gestione globale degli errori
// Registra la gestione globale degli errori
$logger = new \Monolog\Logger('test_fatal');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/../../logs/test.log', \Monolog\Logger::DEBUG));
\FratellanzaMilitare\Debug\GlobalExceptionHandler::registerGlobalHandlers($logger);

echo "Simulazione Crash Sistema...\n";

// Questo dovrebbe essere catturato dal Gestore Globale delle Eccezioni (GlobalExceptionHandler)
throw new Exception("Errore Critico Simulato per Test Globale");
