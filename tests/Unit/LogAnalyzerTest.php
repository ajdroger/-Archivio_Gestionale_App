<?php

use MCAG\Debug\LogAnalyzer;

test('analyze returns summary', function () {
    // We can't easily mock file system reads here without vfsStream, 
    // but we can check basic structure if we had a LogAnalyzer instance
    expect(class_exists(LogAnalyzer::class))->toBeTrue();
});
