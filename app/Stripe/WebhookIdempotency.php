<?php

namespace App\Stripe;

final class WebhookIdempotency
{
    public static function alreadyProcessed(string $eventId): bool
    {
        $file = __DIR__ . '/../../logs/webhook_ids.log';

        if (!is_file($file)) {
            return false;
        }

        return in_array($eventId, file($file, FILE_IGNORE_NEW_LINES), true);
    }

    public static function markProcessed(string $eventId): void
    {
        file_put_contents(
            __DIR__ . '/../../logs/webhook_ids.log',
            $eventId . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
