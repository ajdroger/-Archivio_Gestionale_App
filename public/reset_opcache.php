<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<h1>OPcache Reset Successful</h1>";
    echo "<p>Please try reloading the DevTools Toolkit now.</p>";
} else {
    echo "<h1>OPcache not enabled</h1>";
    echo "<p>If you are still seeing the error, please restart your web server (Ampps).</p>";
}
echo "<a href='/MCAG_Militare-Civile-Archivio-Gestionale/public/devtools'>Back to DevTools</a>";

