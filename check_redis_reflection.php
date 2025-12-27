<?php
require 'vendor/autoload.php';

$r = new ReflectionClass(Predis\Client::class);
echo "Has get? " . ($r->hasMethod('get') ? 'YES' : 'NO') . "\n";
echo "Has __call? " . ($r->hasMethod('__call') ? 'YES' : 'NO') . "\n";
echo "Is Final? " . ($r->isFinal() ? 'YES' : 'NO') . "\n";
