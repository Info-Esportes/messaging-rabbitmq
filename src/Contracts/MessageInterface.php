<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Contracts;

interface MessageInterface
{
    /**
     * Get unique message identifier.
     */
    public function getId(): string;

    /**
     * Get message type (email, sms, whatsapp).
     */
    public function getType(): string;

    /**
     * Get recipient (email address or phone number).
     */
    public function getRecipient(): string;

    /**
     * Get message data.
     */
    public function getData(): array;

    /**
     * Get message metadata.
     */
    public function getMetadata(): array;

    /**
     * Get message options.
     */
    public function getOptions(): array;

    /**
     * Get message priority (1-10).
     */
    public function getPriority(): int;

    /**
     * Convert message to array.
     */
    public function toArray(): array;

    /**
     * Convert message to JSON.
     */
    public function toJson(): string;
}
