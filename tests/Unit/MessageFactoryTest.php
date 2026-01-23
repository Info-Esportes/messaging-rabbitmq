<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Tests\Unit;

use InfoEsportes\Messaging\DTO\EmailMessage;
use InfoEsportes\Messaging\DTO\SMSMessage;
use InfoEsportes\Messaging\DTO\WhatsAppMessage;
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
            'phone' => '+5511999999999',
            'message' => 'Static message',
        ], 'test-app');

        $this->assertTrue(MessageType::SMS === $message->getType(), 'Message type should be SMS');
        $this->assertTrue('+5511999999999' === $message->getRecipient(), 'Recipient should match');
        $this->assertTrue('Static message' === $message->getData()['message'], 'Message content should match');
        $this->assertTrue('test-app' === $message->getMetadata()['source'], 'Source should match');
    }

    public function testCanMakeEmailMessageWithDTO(): void
    {
        $dto = new EmailMessage(
            to: 'test@example.com',
            subject: 'Test Subject',
            body: 'Test Body',
            priority: MessagePriority::NORMAL
        );

        $message = MessageFactory::make(MessageType::EMAIL, $dto, 'test-app');

        $this->assertEquals('email', $message->getType());
        $this->assertEquals('test@example.com', $message->getRecipient());
        $this->assertEquals('Test Subject', $message->getData()['subject']);
        $this->assertEquals('Test Body', $message->getData()['body']);
    }

    public function testCanMakeSMSMessageWithDTO(): void
    {
        $dto = new SMSMessage(
            phone: '+5511999999999',
            message: 'DTO SMS message',
            priority: MessagePriority::HIGH
        );

        $message = MessageFactory::make(MessageType::SMS, $dto, 'test-app');

        $this->assertEquals('sms', $message->getType());
        $this->assertEquals('+5511999999999', $message->getRecipient());
        $this->assertEquals('DTO SMS message', $message->getData()['message']);
    }

    public function testCanMakeWhatsAppMessageWithDTO(): void
    {
        $dto = new WhatsAppMessage(
            phone: '+5511999999999',
            message: 'DTO WhatsApp message',
            media: 'https://example.com/image.jpg',
            priority: MessagePriority::HIGH
        );

        $message = MessageFactory::make(MessageType::WHATSAPP, $dto, 'test-app');

        $this->assertEquals('whatsapp', $message->getType());
        $this->assertEquals('+5511999999999', $message->getRecipient());
        $this->assertEquals('DTO WhatsApp message', $message->getData()['message']);
        $this->assertEquals('https://example.com/image.jpg', $message->getData()['media']);
    }

    public function testEmailDTOFromArray(): void
    {
        $data = [
            'to' => 'test@example.com',
            'subject' => 'Test',
            'body' => 'Body',
            'template' => 'welcome',
            'variables' => ['name' => 'John'],
            'metadata' => ['key' => 'value'],
            'priority' => MessagePriority::HIGH,
        ];

        $dto = EmailMessage::fromArray($data);

        $this->assertEquals('test@example.com', $dto->to);
        $this->assertEquals('Test', $dto->subject);
        $this->assertEquals('Body', $dto->body);
        $this->assertEquals('welcome', $dto->template);
        $this->assertEquals(['name' => 'John'], $dto->variables);
        $this->assertEquals(['key' => 'value'], $dto->metadata);
        $this->assertEquals(MessagePriority::HIGH, $dto->priority);
    }

    public function testSMSDTOFromArray(): void
    {
        $data = [
            'phone' => '+5511999999999',
            'message' => 'Test message',
            'metadata' => ['key' => 'value'],
            'priority' => MessagePriority::NORMAL,
        ];

        $dto = SMSMessage::fromArray($data);

        $this->assertEquals('+5511999999999', $dto->phone);
        $this->assertEquals('Test message', $dto->message);
        $this->assertEquals(['key' => 'value'], $dto->metadata);
        $this->assertEquals(MessagePriority::NORMAL, $dto->priority);
    }

    public function testWhatsAppDTOFromArray(): void
    {
        $data = [
            'phone' => '+5511999999999',
            'message' => 'Test message',
            'media' => 'https://example.com/image.jpg',
            'metadata' => ['key' => 'value'],
            'priority' => MessagePriority::HIGH,
        ];

        $dto = WhatsAppMessage::fromArray($data);

        $this->assertEquals('+5511999999999', $dto->phone);
        $this->assertEquals('Test message', $dto->message);
        $this->assertEquals('https://example.com/image.jpg', $dto->media);
        $this->assertEquals(['key' => 'value'], $dto->metadata);
        $this->assertEquals(MessagePriority::HIGH, $dto->priority);
    }
}
