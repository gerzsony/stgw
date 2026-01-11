<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\PaymentRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PaymentRequestTest extends TestCase
{
    /** @test */
    public function it_parses_valid_did_payload(): void
    {
        $cart = [
            ['name' => 'Item 1', 'price' => 1000, 'qty' => 2, 'metadata' => []],
        ];
        $encoded = base64_encode(json_encode($cart));
        
        $result = PaymentRequest::fromRequest(['did' => $encoded]);
        
        $this->assertIsArray($result);
        $this->assertEquals($cart, $result);
    }

    /** @test */
    public function it_fails_when_did_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing payment data');
        
        PaymentRequest::fromRequest([]);
    }

    /** @test */
    public function it_fails_when_did_is_not_base64(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid base64 payment data');
        
        PaymentRequest::fromRequest(['did' => 'not-valid-base64!!!']);
    }

    /** @test */
    public function it_fails_when_did_is_not_json(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON payment data');
        
        PaymentRequest::fromRequest(['did' => base64_encode('not json')]);
    }

    /** @test */
    public function it_fails_when_decoded_json_is_not_array(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON payment data');
        
        PaymentRequest::fromRequest(['did' => base64_encode('"just a string"')]);
    }

    /** @test */
    public function it_returns_instance_with_session_id(): void
    {
        $sessionId = 'test_session_123';
        
        $request = PaymentRequest::fromResultRequest(['sid' => $sessionId]);
        
        $this->assertInstanceOf(PaymentRequest::class, $request);
        $this->assertEquals($sessionId, $request->getSessionId());
    }

    /** @test */
    public function it_fails_when_sid_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing or invalid session ID');
        
        PaymentRequest::fromResultRequest([]);
    }

    /** @test */
    public function it_fails_when_sid_is_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing or invalid session ID');
        
        PaymentRequest::fromResultRequest(['sid' => '']);
    }
}