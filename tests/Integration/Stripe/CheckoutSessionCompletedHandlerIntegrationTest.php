<?php
declare(strict_types=1);

namespace Tests\Integration\Stripe;

use PHPUnit\Framework\TestCase;
use App\Stripe\CheckoutSessionCompletedHandler;
use App\Config\AppConfig;
use App\Support\AppLogger;

final class CheckoutSessionCompletedHandlerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure logger is initialized
        try {
            AppLogger::get();
        } catch (\RuntimeException $e) {
            AppLogger::set(new \Psr\Log\NullLogger());
        }
        
        // Set up test environment
        $_ENV['CUSTOMIZATION_DIR'] = sys_get_temp_dir() . '/test_customize';
        if (!is_dir($_ENV['CUSTOMIZATION_DIR'])) {
            mkdir($_ENV['CUSTOMIZATION_DIR'], 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (isset($_ENV['CUSTOMIZATION_DIR']) && is_dir($_ENV['CUSTOMIZATION_DIR'])) {
            rmdir($_ENV['CUSTOMIZATION_DIR']);
        }
        parent::tearDown();
    }

    public function test_it_processes_paid_checkout_session(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/checkout_session_completed.json');
        $payload = json_decode($json, true);
        
        // Create session object using anonymous class
        $session = new class($payload) {
            private array $data;
            public string $id;
            public string $payment_status;
            
            public function __construct(array $payload) {
                $this->data = $payload['data']['object'];
                $this->id = $this->data['id'];
                $this->payment_status = $this->data['payment_status'];
            }
            
            public function toArray(): array {
                return $this->data;
            }
        };
        
        $config = AppConfig::getInstance();
        
        // Should not throw exception
        CheckoutSessionCompletedHandler::handle($session, $config);
        
        $this->assertTrue(true);
    }
}
