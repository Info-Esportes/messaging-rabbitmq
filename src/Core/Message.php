<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Core;

use InfoEsportes\Messaging\Contracts\MessageInterface;
use InfoEsportes\Messaging\Exceptions\ValidationException;

class Message implements MessageInterface
{
    private string $id;
    private string $type;
    private string $recipient;
    private array $data;
    private array $metadata;
    private array $options;
    private string $timestamp;

    /**
     * @param string $type      Message type (email, sms, whatsapp)
     * @param string $recipient Recipient (email or phone)
     * @param array  $data      Message data
     * @param array  $metadata  Additional metadata
     * @param array  $options   Message options
     */
    public function __construct(
        string $type,
        string $recipient,
        array $data = [],
        array $metadata = [],
        array $options = []
    ) {
        $this->id = $this->generateUuid();
        $this->type = $type;
        $this->recipient = $recipient;
        $this->data = $data;
        $this->metadata = array_merge([
            'source' => 'unknown',
            'created_at' => date('c'),
        ], $metadata);
        $this->options = array_merge([
            'priority' => MessagePriority::NORMAL,
            'retry_count' => 0,
            'max_retries' => 3,
            'timeout' => 120,
        ], $options);
        $this->timestamp = date('c');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getPriority(): int
    {
        return $this->options['priority'] ?? MessagePriority::NORMAL;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'timestamp' => $this->timestamp,
            'recipient' => $this->recipient,
            'data' => $this->data,
            'metadata' => $this->metadata,
            'options' => $this->options,
        ];
    }

    public function toJson(): string
    {
        $json = json_encode($this->toArray(), JSON_THROW_ON_ERROR);

        if (is_string($json)) {
            return $json;
        }

        throw new ValidationException('Failed to encode message to JSON');
    }

    /**
     * Create message from array.
     *
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['type'], $data['recipient'])) {
            throw new ValidationException('Message must contain type and recipient');
        }

        $message = new self(
            $data['type'],
            $data['recipient'],
            $data['data'] ?? [],
            $data['metadata'] ?? [],
            $data['options'] ?? []
        );

        if (isset($data['id'])) {
            $message->id = $data['id'];
        }

        if (isset($data['timestamp'])) {
            $message->timestamp = $data['timestamp'];
        }

        return $message;
    }

    /**
     * Create message from JSON.
     *
     * @throws ValidationException
     */
    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ValidationException("Invalid JSON: {$e->getMessage()}", 0, $e);
        }

        return self::fromArray($data);
    }

    /**
     * Generate UUID v4.
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80); // Variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
