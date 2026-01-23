<?php

namespace InfoEsportes\Messaging\Core;

class RoutingKey
{
    public const SMS_NOTIFICATION = 'sms.notification';
    public const SMS_OTP = 'sms.otp';
    public const SMS_PROMOTIONAL = 'sms.promotional';
    public const SMS_URGENT = 'sms.urgent';

    public const WHATSAPP_NOTIFICATION = 'whatsapp.notification';
    public const WHATSAPP_OTP = 'whatsapp.otp';
    public const WHATSAPP_MARKETING = 'whatsapp.marketing';
    public const WHATSAPP_URGENT = 'whatsapp.urgent';

    public const EMAIL_TRANSACTIONAL = 'email.transactional';
    public const EMAIL_MARKETING = 'email.marketing';
    public const EMAIL_URGENT = 'email.urgent';

    /**
     * Get all valid routing keys.
     *
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::SMS_NOTIFICATION,
            self::WHATSAPP_NOTIFICATION,
            self::EMAIL_TRANSACTIONAL,
            self::SMS_OTP,
            self::SMS_PROMOTIONAL,
            self::WHATSAPP_OTP,
            self::WHATSAPP_MARKETING,
            self::EMAIL_MARKETING,
        ];
    }

    /**
     * Check if routing key is valid.
     */
    public static function isValid(string $routingKey): bool
    {
        return in_array($routingKey, self::all(), true);
    }
}
