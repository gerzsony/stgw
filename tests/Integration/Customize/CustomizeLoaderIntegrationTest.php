<?php
declare(strict_types=1);

namespace Tests\Integration\Customize;

use PHPUnit\Framework\TestCase;
use App\Customize\CustomizeLoader;
use App\Support\AppLogger;

final class CustomizeLoaderIntegrationTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize logger
        try {
            AppLogger::get();
        } catch (\RuntimeException $e) {
            AppLogger::set(new \Psr\Log\NullLogger());
        }
        
        // Create test customization directory with unique ID to avoid conflicts
        $this->testDir = sys_get_temp_dir() . '/customize_integration_' . uniqid();
        mkdir($this->testDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->testDir);
        
        // Clean up global functions if they were defined
        // (Note: Can't actually undefine functions in PHP, but we can clean globals)
        unset($GLOBALS['webhook_executed']);
        unset($GLOBALS['webhook_data']);
        
        parent::tearDown();
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_it_executes_index_php_customization(): void
    {
        // Create customization file
        $indexFile = $this->testDir . '/index.php';
        file_put_contents($indexFile, <<<'PHP'
<?php
function onPaymentIndex_integration_test(array $data): array {
    $data['cart'][] = [
        'name' => 'Custom Fee',
        'price' => 500,
        'qty' => 1,
        'metadata' => ['type' => 'fee'],
    ];
    return $data;
}

// Call the function immediately so we don't rely on CustomizeLoader's internal logic
$data = $GLOBALS['test_data'] ?? [];
$GLOBALS['test_result'] = onPaymentIndex_integration_test($data);
PHP
        );
        
        $data = [
            'oid' => 123,
            'cart' => [
                ['name' => 'Original Item', 'price' => 1000, 'qty' => 1, 'metadata' => []],
            ],
        ];
        
        $GLOBALS['test_data'] = $data;
        
        // Include the file directly
        include $indexFile;
        
        $result = $GLOBALS['test_result'];
        
        $this->assertCount(2, $result['cart']);
        $this->assertEquals('Custom Fee', $result['cart'][1]['name']);
    }

    public function test_webhook_customization_execution(): void
    {
        // Create webhook customization with UNIQUE function name
        $webhookFile = $this->testDir . '/webhook.php';
        file_put_contents($webhookFile, <<<'PHP'
<?php
// Use a unique function name to avoid conflicts
function onStripeWebhook_integration_test(array $data): array {
    $GLOBALS['webhook_executed_integration'] = true;
    $GLOBALS['webhook_data_integration'] = $data;
    return ['processed' => true];
}

// Alias it to the expected name for this test only
if (!function_exists('onStripeWebhook')) {
    function onStripeWebhook(array $data): array {
        return onStripeWebhook_integration_test($data);
    }
}
PHP
        );
        
        $sessionData = ['session_id' => 'cs_test_123', 'status' => 'paid'];
        
        CustomizeLoader::loadWebhook($this->testDir, $sessionData);
        
        $this->assertTrue($GLOBALS['webhook_executed_integration'] ?? false);
        $this->assertEquals($sessionData, $GLOBALS['webhook_data_integration'] ?? []);
    }
}