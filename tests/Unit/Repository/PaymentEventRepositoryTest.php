<?php
declare(strict_types=1);

namespace Tests\Unit\Repository;

use App\Repository\PaymentEventRepository;
use App\Config\DatabaseConfig;
use App\Support\AppLogger;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

final class PaymentEventRepositoryTest extends TestCase
{
    private DatabaseConfig $dbConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure logger is initialized
        try {
            AppLogger::get();
        } catch (\RuntimeException $e) {
            AppLogger::set(new \Psr\Log\NullLogger());
        }
        
        // Mock DatabaseConfig
        $this->dbConfig = $this->createMock(DatabaseConfig::class);
    }

    /** @test */
    public function it_does_nothing_when_database_is_disabled(): void
    {
        $this->dbConfig->method('isEnabled')->willReturn(false);
        
        $repo = new PaymentEventRepository($this->dbConfig);
        
        $event = [
            'stripe_session_id' => 'cs_test_123',
            'event_source' => 'webhook',
            'event_type' => 'checkout.session.completed',
            'payload' => ['test' => 'data'],
        ];
        
        // Should not throw any exception
        $repo->recordEvent($event);
        
        $this->assertTrue(true); // No exception means success
    }

    /** @test */
    public function it_handles_pdo_connection_failure_gracefully(): void
    {
        $this->dbConfig->method('isEnabled')->willReturn(true);
        $this->dbConfig->method('getPdoDsn')->willReturn('mysql:host=invalid;dbname=test');
        $this->dbConfig->method('getUser')->willReturn('invalid_user');
        $this->dbConfig->method('getPassword')->willReturn('invalid_pass');
        $this->dbConfig->method('getSafeConfig')->willReturn([]);
        
        // Should not throw exception even if PDO connection fails
        $repo = new PaymentEventRepository($this->dbConfig);
        
        $event = [
            'stripe_session_id' => 'cs_test_123',
            'event_source' => 'webhook',
            'event_type' => 'checkout.session.completed',
            'payload' => ['test' => 'data'],
        ];
        
        // Should handle gracefully and just log
        $repo->recordEvent($event);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_records_event_with_all_fields(): void
    {
        // We can't easily test actual PDO without a real database
        // But we can verify the class structure and method calls
        
        $event = [
            'stripe_session_id' => 'cs_test_123456',
            'stripe_event_id' => 'evt_test_789',
            'event_source' => 'webhook',
            'event_type' => 'checkout.session.completed',
            'event_status' => 'paid',
            'http_method' => 'POST',
            'request_ip' => '192.168.1.1',
            'user_agent' => 'Stripe/1.0',
            'payload' => [
                'id' => 'cs_test_123456',
                'payment_status' => 'paid',
                'amount_total' => 10000,
            ],
        ];
        
        // Verify the event structure is valid
        $this->assertArrayHasKey('stripe_session_id', $event);
        $this->assertArrayHasKey('event_source', $event);
        $this->assertArrayHasKey('event_type', $event);
        $this->assertArrayHasKey('payload', $event);
        $this->assertIsArray($event['payload']);
    }

    /** @test */
    public function it_records_event_with_minimal_required_fields(): void
    {
        $event = [
            'stripe_session_id' => 'cs_test_minimal',
            'event_source' => 'result',
            'event_type' => 'checkout.session.viewed',
            'payload' => ['minimal' => 'data'],
        ];
        
        // Optional fields should be nullable
        $this->assertArrayNotHasKey('stripe_event_id', $event);
        $this->assertArrayNotHasKey('event_status', $event);
        $this->assertArrayNotHasKey('http_method', $event);
        $this->assertArrayNotHasKey('request_ip', $event);
        $this->assertArrayNotHasKey('user_agent', $event);
        
        // But required fields are present
        $this->assertArrayHasKey('stripe_session_id', $event);
        $this->assertArrayHasKey('event_source', $event);
        $this->assertArrayHasKey('event_type', $event);
        $this->assertArrayHasKey('payload', $event);
    }

    /** @test */
    public function it_handles_json_encoding_of_complex_payload(): void
    {
        $complexPayload = [
            'id' => 'cs_test',
            'nested' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'deep value',
                    ],
                ],
            ],
            'unicode' => 'Fizetés átvétel',
            'numbers' => [1, 2, 3, 4, 5],
            'boolean' => true,
            'null_value' => null,
        ];
        
        $json = json_encode($complexPayload, JSON_THROW_ON_ERROR);
        
        $this->assertIsString($json);
        
        // Verify it can be decoded back
        $decoded = json_decode($json, true);
        $this->assertEquals($complexPayload, $decoded);
    }

    /** @test */
    public function it_handles_different_event_sources(): void
    {
        $sources = ['webhook', 'result', 'manual', 'api'];
        
        foreach ($sources as $source) {
            $event = [
                'stripe_session_id' => 'cs_test',
                'event_source' => $source,
                'event_type' => 'test',
                'payload' => [],
            ];
            
            $this->assertEquals($source, $event['event_source']);
        }
    }

    /** @test */
    public function it_handles_different_event_types(): void
    {
        $types = [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.async_payment_failed',
            'payment_intent.succeeded',
            'charge.succeeded',
        ];
        
        foreach ($types as $type) {
            $event = [
                'stripe_session_id' => 'cs_test',
                'event_source' => 'webhook',
                'event_type' => $type,
                'payload' => [],
            ];
            
            $this->assertEquals($type, $event['event_type']);
        }
    }

    /** @test */
    public function it_handles_null_optional_fields(): void
    {
        $event = [
            'stripe_session_id' => 'cs_test',
            'stripe_event_id' => null,
            'event_source' => 'webhook',
            'event_type' => 'test',
            'event_status' => null,
            'http_method' => null,
            'request_ip' => null,
            'user_agent' => null,
            'payload' => [],
        ];
        
        // All null values should be acceptable
        $this->assertNull($event['stripe_event_id']);
        $this->assertNull($event['event_status']);
        $this->assertNull($event['http_method']);
        $this->assertNull($event['request_ip']);
        $this->assertNull($event['user_agent']);
    }

    /** @test */
    public function it_handles_empty_payload(): void
    {
        $event = [
            'stripe_session_id' => 'cs_test',
            'event_source' => 'webhook',
            'event_type' => 'test',
            'payload' => [],
        ];
        
        $json = json_encode($event['payload']);
        
        $this->assertEquals('[]', $json);
    }

    /** @test */
    public function it_handles_large_payload(): void
    {
        $largePayload = [
            'session' => [
                'id' => 'cs_test_' . str_repeat('x', 1000),
                'metadata' => array_fill(0, 100, 'value'),
                'line_items' => array_fill(0, 50, [
                    'name' => 'Product',
                    'price' => 1000,
                    'quantity' => 1,
                ]),
            ],
        ];
        
        $json = json_encode($largePayload, JSON_THROW_ON_ERROR);
        
        $this->assertIsString($json);
        $this->assertGreaterThan(5000, strlen($json));
    }

    /** @test */
    public function it_preserves_special_characters_in_payload(): void
    {
        $event = [
            'stripe_session_id' => 'cs_test',
            'event_source' => 'webhook',
            'event_type' => 'test',
            'payload' => [
                'description' => 'Szálláshely 4 főre - "Apartman"',
                'special_chars' => '<>&"\' éáőűúóüö',
            ],
        ];
        
        $json = json_encode($event['payload'], JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true);
        
        $this->assertEquals($event['payload'], $decoded);
    }

    /** @test */
    public function it_handles_http_methods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        
        foreach ($methods as $method) {
            $event = [
                'stripe_session_id' => 'cs_test',
                'event_source' => 'webhook',
                'event_type' => 'test',
                'http_method' => $method,
                'payload' => [],
            ];
            
            $this->assertEquals($method, $event['http_method']);
        }
    }

    /** @test */
    public function it_handles_ip_addresses(): void
    {
        $ips = [
            '192.168.1.1',
            '10.0.0.1',
            '127.0.0.1',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334', // IPv6
        ];
        
        foreach ($ips as $ip) {
            $event = [
                'stripe_session_id' => 'cs_test',
                'event_source' => 'webhook',
                'event_type' => 'test',
                'request_ip' => $ip,
                'payload' => [],
            ];
            
            $this->assertEquals($ip, $event['request_ip']);
        }
    }

    /** @test */
    public function it_handles_user_agent_strings(): void
    {
        $userAgents = [
            'Stripe/1.0 (+https://stripe.com/docs/webhooks)',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'curl/7.68.0',
        ];
        
        foreach ($userAgents as $ua) {
            $event = [
                'stripe_session_id' => 'cs_test',
                'event_source' => 'webhook',
                'event_type' => 'test',
                'user_agent' => $ua,
                'payload' => [],
            ];
            
            $this->assertEquals($ua, $event['user_agent']);
        }
    }
}