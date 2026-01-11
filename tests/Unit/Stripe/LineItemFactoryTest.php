<?php
declare(strict_types=1);

namespace Tests\Unit\Stripe;

use App\Stripe\LineItemFactory;
use PHPUnit\Framework\TestCase;

final class LineItemFactoryTest extends TestCase
{
    /** @test */
    public function it_converts_single_cart_item_to_stripe_format(): void
    {
        $cart = [
            [
                'name' => 'Test Product',
                'price' => 1000, // 1000 HUF
                'qty' => 1,
                'metadata' => ['sku' => 'PROD-001'],
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        $this->assertCount(1, $result);
        $this->assertEquals('huf', $result[0]['price_data']['currency']);
        $this->assertEquals('Test Product', $result[0]['price_data']['product_data']['name']);
        $this->assertEquals(100000, $result[0]['price_data']['unit_amount']); // 1000 * 100
        $this->assertEquals(1, $result[0]['quantity']);
        $this->assertEquals(['sku' => 'PROD-001'], $result[0]['price_data']['product_data']['metadata']);
    }

    /** @test */
    public function it_converts_multiple_cart_items(): void
    {
        $cart = [
            [
                'name' => 'Product A',
                'price' => 1500,
                'qty' => 2,
                'metadata' => [],
            ],
            [
                'name' => 'Product B',
                'price' => 2500,
                'qty' => 1,
                'metadata' => [],
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        $this->assertCount(2, $result);
        
        // First item
        $this->assertEquals('Product A', $result[0]['price_data']['product_data']['name']);
        $this->assertEquals(150000, $result[0]['price_data']['unit_amount']);
        $this->assertEquals(2, $result[0]['quantity']);
        
        // Second item
        $this->assertEquals('Product B', $result[1]['price_data']['product_data']['name']);
        $this->assertEquals(250000, $result[1]['price_data']['unit_amount']);
        $this->assertEquals(1, $result[1]['quantity']);
    }

    /** @test */
    public function it_handles_empty_cart(): void
    {
        $cart = [];

        $result = LineItemFactory::fromCart($cart);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_handles_missing_metadata(): void
    {
        $cart = [
            [
                'name' => 'Product Without Metadata',
                'price' => 500,
                'qty' => 1,
                // metadata intentionally missing
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        $this->assertCount(1, $result);
        $this->assertEquals([], $result[0]['price_data']['product_data']['metadata']);
    }

    /** @test */
    public function it_converts_price_correctly_to_cents(): void
    {
        $testCases = [
            ['price' => 1, 'expected' => 100],       // 1 HUF = 100 fillér
            ['price' => 100, 'expected' => 10000],   // 100 HUF = 10000 fillér
            ['price' => 1000, 'expected' => 100000], // 1000 HUF
            ['price' => 12345, 'expected' => 1234500], // 12345 HUF
        ];

        foreach ($testCases as $case) {
            $cart = [
                [
                    'name' => 'Test',
                    'price' => $case['price'],
                    'qty' => 1,
                    'metadata' => [],
                ],
            ];

            $result = LineItemFactory::fromCart($cart);

            $this->assertEquals(
                $case['expected'],
                $result[0]['price_data']['unit_amount'],
                "Failed for price: {$case['price']}"
            );
        }
    }

    /** @test */
    public function it_handles_different_quantities(): void
    {
        $cart = [
            [
                'name' => 'Bulk Product',
                'price' => 100,
                'qty' => 10,
                'metadata' => [],
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        $this->assertEquals(10, $result[0]['quantity']);
    }

    /** @test */
    public function it_casts_string_values_to_integers(): void
    {
        $cart = [
            [
                'name' => 'String Values Product',
                'price' => '1500', // string instead of int
                'qty' => '3',      // string instead of int
                'metadata' => [],
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        $this->assertIsInt($result[0]['price_data']['unit_amount']);
        $this->assertEquals(150000, $result[0]['price_data']['unit_amount']);
        $this->assertIsInt($result[0]['quantity']);
        $this->assertEquals(3, $result[0]['quantity']);
    }

    /** @test */
    public function it_handles_zero_quantity(): void
    {
        $cart = [
            [
                'name' => 'Zero Quantity Product',
                'price' => 1000,
                'qty' => 0,
                'metadata' => [],
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        $this->assertCount(1, $result);
        $this->assertEquals(0, $result[0]['quantity']);
    }

    /** @test */
    public function it_handles_zero_price(): void
    {
        $cart = [
            [
                'name' => 'Free Product',
                'price' => 0,
                'qty' => 1,
                'metadata' => [],
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        $this->assertCount(1, $result);
        $this->assertEquals(0, $result[0]['price_data']['unit_amount']);
    }

    /** @test */
    public function it_preserves_complex_metadata(): void
    {
        $metadata = [
            'sku' => 'PROD-123',
            'category' => 'electronics',
            'warehouse' => 'EU-WEST-1',
            'custom_field' => 'custom_value',
        ];

        $cart = [
            [
                'name' => 'Product with Metadata',
                'price' => 5000,
                'qty' => 2,
                'metadata' => $metadata,
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        $this->assertEquals($metadata, $result[0]['price_data']['product_data']['metadata']);
    }

    /** @test */
    public function it_handles_unicode_product_names(): void
    {
        $cart = [
            [
                'name' => 'Szálláshely 4 főre - Apartman',
                'price' => 29900,
                'qty' => 1,
                'metadata' => [],
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        $this->assertEquals('Szálláshely 4 főre - Apartman', $result[0]['price_data']['product_data']['name']);
    }

    /** @test */
    public function it_creates_correct_stripe_structure(): void
    {
        $cart = [
            [
                'name' => 'Test Product',
                'price' => 1000,
                'qty' => 1,
                'metadata' => ['key' => 'value'],
            ],
        ];

        $result = LineItemFactory::fromCart($cart);

        // Verify complete structure
        $this->assertArrayHasKey('price_data', $result[0]);
        $this->assertArrayHasKey('quantity', $result[0]);
        
        $priceData = $result[0]['price_data'];
        $this->assertArrayHasKey('currency', $priceData);
        $this->assertArrayHasKey('product_data', $priceData);
        $this->assertArrayHasKey('unit_amount', $priceData);
        
        $productData = $priceData['product_data'];
        $this->assertArrayHasKey('name', $productData);
        $this->assertArrayHasKey('metadata', $productData);
    }
}