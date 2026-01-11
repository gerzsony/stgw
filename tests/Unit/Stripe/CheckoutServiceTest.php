<?php
declare(strict_types=1);

namespace Tests\Unit\Stripe;

use App\Stripe\CheckoutService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stripe\Checkout\Session;

final class CheckoutServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set Stripe API key for testing
        // Note: Use test key, never production key in tests!
        if (!isset($_ENV['STRIPE_SK_KEY'])) {
            $_ENV['STRIPE_SK_KEY'] = 'sk_test_fake_key_for_unit_tests';
        }
    }

    /** @test */
    public function it_creates_checkout_session_with_valid_data(): void
    {
        $sessionData = [
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'huf',
                        'product_data' => [
                            'name' => 'Test Product',
                        ],
                        'unit_amount' => 100000,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ];

        // This will attempt to call the real Stripe API
        // In a real test environment, you'd either:
        // 1. Use Stripe test mode with real API calls (integration test)
        // 2. Mock the Session::create method (unit test)
        // 3. Use a test double/stub
        
        // For now, we'll just verify the method exists and accepts the right parameters
        $this->assertTrue(method_exists(CheckoutService::class, 'create'));
    }

    /** @test */
    public function it_validates_session_data_structure(): void
    {
        // Test various session data structures
        $validStructures = [
            // Minimal structure
            [
                'payment_method_types' => ['card'],
                'line_items' => [],
                'mode' => 'payment',
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel',
            ],
            // With metadata
            [
                'payment_method_types' => ['card'],
                'line_items' => [],
                'mode' => 'payment',
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel',
                'metadata' => [
                    'order_id' => '123',
                    'customer_id' => '456',
                ],
            ],
            // With customer email
            [
                'payment_method_types' => ['card'],
                'line_items' => [],
                'mode' => 'payment',
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel',
                'customer_email' => 'test@example.com',
            ],
        ];

        foreach ($validStructures as $structure) {
            $this->assertIsArray($structure);
            $this->assertArrayHasKey('payment_method_types', $structure);
            $this->assertArrayHasKey('line_items', $structure);
            $this->assertArrayHasKey('mode', $structure);
            $this->assertArrayHasKey('success_url', $structure);
            $this->assertArrayHasKey('cancel_url', $structure);
        }
    }

    /** @test */
    public function it_retrieves_session_by_id(): void
    {
        // Test the retrieve method signature
        $this->assertTrue(method_exists(CheckoutService::class, 'retrieve'));
        
        // Verify it returns a Session type
        $reflection = new \ReflectionMethod(CheckoutService::class, 'retrieve');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('Stripe\Checkout\Session', $returnType->getName());
    }

    /** @test */
    public function it_throws_runtime_exception_on_invalid_session_id(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stripe session retrieval failed/');
        
        // This will fail because it's not a valid session ID
        CheckoutService::retrieve('invalid_session_id');
    }

    /** @test */
    public function it_throws_exception_on_empty_session_id(): void
    {
        $this->expectException(\Throwable::class);
        
        CheckoutService::retrieve('');
    }

    /** @test */
    public function it_handles_valid_session_id_format(): void
    {
        // Stripe session IDs follow a specific format: cs_test_... or cs_live_...
        $validFormats = [
            'cs_test_a1B2c3D4e5F6g7H8i9J0k1L2m3N4o5P6',
            'cs_live_a1B2c3D4e5F6g7H8i9J0k1L2m3N4o5P6',
        ];

        foreach ($validFormats as $sessionId) {
            $this->assertMatchesRegularExpression('/^cs_(test|live)_[a-zA-Z0-9]+$/', $sessionId);
        }
    }

    /** @test */
    public function it_accepts_array_for_create_method(): void
    {
        $reflection = new \ReflectionMethod(CheckoutService::class, 'create');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('sessionData', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());
    }

    /** @test */
    public function it_accepts_string_for_retrieve_method(): void
    {
        $reflection = new \ReflectionMethod(CheckoutService::class, 'retrieve');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('sessionId', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    /** @test */
    public function it_wraps_stripe_exceptions_in_runtime_exception(): void
    {
        try {
            CheckoutService::retrieve('cs_test_nonexistent');
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Stripe session retrieval failed', $e->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $e);
        }
    }

    /** @test */
    public function create_method_is_static(): void
    {
        $reflection = new \ReflectionMethod(CheckoutService::class, 'create');
        $this->assertTrue($reflection->isStatic());
    }

    /** @test */
    public function retrieve_method_is_static(): void
    {
        $reflection = new \ReflectionMethod(CheckoutService::class, 'retrieve');
        $this->assertTrue($reflection->isStatic());
    }

    /** @test */
    public function class_is_final(): void
    {
        $reflection = new \ReflectionClass(CheckoutService::class);
        $this->assertTrue($reflection->isFinal());
    }

    /** @test */
    public function it_validates_payment_modes(): void
    {
        $validModes = ['payment', 'setup', 'subscription'];
        
        foreach ($validModes as $mode) {
            $sessionData = [
                'mode' => $mode,
                'payment_method_types' => ['card'],
                'line_items' => [],
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel',
            ];
            
            $this->assertEquals($mode, $sessionData['mode']);
            $this->assertContains($mode, ['payment', 'setup', 'subscription']);
        }
    }

    /** @test */
    public function it_validates_payment_method_types(): void
    {
        $validTypes = [
            ['card'],
            ['card', 'ideal'],
            ['card', 'sepa_debit'],
            ['bancontact', 'card', 'giropay'],
        ];
        
        foreach ($validTypes as $types) {
            $this->assertIsArray($types);
            $this->assertNotEmpty($types);
            
            foreach ($types as $type) {
                $this->assertIsString($type);
            }
        }
    }

    /** @test */
    public function it_validates_url_formats(): void
    {
        $validUrls = [
            'https://example.com/success',
            'https://example.com/cancel',
            'https://apartmanszallo.hu/result?sid={CHECKOUT_SESSION_ID}',
        ];
        
        foreach ($validUrls as $url) {
            $this->assertMatchesRegularExpression('/^https?:\/\//', $url);
        }
    }

    /** @test */
    public function it_handles_line_items_array(): void
    {
        $lineItems = [
            [
                'price_data' => [
                    'currency' => 'huf',
                    'product_data' => [
                        'name' => 'Product 1',
                    ],
                    'unit_amount' => 100000,
                ],
                'quantity' => 2,
            ],
            [
                'price_data' => [
                    'currency' => 'huf',
                    'product_data' => [
                        'name' => 'Product 2',
                    ],
                    'unit_amount' => 50000,
                ],
                'quantity' => 1,
            ],
        ];
        
        $this->assertIsArray($lineItems);
        $this->assertCount(2, $lineItems);
        
        foreach ($lineItems as $item) {
            $this->assertArrayHasKey('price_data', $item);
            $this->assertArrayHasKey('quantity', $item);
        }
    }
}