<?php

namespace App\Stripe;

use Stripe\Webhook;
use App\Config\StripeConfig;

final class WebhookVerifier
{
    public static function verify(string $payload, string $signature)
    {
        return Webhook::constructEvent(
            $payload,
            $signature,
            StripeConfig::webhookSecret()
        );
    }
}
