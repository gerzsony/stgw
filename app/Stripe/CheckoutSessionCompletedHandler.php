<?php

namespace App\Stripe;

use App\Support\AppLogger;
use App\Customize\CustomizeLoader;

final class CheckoutSessionCompletedHandler
{
    public static function handle(object $session, $config): void
    {
        AppLogger::get()->info('Checkout session completed', [
            'id' => $session->id
        ]);

        CustomizeLoader::loadWebhook(
            $config->get('customization_dir'),
            $session->toArray()
        );
    }
}
