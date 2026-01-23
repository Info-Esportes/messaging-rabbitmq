<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Core;

class MessageValidator
{
    /**
     * Validate email address.
     */
    public function isValidEmail(string $email): bool
    {
        return false !== filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate phone number (basic validation)
     * For production, consider using libphonenumber-for-php.
     */
    public function isValidPhone(string $phone): bool
    {
        // Remove common separators
        $cleaned = preg_replace('/[\s\-\(\)\.]+/', '', $phone);

        if (null === $cleaned) {
            return false;
        }

        // Check if it's a valid format (+ followed by digits)
        // Minimum 10 digits, maximum 15 (E.164 standard)
        return 1 === preg_match('/^\+?[1-9]\d{9,14}$/', $cleaned);
    }

    /**
     * Validate URL.
     */
    public function isValidUrl(string $url): bool
    {
        return false !== filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Validate message priority (1-10).
     */
    public function isValidPriority(int $priority): bool
    {
        return $priority >= 1 && $priority <= 10;
    }

    /**
     * Validate message type.
     *
     * @param 'email'|'sms'|'whatsapp' $type
     */
    public function isValidType(string $type): bool
    {
        return MessageType::isValid($type);
    }

    /**
     * Validate complete message array.
     */
    public function validateMessageArray(array $data): array
    {
        $errors = [];

        if (!isset($data['type'])) {
            $errors[] = 'Missing required field: type';
        } elseif (!$this->isValidType($data['type'])) {
            $errors[] = 'Invalid message type: '.$data['type'];
        }

        if (!isset($data['recipient'])) {
            $errors[] = 'Missing required field: recipient';
        }

        if (isset($data['options']['priority']) && !$this->isValidPriority($data['options']['priority'])) {
            $errors[] = 'Invalid priority (must be 1-10)';
        }

        return $errors;
    }
}
