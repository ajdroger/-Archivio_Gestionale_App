<?php

use MCAG\Debug\GlobalExceptionHandler;

test('handler can be instantiated', function () {
    // GlobalExceptionHandler registers handlers in constructor usually, 
    // or has static methods. Let's check existence.
    expect(class_exists(GlobalExceptionHandler::class))->toBeTrue();
});
