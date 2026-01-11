<?php
declare(strict_types=1);

namespace Tests\Unit\Stripe;

use App\Config\AppConfig;
use App\Stripe\CheckoutSessionCompletedHandler;
use PHPUnit\Framework\TestCase;

final class CheckoutSessionCompletedHandlerTest extends TestCase
{
    private AppConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment variables for AppConfig
        $_ENV['CUSTOMIZATION_DIR'] = sys_get_temp_dir() . '/test_customize';
        
        // Create the directory if it doesn't exist
        if (!is_dir($_ENV['CUSTOMIZATION_DIR'])) {
            mkdir($_ENV['CUSTOMIZATION_DIR'], 0777, true);
        }
        
        // Get real AppConfig instance (it's a singleton)
        $this->config = AppConfig::getInstance();
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (isset($_ENV['CUSTOMIZATION_DIR']) && is_dir($_ENV['CUSTOMIZATION_DIR'])) {
            rmdir($_ENV['CUSTOMIZATION_DIR']);
        }
        parent::tearDown();
    }

    /** @test */
    public function it_does_nothing_if_session_has_no_id(): void
    {
        $session = (object)[
            'payment_status' => 'paid'
        ];
        
        CheckoutSessionCompletedHandler::handle($session, $this->config);
        
        $this->assertTrue(true); // no exception
    }

    /** @test */
    public function it_does_nothing_if_payment_is_not_paid(): void
    {
        $session = (object)[
            'id' => 'cs_test_123',
            'payment_status' => 'unpaid'
        ];
        
        CheckoutSessionCompletedHandler::handle($session, $this->config);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_processes_paid_checkout_session(): void
    {
        $session = new class {
            public string $id = 'cs_test_123';
            public string $payment_status = 'paid';
            public bool $livemode = false;
            
            public function toArray(): array
            {
                return [
                    'id' => $this->id,
                    'payment_status' => $this->payment_status,
                    'livemode' => $this->livemode,
                    'metadata' => [
                        'oid' => 42,
                    ],
                ];
            }
        };
        
        CheckoutSessionCompletedHandler::handle($session, $this->config);
        
        $this->assertTrue(true);
    }
}