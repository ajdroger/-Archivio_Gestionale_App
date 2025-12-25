<?php

require __DIR__ . '/../../vendor/autoload.php';

// Registra la gestione globale degli errori
$logger = new \Monolog\Logger('generate_docs');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/../../logs/app.log', \Monolog\Logger::DEBUG));
\FratellanzaMilitare\Debug\GlobalExceptionHandler::registerGlobalHandlers($logger);

use FratellanzaMilitare\GestioneSoci\Socio; // Forza l'autoloading per sicurezza

$srcDir = __DIR__ . '/../src';
$outputFile = __DIR__ . '/../Documentazione/API_REFERENCE.md';
$docContent = "# Riferimento Tecnico API\n\nGenerato automaticamente via `bin/generate_docs.php` il " . date('d/m/Y H:i:s') . "\n\n";

// Mappa il prefisso logico del Namespace al percorso fisico
$psr4Prefix = 'FratellanzaMilitare\\';
$srcDirPath = realpath($srcDir);

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDirPath));
$classesFound = [];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        // Converte il percorso in un namespace
        $fullPath = $file->getRealPath();
        $relativePath = substr($fullPath, strlen($srcDirPath) + 1);
        $relativePath = str_replace('.php', '', $relativePath);
        $className = $psr4Prefix . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        if (class_exists($className) || interface_exists($className) || trait_exists($className) || (function_exists('enum_exists') && enum_exists($className))) {
            $classesFound[] = $className;
        }
    }
}

sort($classesFound);

foreach ($classesFound as $className) {
    $ref = new ReflectionClass($className);

    // Salta le classi interne di PHP per sicurezza
    if (!$ref->isUserDefined())
        continue;

    $docContent .= "## " . $ref->getShortName() . "\n";
    $docContent .= "**Namespace:** `" . $ref->getNamespaceName() . "`\n\n";

    if ($ref->isInterface()) {
        $docContent .= "*Interfaccia*\n\n";
    } elseif ($ref->isAbstract()) {
        $docContent .= "*Classe Astratta*\n\n";
    } elseif (function_exists('enum_exists') && enum_exists($className)) {
        $docContent .= "*Enum*\n\n";
    } else {
        $docContent .= "*Classe*\n\n";
    }

    // Metodi
    $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
    if (!empty($methods)) {
        $docContent .= "### Metodi\n";
        foreach ($methods as $method) {
            if ($method->isConstructor())
                continue;

            $params = [];
            foreach ($method->getParameters() as $param) {
                $type = $param->getType() ? $param->getType()->getName() : 'mixed';
                $params[] = ($param->isOptional() ? '[' : '') . "$type $" . $param->getName() . ($param->isOptional() ? ']' : '');
            }
            $paramStr = implode(', ', $params);
            $returnType = $method->getReturnType() ? ": " . $method->getReturnType()->getName() : '';

            $docContent .= "- **" . $method->getName() . "**(" . $paramStr . ")" . $returnType . "\n";
        }
        $docContent .= "\n";
    }

    $docContent .= "---\n\n";
}

file_put_contents($outputFile, $docContent);

// NUOVO: Genera un log di esecuzione separato
$logFile = __DIR__ . '/../logs/generate_docs.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}
$logEntry = "[" . date('Y-m-d H:i:s') . "] Documentazione generata. Classi trovate: " . count($classesFound) . "\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

echo "Documentazione generata in: {$outputFile}\n";
echo "Log aggiornato in: {$logFile}\n";


