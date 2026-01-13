<?php

/**
 * Legacy Aliases for Backward Compatibility
 * 
 * This file maps old "FratellanzaMilitare" classes to their new "MCAG" counterparts.
 * Usage: require_once __DIR__ . '/legacy_aliases.php';
 */

$classMap = [
    'FratellanzaMilitare\\Controller\\HomeController' => 'MCAG\\Controller\\HomeController',
    'FratellanzaMilitare\\GestioneSoci\\SocioRepository' => 'MCAG\\GestioneSoci\\SocioRepository',
    'FratellanzaMilitare\\SecurityLayer\\SessionManager' => 'MCAG\\SecurityLayer\\SessionManager',
    // Add other critical classes here if needed
];

foreach ($classMap as $old => $new) {
    if (!class_exists($old) && class_exists($new)) {
        class_alias($new, $old);
    }
}
