<?php

use FratellanzaMilitare\SecurityLayer\RedisSessionHandler;
use Predis\Client;

beforeEach(function () {
    $this->redis = Mockery::mock(Client::class);
    $this->handler = new RedisSessionHandler($this->redis);
});

test('read returns data from redis', function () {
    $this->redis->shouldReceive('get')
        ->once()
        ->with('session:test_id')
        ->andReturn('session_data');

    expect($this->handler->read('test_id'))->toBe('session_data');
});

test('read returns empty string if key missing', function () {
    $this->redis->shouldReceive('get')
        ->once()
        ->with('session:missing_id')
        ->andReturn(null);

    expect($this->handler->read('missing_id'))->toBe('');
});

test('write saves data to redis with TTL', function () {
    $this->redis->shouldReceive('setex')
        ->once()
        ->with('session:id123', 3600, 'data')
        ->andReturn(true);

    expect($this->handler->write('id123', 'data'))->toBeTrue();
});

test('destroy deletes key from redis', function () {
    $this->redis->shouldReceive('del')
        ->once()
        ->with('session:idToKill')
        ->andReturn(1);

    expect($this->handler->destroy('idToKill'))->toBeTrue();
});
