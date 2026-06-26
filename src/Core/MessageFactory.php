<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Core;

use InfoEsportes\Messaging\DTO\EmailMessage;
use InfoEsportes\Messaging\DTO\SMSMessage;
use InfoEsportes\Messaging\DTO\WhatsAppMessage;
use InfoEsportes\Messaging\Exceptions\MessageException;
use InfoEsportes\Messaging\Exceptions\ValidationException;

class MessageFactory
{
    private string $defaultSource;
    private MessageValidator $validator;

    public function __construct(string $source = 'unknown')
    {
        $this->defaultSource = $source;
        $this->validator = new MessageValidator();
    }

    public static function make(string $type, mixed $data, string $source = 'unknown'): Message
    {
        $factory = new self($source);

        switch ($type) {
            case MessageType::EMAIL:
                $dto = $data instanceof EmailMessage ? $data : EmailMessage::fromArray($data);
                return $factory->createEmailMessageFromDTO($dto);

            case MessageType::SMS:
                $dto = $data instanceof SMSMessage ? $data : SMSMessage::fromArray($data);
                return $factory->createSMSMessageFromDTO($dto);

            case MessageType::WHATSAPP:
                $dto = $data instanceof WhatsAppMessage ? $data : WhatsAppMessage::fromArray($data);
                return $factory->createWhatsAppMessageFromDTO($dto);

            default:
                throw new MessageException("Unsupported message type: {$type}");
        }
    }

    /**
     * Create email message from DTO.
     *
     * @throws ValidationException
     */
    public function createEmailMessageFromDTO(EmailMessage $dto): Message
    {
        return $this->createEmailMessage(
            $dto->to,
            $dto->subject,
            $dto->body,
            $dto->template,
            $dto->variables,
            $dto->metadata,
            $dto->priority
        );
    }

    /**
     * Create email message.
     *
     * @param string      $to        Email address
     * @param string      $subject   Email subject
     * @param string      $body      Email body
     * @param null|string $template  Email template name
     * @param array       $variables Template variables
     * @param array       $metadata  Additional metadata
     * @param int         $priority  Message priority
     *
     * @throws ValidationException
     */
    public function createEmailMessage(
        string $to,
        ?string $subject = null,
        ?string $body = null,
        ?string $template = null,
        array $variables = [],
        array $metadata = [],
        int $priority = MessagePriority::NORMAL
    ): Message {
        // Validate email
        if (!$this->validator->isValidEmail($to)) {
            throw new ValidationException("Invalid email address: {$to}");
        }

        if (empty($subject) && empty($body) && empty($template)) {
            throw new ValidationException('Email subject, body and template cannot be empty');
        }

        return new Message(
            'email',
            $to,
            [
                'subject' => $subject,
                'body' => $body,
                'template' => $template,
                'variables' => $variables,
            ],
            array_merge(['source' => $this->defaultSource], $metadata),
            ['priority' => $priority]
        );
    }

    /**
     * Create SMS message from DTO.
     *
     * @throws ValidationException
     */
    public function createSMSMessageFromDTO(SMSMessage $dto): Message
    {
        return $this->createSMSMessage(
            $dto->phone,
            $dto->message,
            $dto->template,
            $dto->variables,
            $dto->metadata,
            $dto->priority
        );
    }

    /**
     * Create SMS message.
     *
     * @param string $phone    Phone number
     * @param string $message  SMS text
     * @param array  $metadata Additional metadata
     * @param int    $priority Message priority
     *
     * @throws ValidationException
     */
    public function createSMSMessage(
        string $phone,
        ?string $message = null,
        ?string $template = null,
        array $variables = [],
        array $metadata = [],
        int $priority = MessagePriority::HIGH
    ): Message {
        // Validate phone
        if (!$this->validator->isValidPhone($phone)) {
            throw new ValidationException("Invalid phone number: {$phone}");
        }

        if (empty($message) && empty($template)) {
            throw new ValidationException('SMS message cannot be empty');
        }

        // Check message length (standard SMS limit)
        if ($message !== null && strlen($message) > 1600) {
            throw new ValidationException('SMS message too long (max 1600 characters)');
        }

        return new Message(
            'sms',
            $phone,
            ['message' => $message, 'template' => $template, 'variables' => $variables],
            array_merge(['source' => $this->defaultSource], $metadata),
            ['priority' => $priority]
        );
    }

    /**
     * Create WhatsApp message from DTO.
     *
     * @throws ValidationException
     */
    public function createWhatsAppMessageFromDTO(WhatsAppMessage $dto): Message
    {
        return $this->createWhatsAppMessage(
            $dto->phone,
            $dto->message,
            $dto->media,
            $dto->template,
            $dto->variables,
            $dto->metadata,
            $dto->priority
        );
    }

    /**
     * Create WhatsApp message.
     *
     * @param string      $phone    Phone number
     * @param string      $message  Message text
     * @param null|string $media    Media URL
     * @param array       $metadata Additional metadata
     * @param int         $priority Message priority
     *
     * @throws ValidationException
     */
    public function createWhatsAppMessage(
        string $phone,
        string $message,
        ?string $media = null,
        ?string $template = null,
        array $variables = [],
        array $metadata = [],
        int $priority = MessagePriority::NORMAL
    ): Message {
        // Validate phone
        if (!$this->validator->isValidPhone($phone)) {
            throw new ValidationException("Invalid phone number: {$phone}");
        }

        if (empty($message) && empty($template)) {
            throw new ValidationException('WhatsApp message cannot be empty');
        }

        // Validate media URL if provided
        if (null !== $media && !$this->validator->isValidUrl($media)) {
            throw new ValidationException("Invalid media URL: {$media}");
        }

        return new Message(
            'whatsapp',
            $phone,
            [
                'message' => $message,
                'media' => $media,
                'template' => $template,
                'variables' => $variables,
            ],
            array_merge(['source' => $this->defaultSource], $metadata),
            ['priority' => $priority]
        );
    }

    /**
     * Set default source.
     */
    public function setDefaultSource(string $source): void
    {
        $this->defaultSource = $source;
    }

    /**
     * Get default source.
     */
    public function getDefaultSource(): string
    {
        return $this->defaultSource;
    }
}
