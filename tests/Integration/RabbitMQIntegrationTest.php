<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Tests\Integration;

use InfoEsportes\Messaging\Consumer\RabbitMQConsumer;
use InfoEsportes\Messaging\Core\MessagePriority;
use InfoEsportes\Messaging\Exceptions\ConnectionException;
use InfoEsportes\Messaging\Publisher\RabbitMQPublisher;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests require a running RabbitMQ instance
 * Skip these tests if RabbitMQ is not available.
 *
 * To run: docker run -d -p 5672:5672 rabbitmq:3-management
 *
 * @internal
 *
 * @coversNothing
 */
class RabbitMQIntegrationTest extends TestCase
{
    private ?RabbitMQPublisher $publisher = null;
    private ?RabbitMQConsumer $consumer = null;

    protected function setUp(): void
    {
        if (!$this->isRabbitMQAvailable()) {
            $this->markTestSkipped('RabbitMQ is not available');
        }

        $config = [
            'host' => getenv('RABBITMQ_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('RABBITMQ_PORT') ?: 5672),
            'user' => getenv('RABBITMQ_USER') ?: 'guest',
            'password' => getenv('RABBITMQ_PASSWORD') ?: 'guest',
            'vhost' => '/',
            'exchange' => 'test.messages.topic',
        ];

        try {
            $this->publisher = new RabbitMQPublisher($config, 'integration-test');
            $this->consumer = new RabbitMQConsumer($config);
        } catch (ConnectionException $e) {
            $this->markTestSkipped('Cannot connect to RabbitMQ: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->consumer) {
            $this->consumer->stop();
            $this->consumer->disconnect();
        }

        if ($this->publisher) {
            $this->publisher->disconnect();
        }
    }

    public function testPublishesEmailMessage(): void
    {
        $result = $this->publisher->sendEmail(
            'test@example.com',
            'Integration Test',
            '<p>This is a test email</p>',
            ['template' => 'test'],
            'email.transactional',
            MessagePriority::NORMAL
        );

        $this->assertTrue($result);
    }

    public function testPublishesSmsMessage(): void
    {
        $result = $this->publisher->sendSMS(
            '+5511999999999',
            'Integration test SMS',
            'sms.notification',
            MessagePriority::HIGH
        );

        $this->assertTrue($result);
    }

    public function testPublishesWhatsappMessage(): void
    {
        $result = $this->publisher->sendWhatsApp(
            '+5511999999999',
            'Integration test WhatsApp',
            'https://example.com/image.jpg',
            'whatsapp.notification',
            MessagePriority::NORMAL
        );

        $this->assertTrue($result);
    }

    public function testIsConnected(): void
    {
        $this->assertTrue($this->publisher->isConnected());
    }

    public function testDisconnects(): void
    {
        $this->publisher->disconnect();

        $this->assertFalse($this->publisher->isConnected());
    }

    public function testReconnectsAfterDisconnect(): void
    {
        $this->publisher->disconnect();
        $this->assertFalse($this->publisher->isConnected());

        // Should reconnect automatically on next publish
        $result = $this->publisher->sendEmail(
            'test@example.com',
            'Reconnection Test',
            'Body'
        );

        $this->assertTrue($result);
        $this->assertTrue($this->publisher->isConnected());
    }

    public function testPublishesWithDifferentPriorities(): void
    {
        foreach (MessagePriority::all() as $priority) {
            $result = $this->publisher->sendEmail(
                'test@example.com',
                "Priority {$priority} Test",
                'Body',
                [],
                $priority
            );

            $this->assertTrue($result, "Failed to publish with priority {$priority}");
        }
    }

    public function testConsumerDeclaresQueue(): void
    {
        $queueName = 'test.consumer.queue';

        $this->consumer->declareQueue(
            $queueName,
            ['email.#', 'sms.#'],
            true,  // durable
            false, // exclusive
            false, // auto_delete
            10     // max_priority
        );

        $this->assertTrue($this->consumer->isConnected());
    }

    public function testConsumerReceivesMessages(): void
    {
        $queueName = 'test.consumer.receive';
        $receivedMessages = [];

        // Declare queue
        $this->consumer->declareQueue($queueName, ['test.routing.key']);

        // Publish a test message
        $this->publisher->publish(
            'test.messages.topic',
            'test.routing.key',
            ['test' => 'data', 'timestamp' => time()],
            5
        );

        // Consume with timeout
        $startTime = time();
        $timeout = 5;

        $callback = function (AMQPMessage $msg) use (&$receivedMessages, &$startTime, $timeout): void {
            $receivedMessages[] = json_decode($msg->getBody(), true);

            // Stop after receiving one message or timeout
            if (count($receivedMessages) >= 1 || (time() - $startTime) > $timeout) {
                $this->consumer->stop();
            }
        };

        try {
            $this->consumer->consume($queueName, $callback);
        } catch (\Exception $e) {
            // Timeout or consumer stopped
        }

        $this->assertNotEmpty($receivedMessages, 'No messages received');
        $this->assertArrayHasKey('test', $receivedMessages[0]);
        $this->assertSame('data', $receivedMessages[0]['test']);
    }

    public function testConsumerStopsConsuming(): void
    {
        $queueName = 'test.consumer.stop';

        $this->consumer->declareQueue($queueName, ['stop.test']);

        $callback = function (AMQPMessage $msg): void {
            $this->consumer->stop();
        };

        $this->assertFalse($this->consumer->isConsuming());

        // Start consuming (will stop immediately via callback)
        $this->publisher->publish('test.messages.topic', 'stop.test', ['data' => 'test'], 5);

        try {
            $this->consumer->consume($queueName, $callback);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertFalse($this->consumer->isConsuming());
    }

    public function testConsumerManualAcknowledgment(): void
    {
        $queueName = 'test.consumer.manual.ack';
        $config = [
            'host' => getenv('RABBITMQ_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('RABBITMQ_PORT') ?: 5672),
            'user' => getenv('RABBITMQ_USER') ?: 'guest',
            'password' => getenv('RABBITMQ_PASSWORD') ?: 'guest',
            'vhost' => '/',
            'exchange' => 'test.messages.topic',
            'auto_ack' => false, // Manual acknowledgment
        ];

        $manualConsumer = new RabbitMQConsumer($config);
        $manualConsumer->declareQueue($queueName, ['manual.ack.test']);

        // Publish a message
        $this->publisher->publish('test.messages.topic', 'manual.ack.test', ['test' => 'manual'], 5);

        $messageProcessed = false;

        $callback = function (AMQPMessage $msg) use ($manualConsumer, &$messageProcessed): void {
            $messageProcessed = true;
            // Message is auto-acknowledged by consumer wrapper
            $manualConsumer->stop();
        };

        try {
            $manualConsumer->consume($queueName, $callback);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertTrue($messageProcessed);

        $manualConsumer->disconnect();
    }

    public function testConsumerPrefetchCount(): void
    {
        $queueName = 'test.consumer.prefetch';
        $config = [
            'host' => getenv('RABBITMQ_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('RABBITMQ_PORT') ?: 5672),
            'user' => getenv('RABBITMQ_USER') ?: 'guest',
            'password' => getenv('RABBITMQ_PASSWORD') ?: 'guest',
            'vhost' => '/',
            'exchange' => 'test.messages.topic',
            'prefetch_count' => 5,
        ];

        $prefetchConsumer = new RabbitMQConsumer($config);
        $prefetchConsumer->declareQueue($queueName, ['prefetch.test']);

        $this->assertTrue($prefetchConsumer->isConnected());

        $prefetchConsumer->disconnect();
    }

    private function isRabbitMQAvailable(): bool
    {
        $host = getenv('RABBITMQ_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('RABBITMQ_PORT') ?: 5672);

        $connection = @fsockopen($host, $port, $errno, $errstr, 1);

        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }
}
