<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Publisher;

use InfoEsportes\Messaging\Exceptions\ConnectionException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;

class ConnectionManager
{
    private PublisherConfig $config;

    public function __construct(PublisherConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Create new RabbitMQ connection.
     *
     * @throws ConnectionException
     */
    public function connect(): AMQPStreamConnection
    {
        try {
            return new AMQPStreamConnection(
                $this->config->getHost(),
                $this->config->getPort(),
                $this->config->getUser(),
                $this->config->getPassword(),
                $this->config->getVhost(),
                false,  // insist
                'AMQPLAIN',
                null,   // login_response
                'en_US',
                $this->config->getConnectionTimeout(),
                $this->config->getReadTimeout(),
                null,   // context
                $this->config->isKeepalive(),
                $this->config->getHeartbeat()
            );
        } catch (AMQPIOException $e) {
            throw new ConnectionException(
                "Cannot connect to RabbitMQ at {$this->config->getHost()}:{$this->config->getPort()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
