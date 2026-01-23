<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Core;

/**
 * Message priority constants.
 */
final class MessagePriority
{
    public const LOWEST = 1;
    public const LOW = 3;
    public const NORMAL = 5;
    public const HIGH = 7;
    public const URGENT = 9;
    public const CRITICAL = 10;

    /**
     * Get all valid priorities.
     */
    public static function all(): array
    {
        return [
            self::LOWEST,
            self::LOW,
            self::NORMAL,
            self::HIGH,
            self::URGENT,
            self::CRITICAL,
        ];
    }

    /**
     * Check if priority is valid.
     */
    public static function isValid(int $priority): bool
    {
        return $priority >= self::LOWEST && $priority <= self::CRITICAL;
    }
}
