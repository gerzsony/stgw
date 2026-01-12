<?php
// tests/Integration/Repository/PaymentEventRepositoryIntegrationTest.php
declare(strict_types=1);

namespace Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use App\Repository\PaymentEventRepository;
use App\Config\DatabaseConfig;
use App\Support\AppLogger;
use PDO;

final class PaymentEventRepositoryIntegrationTest extends TestCase
{
    private PDO $pdo;
    private PaymentEventRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize logger
        try {
            AppLogger::get();
        } catch (\RuntimeException $e) {
            AppLogger::set(new \Psr\Log\NullLogger());
        }
        
        // Create in-memory SQLite database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create table
        $this->pdo->exec("
            CREATE TABLE st_payment_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                stripe_session_id TEXT NOT NULL,
                stripe_event_id TEXT,
                event_source TEXT NOT NULL,
                event_type TEXT NOT NULL,
                event_status TEXT,
                http_method TEXT,
                request_ip TEXT,
                user_agent TEXT,
                payload_json TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Mock DatabaseConfig that returns our test PDO
        $dbConfig = $this->createMock(DatabaseConfig::class);
        $dbConfig->method('isEnabled')->willReturn(true);
        $dbConfig->method('getPdoDsn')->willReturn('sqlite::memory:');
        $dbConfig->method('getUser')->willReturn('');
        $dbConfig->method('getPassword')->willReturn('');
        $dbConfig->method('getSafeConfig')->willReturn([]);
        
        // Create repository with mocked PDO via reflection
        $this->repo = new PaymentEventRepository($dbConfig);
        $reflection = new \ReflectionClass($this->repo);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->repo, $this->pdo);
        
        $enabledProperty = $reflection->getProperty('enabled');
        $enabledProperty->setAccessible(true);
        $enabledProperty->setValue($this->repo, true);
    }

    public function test_it_records_payment_event(): void
    {
        $event = [
            'stripe_session_id' => 'cs_test_123',
            'stripe_event_id' => 'evt_test_456',
            'event_source' => 'webhook',
            'event_type' => 'checkout.session.completed',
            'event_status' => 'paid',
            'http_method' => 'POST',
            'request_ip' => '192.168.1.1',
            'user_agent' => 'Stripe/1.0',
            'payload' => ['id' => 'cs_test_123', 'status' => 'paid'],
        ];
        
        $this->repo->recordEvent($event);
        
        // Verify it was saved
        $stmt = $this->pdo->query("SELECT * FROM st_payment_events WHERE stripe_session_id = 'cs_test_123'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($row);
        $this->assertEquals('cs_test_123', $row['stripe_session_id']);
        $this->assertEquals('webhook', $row['event_source']);
        $this->assertEquals('paid', $row['event_status']);
    }
}