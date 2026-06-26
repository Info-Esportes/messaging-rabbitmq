<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\DTO;

class EmailMessage
{
    public function __construct(
        public readonly string $to,
        public readonly ?string $subject = null,
        public readonly ?string $body = null,
        public readonly ?string $template = null,
        public readonly array $variables = [],
        public readonly array $metadata = [],
        public readonly int $priority = 1 // MessagePriority::NORMAL
    ) {}

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            to: $data['to'] ?? '',
            subject: $data['subject'] ?? null,
            body: $data['body'] ?? null,
            template: $data['template'] ?? null,
            variables: $data['variables'] ?? [],
            metadata: $data['metadata'] ?? [],
            priority: $data['priority'] ?? 1
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'to' => $this->to,
            'subject' => $this->subject,
            'body' => $this->body,
            'template' => $this->template,
            'variables' => $this->variables,
            'metadata' => $this->metadata,
            'priority' => $this->priority,
        ];
    }
}
