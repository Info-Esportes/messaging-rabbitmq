<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Tests\Unit;

use InfoEsportes\Messaging\Consumer\ConsumerConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \InfoEsportes\Messaging\Consumer\ConsumerConfig
 */
class ConsumerConfigTest extends TestCase
{
    public function testCreatesConfigWithDefaults(): void
    {
        $config = new ConsumerConfig([]);

        $this->assertSame('127.0.0.1', $config->getHost());
        $this->assertSame(5672, $config->getPort());
        $this->assertSame('guest', $config->getUser());
        $this->assertSame('guest', $config->getPassword());
        $this->assertSame('/', $config->getVhost());
        $this->assertSame('messages.topic', $config->getExchange());
        $this->assertSame(3.0, $config->getConnectionTimeout());
        $this->assertSame(30.0, $config->getReadTimeout());
        $this->assertSame(3.0, $config->getWriteTimeout());
        $this->assertSame(30, $config->getHeartbeat());
        $this->assertTrue($config->isKeepalive());
        $this->assertSame(1, $config->getPrefetchCount());
        $this->assertFalse($config->isAutoAck());
        $this->assertFalse($config->isNoLocal());
        $this->assertFalse($config->isExclusive());
        $this->assertFalse($config->isNoWait());
    }

    public function testCreatesConfigWithCustomValues(): void
    {
        $config = new ConsumerConfig([
            'host' => 'rabbitmq.example.com',
            'port' => 5673,
            'user' => 'custom_user',
            'password' => 'custom_pass',
            'vhost' => '/custom',
            'exchange' => 'custom.exchange',
            'connection_timeout' => 5.0,
            'read_timeout' => 60.0,
            'write_timeout' => 5.0,
            'heartbeat' => 60,
            'keepalive' => false,
            'prefetch_count' => 10,
            'auto_ack' => true,
            'no_local' => true,
            'exclusive' => true,
            'no_wait' => true,
        ]);

        $this->assertSame('rabbitmq.example.com', $config->getHost());
        $this->assertSame(5673, $config->getPort());
        $this->assertSame('custom_user', $config->getUser());
        $this->assertSame('custom_pass', $config->getPassword());
        $this->assertSame('/custom', $config->getVhost());
        $this->assertSame('custom.exchange', $config->getExchange());
        $this->assertSame(5.0, $config->getConnectionTimeout());
        $this->assertSame(60.0, $config->getReadTimeout());
        $this->assertSame(5.0, $config->getWriteTimeout());
        $this->assertSame(60, $config->getHeartbeat());
        $this->assertFalse($config->isKeepalive());
        $this->assertSame(10, $config->getPrefetchCount());
        $this->assertTrue($config->isAutoAck());
        $this->assertTrue($config->isNoLocal());
        $this->assertTrue($config->isExclusive());
        $this->assertTrue($config->isNoWait());
    }

    public function testConvertsToArray(): void
    {
        $inputConfig = [
            'host' => 'rabbitmq.example.com',
            'port' => 5673,
            'user' => 'test_user',
            'password' => 'test_pass',
            'vhost' => '/test',
            'exchange' => 'test.exchange',
            'connection_timeout' => 5.0,
            'read_timeout' => 60.0,
            'write_timeout' => 5.0,
            'heartbeat' => 60,
            'keepalive' => false,
            'prefetch_count' => 5,
            'auto_ack' => true,
            'no_local' => false,
            'exclusive' => false,
            'no_wait' => false,
        ];

        $config = new ConsumerConfig($inputConfig);
        $outputArray = $config->toArray();

        $this->assertSame('rabbitmq.example.com', $outputArray['host']);
        $this->assertSame(5673, $outputArray['port']);
        $this->assertSame('test_user', $outputArray['user']);
        $this->assertSame('/test', $outputArray['vhost']);
        $this->assertSame('test.exchange', $outputArray['exchange']);
        $this->assertSame(5.0, $outputArray['connection_timeout']);
        $this->assertSame(60.0, $outputArray['read_timeout']);
        $this->assertSame(5.0, $outputArray['write_timeout']);
        $this->assertSame(60, $outputArray['heartbeat']);
        $this->assertFalse($outputArray['keepalive']);
        $this->assertSame(5, $outputArray['prefetch_count']);
        $this->assertTrue($outputArray['auto_ack']);
        $this->assertFalse($outputArray['no_local']);
        $this->assertFalse($outputArray['exclusive']);
        $this->assertFalse($outputArray['no_wait']);
    }

    public function testTypeCastsIntegerValues(): void
    {
        $config = new ConsumerConfig([
            'port' => '5673',
            'heartbeat' => '60',
            'prefetch_count' => '10',
        ]);

        $this->assertSame(5673, $config->getPort());
        $this->assertSame(60, $config->getHeartbeat());
        $this->assertSame(10, $config->getPrefetchCount());
    }

    public function testTypeCastsFloatValues(): void
    {
        $config = new ConsumerConfig([
            'connection_timeout' => '5',
            'read_timeout' => '60',
            'write_timeout' => '5',
        ]);

        $this->assertSame(5.0, $config->getConnectionTimeout());
        $this->assertSame(60.0, $config->getReadTimeout());
        $this->assertSame(5.0, $config->getWriteTimeout());
    }

    public function testTypeCastsBooleanValues(): void
    {
        $config = new ConsumerConfig([
            'keepalive' => 1,
            'auto_ack' => 1,
            'no_local' => 0,
            'exclusive' => 'true',
            'no_wait' => 'false',
        ]);

        $this->assertTrue($config->isKeepalive());
        $this->assertTrue($config->isAutoAck());
        $this->assertFalse($config->isNoLocal());
        $this->assertTrue($config->isExclusive());
        $this->assertFalse($config->isNoWait());
    }
}
