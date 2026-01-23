<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Consumer;

use InfoEsportes\Messaging\Contracts\MessageConsumerInterface;
use InfoEsportes\Messaging\Exceptions\ConnectionException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConsumer implements MessageConsumerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;
    private ConsumerConfig $config;
    private bool $isConsuming = false;
    private ?string $consumerTag = null;

    public function __construct(array $config)
    {
        $this->config = new ConsumerConfig($config);
    }

    public function __destruct()
    {
        $this->stop();
        $this->disconnect();
    }

    /**
     * Start consuming messages from a queue.
     *
     * @param string   $queueName Queue to consume from
     * @param callable $callback  Callback function (AMQPMessage $msg, RabbitMQConsumer $consumer)
     *
     * @throws ConnectionException
     */
    public function consume(string $queueName, callable $callback): void
    {
        $this->ensureConnected();

        if (null === $this->channel) {
            throw new ConnectionException('AMQP channel is not established');
        }

        // Set QoS
        $this->channel->basic_qos(
            0,                                   // prefetch_size
            $this->config->getPrefetchCount(),   // prefetch_count
            false                                 // global
        );

        // Wrap the user callback to handle acknowledgments
        $wrappedCallback = function (AMQPMessage $msg) use ($callback): void {
            try {
                // Call user callback with message and consumer instance
                $callback($msg, $this);

                // Acknowledge message if not auto-ack
                if (!$this->config->isAutoAck() && null !== $this->channel) {
                    $this->channel->basic_ack($msg->getDeliveryTag());
                }
            } catch (\Exception $e) {
                // On error, reject and requeue the message
                if (!$this->config->isAutoAck() && null !== $this->channel) {
                    $this->channel->basic_nack(
                        $msg->getDeliveryTag(),
                        false,  // multiple
                        true    // requeue
                    );
                }
                throw $e;
            }
        };

        // Start consuming
        $this->consumerTag = $this->channel->basic_consume(
            $queueName,
            '',                              // consumer_tag (auto-generated)
            $this->config->isNoLocal(),
            $this->config->isAutoAck(),
            $this->config->isExclusive(),
            $this->config->isNoWait(),
            $wrappedCallback
        );

        $this->isConsuming = true;

        // Process messages
        while ($this->isConsuming && $this->channel && count($this->channel->callbacks)) {
            try {
                $this->channel->wait(null, false, (int) $this->config->getReadTimeout());
            } catch (AMQPConnectionClosedException $e) {
                $this->isConsuming = false;
                throw new ConnectionException('Connection closed during consumption: ' . $e->getMessage(), 0, $e);
            } catch (\Exception $e) {
                if (!$this->isConsuming) {
                    break;
                }
                throw $e;
            }
        }
    }

    /**
     * Stop consuming messages.
     */
    public function stop(): void
    {
        $this->isConsuming = false;

        if ($this->channel && $this->consumerTag) {
            try {
                $this->channel->basic_cancel($this->consumerTag, false, true);
            } catch (\Exception $e) {
                // Silent fail on stop
            }
            $this->consumerTag = null;
        }
    }

    /**
     * Check if consumer is currently consuming.
     */
    public function isConsuming(): bool
    {
        return $this->isConsuming;
    }

    /**
     * Declare and bind a queue to the exchange.
     *
     * @param string        $queueName   Queue name
     * @param array<string> $routingKeys Array of routing keys to bind
     * @param bool          $durable     Whether queue is durable
     * @param bool          $exclusive   Whether queue is exclusive
     * @param bool          $autoDelete  Whether queue auto-deletes
     * @param int           $maxPriority Maximum priority (default 10)
     *
     * @throws ConnectionException
     */
    public function declareQueue(
        string $queueName,
        array $routingKeys = [],
        bool $durable = true,
        bool $exclusive = false,
        bool $autoDelete = false,
        int $maxPriority = 10
    ): void {
        $this->ensureConnected();

        if (null === $this->channel) {
            throw new ConnectionException('AMQP channel is not established');
        }

        // Declare queue with priority support
        $this->channel->queue_declare(
            $queueName,
            false,       // passive
            $durable,
            $exclusive,
            $autoDelete,
            false,       // nowait
            ['x-max-priority' => ['I', $maxPriority]]
        );

        // Bind queue to exchange with routing keys
        foreach ($routingKeys as $routingKey) {
            $this->channel->queue_bind(
                $queueName,
                $this->config->getExchange(),
                $routingKey
            );
        }
    }

    /**
     * Check if connected to RabbitMQ.
     */
    public function isConnected(): bool
    {
        return null !== $this->connection && $this->connection->isConnected();
    }

    /**
     * Disconnect from RabbitMQ.
     */
    public function disconnect(): void
    {
        try {
            if ($this->channel) {
                $this->channel->close();
                $this->channel = null;
            }

            if ($this->connection) {
                $this->connection->close();
                $this->connection = null;
            }
        } catch (\Exception $e) {
            // Silent fail on disconnect
        }
    }

    /**
     * Manually acknowledge a message.
     *
     * @param AMQPMessage $message Message to acknowledge
     */
    public function ack(AMQPMessage $message): void
    {
        if ($this->channel) {
            $this->channel->basic_ack($message->getDeliveryTag());
        }
    }

    /**
     * Manually reject a message.
     *
     * @param AMQPMessage $message Message to reject
     * @param bool        $requeue Whether to requeue the message
     */
    public function nack(AMQPMessage $message, bool $requeue = true): void
    {
        if ($this->channel) {
            $this->channel->basic_nack(
                $message->getDeliveryTag(),
                false,  // multiple
                $requeue
            );
        }
    }

    /**
     * Ensure connection is established.
     *
     * @throws ConnectionException
     */
    private function ensureConnected(): void
    {
        if (null === $this->connection || !$this->isConnected()) {
            $this->connect();
        }
    }

    /**
     * Establish connection to RabbitMQ.
     *
     * @throws ConnectionException
     */
    private function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
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

            $this->channel = $this->connection->channel();

            // Declare exchange (idempotent)
            $this->channel->exchange_declare(
                $this->config->getExchange(),
                'topic',
                false,  // passive
                true,   // durable
                false   // auto_delete
            );
        } catch (AMQPConnectionClosedException | AMQPIOException $e) {
            throw new ConnectionException(
                "Cannot connect to RabbitMQ at {$this->config->getHost()}:{$this->config->getPort()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
