<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Consumer;

class ConsumerConfig
{
    private string $host;
    private int $port;
    private string $user;
    private string $password;
    private string $vhost;
    private string $exchange;
    private float $connectionTimeout;
    private float $readTimeout;
    private float $writeTimeout;
    private int $heartbeat;
    private bool $keepalive;
    private int $prefetchCount;
    private bool $autoAck;
    private bool $noLocal;
    private bool $exclusive;
    private bool $noWait;

    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? '127.0.0.1';
        $this->port = (int) ($config['port'] ?? 5672);
        $this->user = $config['user'] ?? 'guest';
        $this->password = $config['password'] ?? 'guest';
        $this->vhost = $config['vhost'] ?? '/';
        $this->exchange = $config['exchange'] ?? 'messages.topic';
        $this->connectionTimeout = (float) ($config['connection_timeout'] ?? 3.0);
        $this->readTimeout = (float) ($config['read_timeout'] ?? 30.0);
        $this->writeTimeout = (float) ($config['write_timeout'] ?? 3.0);
        $this->heartbeat = (int) ($config['heartbeat'] ?? 30);
        $this->keepalive = (bool) ($config['keepalive'] ?? true);
        $this->prefetchCount = (int) ($config['prefetch_count'] ?? 1);
        $this->autoAck = (bool) ($config['auto_ack'] ?? false);
        $this->noLocal = (bool) ($config['no_local'] ?? false);
        $this->exclusive = (bool) ($config['exclusive'] ?? false);
        $this->noWait = (bool) ($config['no_wait'] ?? false);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getVhost(): string
    {
        return $this->vhost;
    }

    public function getExchange(): string
    {
        return $this->exchange;
    }

    public function getConnectionTimeout(): float
    {
        return $this->connectionTimeout;
    }

    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }

    public function getWriteTimeout(): float
    {
        return $this->writeTimeout;
    }

    public function getHeartbeat(): int
    {
        return $this->heartbeat;
    }

    public function isKeepalive(): bool
    {
        return $this->keepalive;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function isAutoAck(): bool
    {
        return $this->autoAck;
    }

    public function isNoLocal(): bool
    {
        return $this->noLocal;
    }

    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    public function isNoWait(): bool
    {
        return $this->noWait;
    }

    public function toArray(): array
    {
        return get_class_vars(self::class);
    }
}
