<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Stripe\LineItemFactory;

final class LineItemFactoryTest extends TestCase
{
    public function testLineItemFromCart(): void
    {
        $cart = [
            [
                'name' => 'Szálláshely 4 főre',
                'price' => 29900,
                'qty' => 1,
                'metadata' => ['booking_id' => 123],
            ],
        ];

        $lineItems = LineItemFactory::fromCart($cart);

        $this->assertIsArray($lineItems);
        $this->assertEquals(29900*100, $lineItems[0]['price_data']['unit_amount']);
        $this->assertEquals('Szálláshely 4 főre', $lineItems[0]['price_data']['product_data']['name']);
    }
}
