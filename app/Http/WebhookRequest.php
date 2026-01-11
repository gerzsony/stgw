<?php

namespace App\Http;

use RuntimeException;

final class WebhookRequest
{
    public static function fromGlobals(): array
    {
        $payload = file_get_contents('php://input');
        if (!$payload) {
            throw new RuntimeException('Empty payload');
        }

        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
        if (!$signature) {
            throw new RuntimeException('Missing Stripe signature');
        }

        return [$payload, $signature];
    }
}
