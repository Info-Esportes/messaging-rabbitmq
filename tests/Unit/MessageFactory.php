<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Tests\Unit;

use InfoEsportes\Messaging\Core\MessageFactory;
use InfoEsportes\Messaging\Core\MessagePriority;
use InfoEsportes\Messaging\Core\MessageType;
use InfoEsportes\Messaging\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class MessageFactoryTest extends TestCase
{
    private MessageFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MessageFactory('test-app');
    }

    public function testCreatesEmailMessage(): void
    {
        $message = $this->factory->createEmailMessage(
            'test@example.com',
            'Test Subject',
            'Test Body'
        );

        $this->assertEquals('email', $message->getType());
        $this->assertEquals('test@example.com', $message->getRecipient());

        $data = $message->getData();
        $this->assertEquals('Test Subject', $data['subject']);
        $this->assertEquals('Test Body', $data['body']);
    }

    public function testCreatesEmailMessageWithTemplate(): void
    {
        $message = $this->factory->createEmailMessage(
            'test@example.com',
            'Subject',
            'Body',
            'welcome-template',
            ['name' => 'John']
        );

        $data = $message->getData();
        $this->assertEquals('welcome-template', $data['template']);
        $this->assertEquals(['name' => 'John'], $data['variables']);
    }

    public function testEmailMessageThrowsExceptionForInvalidEmail(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email address');

        $this->factory->createEmailMessage('invalid-email', 'Subject', 'Body');
    }

    public function testEmailMessageThrowsExceptionForEmptySubject(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email subject cannot be empty');

        $this->factory->createEmailMessage('test@example.com', '', 'Body');
    }

    public function testCreatesSmsMessage(): void
    {
        $message = $this->factory->createSMSMessage(
            '+5511999999999',
            'Test SMS'
        );

        $this->assertEquals('sms', $message->getType());
        $this->assertEquals('+5511999999999', $message->getRecipient());

        $data = $message->getData();
        $this->assertEquals('Test SMS', $data['message']);
    }

    public function testSmsMessageHasHighPriorityByDefault(): void
    {
        $message = $this->factory->createSMSMessage(
            '+5511999999999',
            'Test'
        );

        $this->assertEquals(MessagePriority::HIGH, $message->getPriority());
    }

    public function testSmsMessageThrowsExceptionForInvalidPhone(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid phone number');

        $this->factory->createSMSMessage('invalid', 'Test');
    }

    public function testSmsMessageThrowsExceptionForEmptyMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('SMS message cannot be empty');

        $this->factory->createSMSMessage('+5511999999999', '');
    }

    public function testSmsMessageThrowsExceptionForTooLongMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('SMS message too long');

        $longMessage = str_repeat('a', 1601);
        $this->factory->createSMSMessage('+5511999999999', $longMessage);
    }

    public function testCreatesWhatsappMessage(): void
    {
        $message = $this->factory->createWhatsAppMessage(
            '+5511999999999',
            'Test WhatsApp'
        );

        $this->assertEquals('whatsapp', $message->getType());
        $this->assertEquals('+5511999999999', $message->getRecipient());

        $data = $message->getData();
        $this->assertEquals('Test WhatsApp', $data['message']);
        $this->assertNull($data['media']);
    }

    public function testCreatesWhatsappMessageWithMedia(): void
    {
        $message = $this->factory->createWhatsAppMessage(
            '+5511999999999',
            'Test',
            'https://example.com/image.jpg'
        );

        $data = $message->getData();
        $this->assertEquals('https://example.com/image.jpg', $data['media']);
    }

    public function testWhatsappMessageThrowsExceptionForInvalidPhone(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid phone number');

        $this->factory->createWhatsAppMessage('invalid', 'Test');
    }

    public function testWhatsappMessageThrowsExceptionForEmptyMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('WhatsApp message cannot be empty');

        $this->factory->createWhatsAppMessage('+5511999999999', '');
    }

    public function testWhatsappMessageThrowsExceptionForInvalidMediaUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid media URL');

        $this->factory->createWhatsAppMessage(
            '+5511999999999',
            'Test',
            'not-a-url'
        );
    }

    public function testSetsDefaultSourceInMetadata(): void
    {
        $message = $this->factory->createEmailMessage(
            'test@example.com',
            'Subject',
            'Body'
        );

        $metadata = $message->getMetadata();
        $this->assertEquals('test-app', $metadata['source']);
    }

    public function testCanChangeDefaultSource(): void
    {
        $this->factory->setDefaultSource('new-source');

        $message = $this->factory->createEmailMessage(
            'test@example.com',
            'Subject',
            'Body'
        );

        $metadata = $message->getMetadata();
        $this->assertEquals('new-source', $metadata['source']);
    }

    public function testCanOverrideSourceInMetadata(): void
    {
        $message = $this->factory->createEmailMessage(
            'test@example.com',
            'Subject',
            'Body',
            null,
            [],
            ['source' => 'custom-source']
        );

        $metadata = $message->getMetadata();
        $this->assertEquals('custom-source', $metadata['source']);
    }

    public function testCanMakeMessageStatic(): void
    {
        $message = MessageFactory::make('sms', [
            'recipient' => '+5511999999999',
            'data' => ['message' => 'Static message'],
        ], 'test-app');

        $this->assertTrue(MessageType::SMS === $message->getType(), 'Message type should be SMS');
        $this->assertTrue('+5511999999999' === $message->getRecipient(), 'Recipient should match');
        $this->assertTrue('Static message' === $message->getData()['message'], 'Message content should match');
        $this->assertTrue('test-app' === $message->getMetadata()['source'], 'Source should match');
    }
}
