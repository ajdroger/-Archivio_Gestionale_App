<?php

/**
 * Dependency Injection Registry
 * 
 * Restituisce un elenco di file di definizione da caricare nel ContainerBuilder.
 * Questo approccio previene i limiti di inferenza dei tipi dell'IDE 
 * distribuendo le definizioni su più file analizzati singolarmente.
 */
return [
    __DIR__ . '/definitions/core.php',
    __DIR__ . '/definitions/services.php',
    __DIR__ . '/definitions/auth.php',
    __DIR__ . '/definitions/anagrafica.php',
    __DIR__ . '/definitions/intelligence.php',
    __DIR__ . '/definitions/devtools.php',
];
