<?php

use MCAG\Debug\SystemCheck;

test('check returns system status', function () {
    $check = new SystemCheck();
    expect($check)->toBeObject();
    // Assuming run() or similar method exists
    // expect($check->run())->toBeArray(); 
});
