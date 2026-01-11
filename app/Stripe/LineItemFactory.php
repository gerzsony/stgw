<?php

namespace App\Stripe;

final class LineItemFactory
{
    public static function fromCart(array $cart): array
    {
        $lineItems = [];

        foreach ($cart as $item) {
            $lineItem = [
                'price_data' => [
                    'currency' => 'huf',
                    'product_data' => [
                        'name' => $item['name'],
                        'metadata' => $item['metadata'] ?? [],
                    ],
                    'unit_amount' => (int) $item['price'] * 100,
                ],
                'quantity' => (int) $item['qty'],
            ];

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }
}
