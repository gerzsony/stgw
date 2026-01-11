<?php
declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\StripeConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StripeConfigTest extends TestCase
{
    private ?string $originalSecretKey = null;
    private ?string $originalWebhookSecret = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Backup original env values
        $this->originalSecretKey = $_ENV['STRIPE_SK_KEY'] ?? null;
        $this->originalWebhookSecret = $_ENV['STRIPE_WH_SECRET'] ?? null;
    }

    protected function tearDown(): void
    {
        // Restore original env values
        if ($this->originalSecretKey !== null) {
            $_ENV['STRIPE_SK_KEY'] = $this->originalSecretKey;
        } else {
            unset($_ENV['STRIPE_SK_KEY']);
        }
        
        if ($this->originalWebhookSecret !== null) {
            $_ENV['STRIPE_WH_SECRET'] = $this->originalWebhookSecret;
        } else {
            unset($_ENV['STRIPE_WH_SECRET']);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function it_returns_secret_key_from_environment(): void
    {
        $_ENV['STRIPE_SK_KEY'] = 'test_key_value_123';

        $result = StripeConfig::secretKey();

        $this->assertEquals('test_key_value_123', $result);
    }

    /** @test */
    public function it_throws_exception_when_secret_key_is_missing(): void
    {
        unset($_ENV['STRIPE_SK_KEY']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('STRIPE_SK_KEY is not defined');

        StripeConfig::secretKey();
    }

    /** @test */
    public function it_throws_exception_when_secret_key_is_null(): void
    {
        $_ENV['STRIPE_SK_KEY'] = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('STRIPE_SK_KEY is not defined');

        StripeConfig::secretKey();
    }

    /** @test */
    public function it_returns_webhook_secret_from_environment(): void
    {
        $_ENV['STRIPE_WH_SECRET'] = 'test_webhook_secret_abc';

        $result = StripeConfig::webhookSecret();

        $this->assertEquals('test_webhook_secret_abc', $result);
    }

    /** @test */
    public function it_throws_exception_when_webhook_secret_is_missing(): void
    {
        unset($_ENV['STRIPE_WH_SECRET']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('STRIPE_WH_SECRET is not defined');

        StripeConfig::webhookSecret();
    }

    /** @test */
    public function it_throws_exception_when_webhook_secret_is_null(): void
    {
        $_ENV['STRIPE_WH_SECRET'] = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('STRIPE_WH_SECRET is not defined');

        StripeConfig::webhookSecret();
    }

    /** @test */
    public function it_accepts_empty_string_as_invalid(): void
    {
        $_ENV['STRIPE_SK_KEY'] = '';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('STRIPE_SK_KEY is not defined');

        StripeConfig::secretKey();
    }

    /** @test */
    public function it_returns_string_type_for_secret_key(): void
    {
        $_ENV['STRIPE_SK_KEY'] = 'any_string_key';

        $result = StripeConfig::secretKey();

        $this->assertIsString($result);
    }

    /** @test */
    public function it_returns_string_type_for_webhook_secret(): void
    {
        $_ENV['STRIPE_WH_SECRET'] = 'any_webhook_secret';

        $result = StripeConfig::webhookSecret();

        $this->assertIsString($result);
    }

    /** @test */
    public function it_does_not_trim_or_modify_secret_key(): void
    {
        $keyWithSpaces = '  test_key_with_spaces  ';
        $_ENV['STRIPE_SK_KEY'] = $keyWithSpaces;

        $result = StripeConfig::secretKey();

        // Should return exactly as provided (no trimming)
        $this->assertEquals($keyWithSpaces, $result);
    }

    /** @test */
    public function it_does_not_trim_or_modify_webhook_secret(): void
    {
        $secretWithSpaces = '  test_secret_with_spaces  ';
        $_ENV['STRIPE_WH_SECRET'] = $secretWithSpaces;

        $result = StripeConfig::webhookSecret();

        // Should return exactly as provided (no trimming)
        $this->assertEquals($secretWithSpaces, $result);
    }
}