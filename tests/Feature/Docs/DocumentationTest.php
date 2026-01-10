<?php

test('api docs ui loads correctly', function () {
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/api/docs');
    $response = $this->app->handle($request);

    if ($response->getStatusCode() !== 200) {
        fwrite(STDERR, "UI Error Body: " . (string) $response->getBody() . "\n");
    }

    expect($response->getStatusCode())->toBe(200);
    expect((string) $response->getBody())->toContain('<div id="swagger-ui"></div>');
});

test('api docs json returns valid openapi spec', function () {
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory)->createServerRequest('GET', '/api/docs/json');
    $response = $this->app->handle($request);

    if ($response->getStatusCode() !== 200) {
        fwrite(STDERR, "Error Body: " . (string) $response->getBody() . "\n");
    }

    expect($response->getStatusCode())->toBe(200);
    expect($response->getHeaderLine('Content-Type'))->toContain('application/json');

    $json = json_decode((string) $response->getBody(), true);

    expect($json)->not->toBeNull();
    expect($json['openapi'])->toContain('3.0.0');
    expect($json['info']['title'])->toContain('MCAG');

    // Check if paths are present
    expect($json['paths'])->toHaveKey('/api/v1/soci');
    expect($json['paths'])->toHaveKey('/health');
});
