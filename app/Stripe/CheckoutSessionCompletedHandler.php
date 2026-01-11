<?php
namespace App\Stripe;

use App\Config\AppConfig;
use App\Support\AppLogger;
use App\Customize\CustomizeLoader;

final class CheckoutSessionCompletedHandler
{
    public static function handle(object $session, AppConfig $config): void
    {
        AppLogger::get()->info('Checkout session completed', [
            'id' => $session->id ?? 'unknown'
        ]);
        
        if (!isset($session->id) || $session->payment_status !== 'paid') {
            return;
        }
        
        CustomizeLoader::loadWebhook(
            $config->get('customization_dir'),
            $session->toArray()
        );
    }
}
