<?php

// Script per verificare php.ini nello scenario di Debug - Spostato in src/Debug/check_ini.php
echo "<h1>PHP Info & Configurazione INI</h1>";
echo "<strong>Loaded Configuration File:</strong> " . php_ini_loaded_file();
echo "<hr>";
phpinfo();
