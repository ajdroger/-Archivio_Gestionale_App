<?php

namespace Tests\Unit\Service;

use FratellanzaMilitare\Service\CacheService;
use FratellanzaMilitare\Service\RedisService;
use PHPUnit\Framework\TestCase;

class CacheServiceTest extends TestCase
{
    private $redisMock;
    private $cacheService;

    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(RedisService::class);
        $this->cacheService = new CacheService($this->redisMock);
    }

    public function testGetReturnsValueFromRedis()
    {
        $this->redisMock->expects($this->once())
            ->method('get')
            ->with('fm:cache:test_key')
            ->willReturn('cached_value');

        $result = $this->cacheService->get('test_key');

        $this->assertEquals('cached_value', $result);
        $this->assertEquals(1, $this->cacheService->getStats()['hits']);
    }

    public function testGetReturnsNullOnMiss()
    {
        $this->redisMock->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $result = $this->cacheService->get('missing_key');

        $this->assertNull($result);
        $this->assertEquals(1, $this->cacheService->getStats()['misses']);
    }

    public function testSetDelegatesToRedis()
    {
        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('fm:cache:key', 'value', 3600)
            ->willReturn(true);

        $result = $this->cacheService->set('key', 'value');

        $this->assertTrue($result);
    }

    public function testRememberReturnsCachedValue()
    {
        $this->redisMock->expects($this->once())
            ->method('remember')
            ->with('fm:cache:key', $this->anything(), 3600)
            ->willReturn('cached_data');

        $result = $this->cacheService->remember('key', function () {
            return 'new_data'; });

        $this->assertEquals('cached_data', $result);
    }

    public function testInvalidateSociCallsDeletePattern()
    {
        $this->redisMock->expects($this->once())
            ->method('deletePattern')
            ->with('fm:cache:soci:*')
            ->willReturn(5);

        $count = $this->cacheService->invalidateSoci();

        $this->assertEquals(5, $count);
    }

    public function testRememberSociListGeneratesCorrectKey()
    {
        $filters = ['status' => 'active'];
        $expectedKey = 'fm:cache:soci:list:' . md5(serialize($filters));

        $this->redisMock->expects($this->once())
            ->method('remember')
            ->with($expectedKey, $this->anything(), 300)
            ->willReturn([]);

        $this->cacheService->rememberSociList($filters, function () {
            return []; });
    }
}
