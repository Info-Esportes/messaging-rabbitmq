<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Contracts;

use InfoEsportes\Messaging\Exceptions\PublishException;

interface MessagePublisherInterface
{
    /**
     * Publish a message to RabbitMQ.
     *
     * @param string $exchange   Exchange name
     * @param string $routingKey Routing key
     * @param array  $payload    Message payload
     * @param int    $priority   Message priority (1-10)
     *
     * @return bool Success
     *
     * @throws PublishException
     */
    public function publish(string $exchange, string $routingKey, array $payload, int $priority = 5): bool;

    /**
     * Send email message.
     *
     * @param string $to       Recipient email address
     * @param string $subject  Email subject
     * @param string $body     Email body (HTML or text)
     * @param string $template Email template
     * @param array  $variables Template variables
     * @param array  $data     Additional data (metadata, etc.)
     * @param int    $priority Message priority (1-10)
     *
     * @return bool Success
     *
     * @throws PublishException
     */
    public function sendEmail(string $to, ?string $subject = null, ?string $body = null, ?string $template = null, array $variables = [], array $data = [], string $routingKey = 'email.transactional', int $priority = 5): bool;

    /**
     * Send SMS message.
     *
     * @param string $phone    Recipient phone number
     * @param string $message  SMS text
     * @param string $template SMS template
     * @param array  $variables Template variables
     * @param int    $priority Message priority (1-10)
     *
     * @return bool Success
     *
     * @throws PublishException
     */
    public function sendSMS(string $phone, ?string $message = null, ?string $template = null, array $variables = [], string $routingKey = 'sms.message', int $priority = 8): bool;

    /**
     * Send WhatsApp message.
     *
     * @param string      $phone    Recipient phone number
     * @param string      $message  Message text
     * @param null|string $template WhatsApp template
     * @param array       $variables Template variables
     * @param null|string $media    Media URL (image, video, document)
     * @param int         $priority Message priority (1-10)
     *
     * @return bool Success
     *
     * @throws PublishException
     */
    public function sendWhatsApp(string $phone, ?string $message = null, ?string $template = null, array $variables = [], string $routingKey = 'whatsapp.notification', ?string $media = null, int $priority = 7): bool;

    /**
     * Check if connection is alive.
     */
    public function isConnected(): bool;

    /**
     * Close connection.
     */
    public function disconnect(): void;
}
