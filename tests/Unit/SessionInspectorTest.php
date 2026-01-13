<?php

use MCAG\Debug\SessionInspector;

test('inspect returns session data structure', function () {
    // SessionInspector uses $_SESSION directly, which is hard to mock in pest without helpers
    // But we can check if it returns the expected structure even if empty
    $data = SessionInspector::inspect();

    expect($data)->toBeArray();
    if ($data['status'] === 'ACTIVE') {
        expect($data)->toHaveKeys(['id', 'data']);
    } else {
        expect($data)->toHaveKeys(['status', 'message']);
    }
});
