<?php

declare(strict_types=1);

namespace InfoEsportes\Messaging\Contracts;

interface MessageConsumerInterface
{
    /**
     * Start consuming messages.
     *
     * @param string   $queueName Queue to consume from
     * @param callable $callback  Callback function to process messages
     */
    public function consume(string $queueName, callable $callback): void;

    /**
     * Stop consuming messages.
     */
    public function stop(): void;

    /**
     * Check if consumer is running.
     */
    public function isConsuming(): bool;
}
