<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\DTO;

class SMSMessage
{
    public function __construct(
        public readonly string $phone,
        public readonly string $message,
        public readonly array $metadata = [],
        public readonly int $priority = 2 // MessagePriority::HIGH
    ) {}

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            phone: $data['phone'] ?? '',
            message: $data['message'] ?? '',
            metadata: $data['metadata'] ?? [],
            priority: $data['priority'] ?? 2
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'phone' => $this->phone,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'priority' => $this->priority,
        ];
    }
}
