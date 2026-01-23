<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Core;

/**
 * Message priority constants.
 */
final class MessageType
{
    public const SMS = 'sms';
    public const WHATSAPP = 'whatsapp';
    public const EMAIL = 'email';

    /**
     * Get all valid priorities.
     */
    public static function all(): array
    {
        return [
            self::SMS,
            self::WHATSAPP,
            self::EMAIL,
        ];
    }

    /**
     * Check if priority is valid.
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::all(), true);
    }
}
