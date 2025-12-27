<?php
chdir(__DIR__ . '/../../');
$cmd = 'vendor\\bin\\phinx migrate -vvv 2>&1';
exec($cmd, $output, $return);
file_put_contents(__DIR__ . '/../../logs/phinx_error.log', implode("\n", $output));
echo "Phinx executed. Return: $return. Log saved.\n";
