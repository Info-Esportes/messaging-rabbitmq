<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Tests\Unit;

use InfoEsportes\Messaging\Core\Message;
use InfoEsportes\Messaging\Core\MessagePriority;
use InfoEsportes\Messaging\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class MessageTest extends TestCase
{
    public function testCreatesMessageWithRequiredFields(): void
    {
        $message = new Message('email', 'test@example.com');

        $this->assertIsString($message->getId());
        $this->assertEquals('email', $message->getType());
        $this->assertEquals('test@example.com', $message->getRecipient());
    }

    public function testGeneratesUniqueIds(): void
    {
        $message1 = new Message('email', 'test@example.com');
        $message2 = new Message('email', 'test@example.com');

        $this->assertNotEquals($message1->getId(), $message2->getId());
    }

    public function testCreatesMessageWithData(): void
    {
        $data = [
            'subject' => 'Test Subject',
            'body' => 'Test Body',
        ];

        $message = new Message('email', 'test@example.com', $data);

        $this->assertEquals($data, $message->getData());
    }

    public function testCreatesMessageWithMetadata(): void
    {
        $metadata = [
            'source' => 'test-app',
            'user_id' => 123,
        ];

        $message = new Message('email', 'test@example.com', [], $metadata);

        $this->assertEquals('test-app', $message->getMetadata()['source']);
        $this->assertEquals(123, $message->getMetadata()['user_id']);
    }

    public function testCreatesMessageWithCustomPriority(): void
    {
        $message = new Message(
            'email',
            'test@example.com',
            [],
            [],
            ['priority' => MessagePriority::URGENT]
        );

        $this->assertEquals(MessagePriority::URGENT, $message->getPriority());
    }

    public function testHasDefaultPriority(): void
    {
        $message = new Message('email', 'test@example.com');

        $this->assertEquals(MessagePriority::NORMAL, $message->getPriority());
    }

    public function testConvertsToArray(): void
    {
        $message = new Message(
            'email',
            'test@example.com',
            ['subject' => 'Test'],
            ['source' => 'test-app']
        );

        $array = $message->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('recipient', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertArrayHasKey('timestamp', $array);
    }

    public function testConvertsToJson(): void
    {
        $message = new Message('email', 'test@example.com');

        $json = $message->toJson();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('email', $decoded['type']);
        $this->assertEquals('test@example.com', $decoded['recipient']);
    }

    public function testCreatesFromArray(): void
    {
        $data = [
            'id' => 'test-id-123',
            'type' => 'sms',
            'recipient' => '+5511999999999',
            'data' => ['message' => 'Test'],
            'metadata' => ['source' => 'test'],
            'options' => ['priority' => 8],
        ];

        $message = Message::fromArray($data);

        $this->assertEquals('test-id-123', $message->getId());
        $this->assertEquals('sms', $message->getType());
        $this->assertEquals('+5511999999999', $message->getRecipient());
    }

    public function testFromArrayThrowsExceptionForMissingType(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Message must contain type and recipient');

        Message::fromArray(['recipient' => 'test@example.com']);
    }

    public function testFromArrayThrowsExceptionForMissingRecipient(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Message must contain type and recipient');

        Message::fromArray(['type' => 'email']);
    }

    public function testCreatesFromJson(): void
    {
        $json = json_encode([
            'type' => 'email',
            'recipient' => 'test@example.com',
            'data' => ['subject' => 'Test'],
        ]);

        if (false === $json) {
            $this->fail('Failed to encode test JSON');
        }

        $message = Message::fromJson($json);

        $this->assertEquals('email', $message->getType());
        $this->assertEquals('test@example.com', $message->getRecipient());
    }

    public function testFromJsonThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid JSON');

        Message::fromJson('invalid json {');
    }

    public function testMessageHasTimestamp(): void
    {
        $message = new Message('email', 'test@example.com');
        $array = $message->toArray();

        $this->assertArrayHasKey('timestamp', $array);
        $this->assertIsString($array['timestamp']);

        // Validate ISO 8601 format
        $timestamp = \DateTime::createFromFormat(\DateTime::ATOM, $array['timestamp']);
        $this->assertInstanceOf(\DateTime::class, $timestamp);
    }

    public function testMessageIncludesDefaultMetadata(): void
    {
        $message = new Message('email', 'test@example.com');
        $metadata = $message->getMetadata();

        $this->assertArrayHasKey('source', $metadata);
        $this->assertArrayHasKey('created_at', $metadata);
    }
}
