<?php

namespace App\Config;
use RuntimeException;

final class StripeConfig
{
    public static function secretKey(): string
    {
        $key = $_ENV['STRIPE_SK_KEY'] ?? null;

        if (!$key) {
            throw new RuntimeException('STRIPE_SK_KEY is not defined');
        }

        return $key;
    }

    public static function webhookSecret(): string
    {
        $secret = $_ENV['STRIPE_WH_SECRET'] ?? null;

        if (!$secret) {
            throw new RuntimeException('STRIPE_WH_SECRET is not defined');
        }

        return $secret;
    }
}
