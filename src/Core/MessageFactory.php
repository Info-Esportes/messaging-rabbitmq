<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Core;

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

    public static function make(string $type, array $data, string $source = 'unknown'): Message
    {
        $factory = new self($source);

        switch ($type) {
            case MessageType::EMAIL:
                return $factory->createEmailMessage(
                    $data['to'],
                    $data['subject'],
                    $data['body'],
                    $data['template'] ?? null,
                    $data['variables'] ?? [],
                    $data['metadata'] ?? [],
                    $data['priority'] ?? MessagePriority::NORMAL
                );

            case MessageType::SMS:
                return $factory->createSMSMessage(
                    $data['phone'],
                    $data['message'],
                    $data['metadata'] ?? [],
                    $data['priority'] ?? MessagePriority::NORMAL
                );

            case MessageType::WHATSAPP:
                return $factory->createWhatsAppMessage(
                    $data['phone'],
                    $data['message'],
                    $data['media'] ?? null,
                    $data['metadata'] ?? [],
                    $data['priority'] ?? MessagePriority::HIGH
                );

            default:
                throw new MessageException("Unsupported message type: {$type}");
        }
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
        string $subject,
        string $body,
        ?string $template = null,
        array $variables = [],
        array $metadata = [],
        int $priority = MessagePriority::NORMAL
    ): Message {
        // Validate email
        if (!$this->validator->isValidEmail($to)) {
            throw new ValidationException("Invalid email address: {$to}");
        }

        if (empty($subject)) {
            throw new ValidationException('Email subject cannot be empty');
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
        string $message,
        array $metadata = [],
        int $priority = MessagePriority::HIGH
    ): Message {
        // Validate phone
        if (!$this->validator->isValidPhone($phone)) {
            throw new ValidationException("Invalid phone number: {$phone}");
        }

        if (empty($message)) {
            throw new ValidationException('SMS message cannot be empty');
        }

        // Check message length (standard SMS limit)
        if (strlen($message) > 1600) {
            throw new ValidationException('SMS message too long (max 1600 characters)');
        }

        return new Message(
            'sms',
            $phone,
            ['message' => $message],
            array_merge(['source' => $this->defaultSource], $metadata),
            ['priority' => $priority]
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
        array $metadata = [],
        int $priority = MessagePriority::NORMAL
    ): Message {
        // Validate phone
        if (!$this->validator->isValidPhone($phone)) {
            throw new ValidationException("Invalid phone number: {$phone}");
        }

        if (empty($message)) {
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
