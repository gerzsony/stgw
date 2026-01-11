<?php
namespace Tests\Unit\Http;

use App\Http\PaymentRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PaymentRequestTest extends TestCase
{
    public function test_fromRequest_parses_valid_did_payload(): void
    {
        $cart = [
            ['name' => 'Item 1', 'price' => 1000, 'qty' => 2, 'metadata' => []],
        ];
        $encoded = base64_encode(json_encode($cart));
        
        $result = PaymentRequest::fromRequest(['did' => $encoded]);
        
        $this->assertIsArray($result);
        $this->assertEquals($cart, $result);
    }

    public function test_fromRequest_fails_when_did_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing payment data');
        
        PaymentRequest::fromRequest([]);
    }

    public function test_fromRequest_fails_when_did_is_not_base64(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid base64 payment data');
        
        PaymentRequest::fromRequest(['did' => 'not-valid-base64!!!']);
    }

    public function test_fromRequest_fails_when_did_is_not_json(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON payment data');
        
        PaymentRequest::fromRequest(['did' => base64_encode('not json')]);
    }

    public function test_fromRequest_fails_when_decoded_json_is_not_array(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON payment data');
        
        PaymentRequest::fromRequest(['did' => base64_encode('"just a string"')]);
    }

    public function test_fromResultRequest_returns_instance_with_session_id(): void
    {
        $sessionId = 'test_session_123';
        
        $request = PaymentRequest::fromResultRequest(['sid' => $sessionId]);
        
        $this->assertInstanceOf(PaymentRequest::class, $request);
        $this->assertEquals($sessionId, $request->getSessionId());
    }

    public function test_fromResultRequest_fails_when_sid_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing or invalid session ID');
        
        PaymentRequest::fromResultRequest([]);
    }

    public function test_fromResultRequest_fails_when_sid_is_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing or invalid session ID');
        
        PaymentRequest::fromResultRequest(['sid' => '']);
    }
}

