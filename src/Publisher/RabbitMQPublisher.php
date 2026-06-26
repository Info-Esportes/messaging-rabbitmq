<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Publisher;

use InfoEsportes\Messaging\Contracts\MessagePublisherInterface;
use InfoEsportes\Messaging\Core\MessageFactory;
use InfoEsportes\Messaging\Exceptions\ConnectionException;
use InfoEsportes\Messaging\Exceptions\PublishException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher implements MessagePublisherInterface
{
    private ConnectionManager $connectionManager;
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;
    private PublisherConfig $config;
    private MessageFactory $factory;

    public function __construct(array $config, string $source = 'unknown')
    {
        $this->config = new PublisherConfig($config);
        $this->connectionManager = new ConnectionManager($this->config);
        $this->factory = new MessageFactory($source);
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function publish(string $exchange, string $routingKey, array $payload, int $priority = 5): bool
    {
        $this->ensureConnected();

        try {
            $message = new AMQPMessage(
                json_encode($payload, JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'priority' => $priority,
                    'message_id' => $payload['id'] ?? null,
                    'timestamp' => time(),
                    'app_id' => $this->factory->getDefaultSource(),
                ]
            );

            if (null === $this->channel) {
                throw new PublishException('AMQP channel is not established');
            }

            $this->channel->basic_publish($message, $exchange, $routingKey);

            return true;
        } catch (\JsonException $e) {
            throw new PublishException("Failed to encode message: {$e->getMessage()}", 0, $e);
        } catch (AMQPConnectionClosedException $e) {
            // Try to reconnect once
            $this->connection = null;
            $this->ensureConnected();

            // Retry publish
            return $this->publish($exchange, $routingKey, $payload, $priority);
        } catch (\Exception $e) {
            throw new PublishException("Failed to publish message: {$e->getMessage()}", 0, $e);
        }
    }

    public function sendEmail(string $to, ?string $subject = null, ?string $body = null, ?string $template = null, array $variables = [], array $data = [], string $routingKey = 'email.transactional', int $priority = 5): bool
    {
        $message = $this->factory->createEmailMessage(
            $to,
            $subject,
            $body,
            $template,
            $variables,
            $data['metadata'] ?? [],
            $priority
        );

        return $this->publish(
            $this->config->getExchange(),
            $routingKey,
            $message->toArray(),
            $priority
        );
    }

    public function sendSMS(string $phone, ?string $message = null, ?string $template = null, array $variables = [], string $routingKey = 'sms.message', int $priority = 8): bool
    {
        $msg = $this->factory->createSMSMessage($phone, $message, $template, $variables, [], $priority);

        return $this->publish(
            $this->config->getExchange(),
            $routingKey,
            $msg->toArray(),
            $priority
        );
    }

    public function sendWhatsApp(string $phone, ?string $message = null, ?string $template = null, array $variables = [], string $routingKey = 'whatsapp.notification', ?string $media = null, int $priority = 7): bool
    {
        $msg = $this->factory->createWhatsAppMessage($phone, $message, $media, $template, $variables, [], $priority);

        return $this->publish(
            $this->config->getExchange(),
            $routingKey,
            $msg->toArray(),
            $priority
        );
    }

    public function isConnected(): bool
    {
        return null !== $this->connection && $this->connection->isConnected();
    }

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
            $this->connection = $this->connectionManager->connect();
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
            throw new ConnectionException("Failed to connect to RabbitMQ: {$e->getMessage()}", 0, $e);
        }
    }
}
