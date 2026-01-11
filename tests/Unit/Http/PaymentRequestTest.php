<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\PaymentRequest;
use PHPUnit\Framework\TestCase;

final class PaymentRequestTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
    }

    /** @test */
    public function it_parses_valid_did_payload(): void
    {
        $payload = [
            'oid'   => 123,
            'cart'  => [['name' => 'Test', 'price' => 1000, 'qty' => 1]],
            'email' => 'test@example.com',
        ];

        $_GET['did'] = base64_encode(json_encode($payload));

        $request = PaymentRequest::fromGlobals();

        $this->assertSame(123, $request->getOid());
        $this->assertSame($payload['cart'], $request->getCart());
        $this->assertSame('test@example.com', $request->getEmail());
    }

    /** @test */
    public function it_fails_when_did_is_missing(): void
    {
        $this->expectException(\RuntimeException::class);

        PaymentRequest::fromGlobals();
    }

    /** @test */
    public function it_fails_when_did_is_not_base64(): void
    {
        $_GET['did'] = '###INVALID###';

        $this->expectException(\RuntimeException::class);

        PaymentRequest::fromGlobals();
    }

    /** @test */
    public function it_fails_when_did_is_not_json(): void
    {
        $_GET['did'] = base64_encode('just-a-string');

        $this->expectException(\RuntimeException::class);

        PaymentRequest::fromGlobals();
    }

    /** @test */
    public function it_fails_when_decoded_json_is_not_array(): void
    {
        $_GET['did'] = base64_encode(json_encode('string'));

        $this->expectException(\RuntimeException::class);

        PaymentRequest::fromGlobals();
    }

    /** @test */
    public function it_fails_when_cart_is_missing(): void
    {
        $payload = ['oid' => 1];

        $_GET['did'] = base64_encode(json_encode($payload));

        $this->expectException(\RuntimeException::class);

        PaymentRequest::fromGlobals();
    }
}
