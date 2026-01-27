<?php

namespace Tests\Unit\Integration;

use Tests\TestCase;
use MCAG\Integration\ERP\ZucchettiAdapter;

class ZucchettiAdapterTest extends TestCase
{
    public function test_it_connects_with_api_key()
    {
        $adapter = new ZucchettiAdapter('https://mock.api', 'key-123');
        $this->assertTrue($adapter->connect());
    }

    public function test_it_fails_connect_without_api_key()
    {
        $adapter = new ZucchettiAdapter('https://mock.api', '');
        $this->assertFalse($adapter->connect());
    }

    public function test_it_syncs_mock_employees()
    {
        $adapter = new ZucchettiAdapter('https://mock.api', 'key-123');
        $adapter->connect();

        $employees = $adapter->syncEmployees('2026-01-01');

        $this->assertIsArray($employees);
        $this->assertCount(2, $employees); // Based on mock implementation
        $this->assertEquals('Mario', $employees[0]['first_name']);
    }

    public function test_it_provides_correct_name()
    {
        $adapter = new ZucchettiAdapter('url', 'key');
        $this->assertEquals('Zucchetti HR Suite', $adapter->getProviderName());
    }
}
