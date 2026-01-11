<?php
declare(strict_types=1);

namespace Tests\Unit\Stripe;

use App\Config\Config;
use App\Stripe\CheckoutSessionCompletedHandler;
use PHPUnit\Framework\TestCase;

final class CheckoutSessionCompletedHandlerTest extends TestCase
{
    /** @test */
    public function it_does_nothing_if_session_has_no_id(): void
    {
        $session = (object)[
            'payment_status' => 'paid'
        ];

        $config = $this->createMock(Config::class);

        CheckoutSessionCompletedHandler::handle($session, $config);

        $this->assertTrue(true); // no exception
    }

    /** @test */
    public function it_does_nothing_if_payment_is_not_paid(): void
    {
        $session = (object)[
            'id' => 'cs_test_123',
            'payment_status' => 'unpaid'
        ];

        $config = $this->createMock(Config::class);

        CheckoutSessionCompletedHandler::handle($session, $config);

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

        $config = $this->createMock(Config::class);
        $config->method('get')->with('customization_dir')->willReturn('/tmp');

        // nincs assert → az a lényeg, hogy ne dobjon hibát
        CheckoutSessionCompletedHandler::handle($session, $config);

        $this->assertTrue(true);
    }
}
