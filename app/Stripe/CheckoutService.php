<?php

namespace App\Stripe;

use Stripe\Checkout\Session;
use Stripe\Stripe;
use RuntimeException;

final class CheckoutService
{
    public static function create(array $sessionData): Session
    {
        return Session::create($sessionData);
    }
	
    public static function retrieve(string $sessionId): Session
    {
        try {
            return Session::retrieve($sessionId);
        } catch (\Throwable $e) {
            throw new RuntimeException("Stripe session retrieval failed: " . $e->getMessage());
        }
    }	
}
