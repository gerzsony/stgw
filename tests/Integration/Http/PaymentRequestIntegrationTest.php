<?php
declare(strict_types=1);

namespace Tests\Integration\Http;

use PHPUnit\Framework\TestCase;
use App\Http\PaymentRequest;

final class PaymentRequestIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SERVER = [];
    }

    public function test_it_parses_request_from_globals(): void
    {
        // Create cart data
        $cart = [
            [
                'name' => 'Test Product',
                'price' => 1000,
                'qty' => 1,
                'metadata' => [],
            ],
        ];
        
        // Encode it as the application expects
        $encoded = base64_encode(json_encode($cart));
        
        // Simulate POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['did'] = $encoded;
        $_REQUEST = $_POST;
        
        // Parse it
        $result = PaymentRequest::fromRequest($_REQUEST);
        
        $this->assertIsArray($result);
        $this->assertEquals($cart, $result);
    }

    public function test_it_parses_result_request_with_session_id(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['sid'] = 'cs_test_123456';
        
        $request = PaymentRequest::fromResultRequest($_GET);
        
        $this->assertInstanceOf(PaymentRequest::class, $request);
        $this->assertEquals('cs_test_123456', $request->getSessionId());
    }
}
