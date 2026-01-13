<?php

namespace Tests\Unit\Service;

use MCAG\Service\RedisService;
use PHPUnit\Framework\TestCase;
use Predis\Client;

// Define a concrete class that extends Client but exposes the methods we need to mock
class MockRedisClient extends Client
{
    public function get($key)
    {
    }
    public function set($key, $value)
    {
    }
    public function setex($key, $seconds, $value)
    {
    }
    public function del($keys)
    {
    }
    public function flushdb()
    {
    }
    public function keys($pattern)
    {
    }
    public function incrby($key, $increment)
    {
    }
    public function expire($key, $seconds)
    {
    }
    public function connect()
    {
    }
}

class RedisServiceTest extends TestCase
{
    private $predisMock;
    private $redisService;

    protected function setUp(): void
    {
        // Mock the helper class which has explicit methods
        $this->predisMock = $this->createMock(MockRedisClient::class);
        $this->redisService = new RedisService($this->predisMock);
    }

    public function testGetDeserializesValue()
    {
        $this->predisMock->expects($this->once())
            ->method('get')
            ->with('key')
            ->willReturn(serialize('value'));

        $this->assertEquals('value', $this->redisService->get('key'));
    }

    public function testGetReturnsNullOnFailure()
    {
        $this->predisMock->expects($this->once())
            ->method('get')
            ->willThrowException(new \Exception('Redis down'));

        $this->assertNull($this->redisService->get('key'));
    }

    public function testSetSerializesValue()
    {
        $this->predisMock->expects($this->once())
            ->method('set')
            ->with('key', serialize(['a' => 1]))
            ->willReturn(true);

        $this->assertTrue($this->redisService->set('key', ['a' => 1]));
    }

    public function testSetWithTtl()
    {
        $this->predisMock->expects($this->once())
            ->method('setex')
            ->with('key', 60, serialize('value'))
            ->willReturn(true);

        $this->assertTrue($this->redisService->set('key', 'value', 60));
    }

    public function testDeleteDelegatesToClient()
    {
        $this->predisMock->expects($this->once())
            ->method('del')
            ->with(['key'])
            ->willReturn(1);

        $this->assertTrue($this->redisService->delete('key'));
    }

    public function testRememberReturnsCachedValue()
    {
        $this->predisMock->expects($this->once())
            ->method('get')
            ->with('key')
            ->willReturn(serialize('cached'));

        $result = $this->redisService->remember('key', function () {
            return 'new';
        });
        $this->assertEquals('cached', $result);
    }

    public function testRememberComputesAndStoresValue()
    {
        $this->predisMock->expects($this->once())
            ->method('get')
            ->with('key')
            ->willReturn(null);

        $this->predisMock->expects($this->once())
            ->method('setex')
            ->with('key', 3600, serialize('new'))
            ->willReturn(true);

        $result = $this->redisService->remember('key', function () {
            return 'new';
        });
        $this->assertEquals('new', $result);
    }

    public function testDisabledServiceReturnsDefaults()
    {
        // Simulate disabled by passing empty config array and setting ENV false if necessary
        // Or simpler: force disabled by ensuring no client injected and env empty.
        // But constructor logic is: if config/client passed, logic runs.
        // We can create a partial mock or new instance.

        // Let's rely on the fact that if we don't pass a client, and ENV is missing, it defaults to disabled.
        putenv('REDIS_ENABLED=false');
        $disabledService = new RedisService();

        $this->assertNull($disabledService->get('any'));
        $this->assertFalse($disabledService->set('any', 'val'));
        $this->assertFalse($disabledService->isEnabled());
    }
}
