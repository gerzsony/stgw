<?php
declare(strict_types=1);

namespace Tests\Unit\Customize;

use App\Customize\CustomizeLoader;
use PHPUnit\Framework\TestCase;

final class CustomizeLoaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/customize_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpDir);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }

        rmdir($dir);
    }

    /** @test */
    public function load_does_nothing_when_oid_is_missing(): void
    {
        CustomizeLoader::load($this->tmpDir, []);

        $this->assertTrue(true); // no exception = pass
    }

    /** @test */
    public function load_does_nothing_when_file_does_not_exist(): void
    {
        CustomizeLoader::load($this->tmpDir, ['oid' => 1]);

        $this->assertTrue(true);
    }

    /** @test */
    public function load_includes_cartloader_when_present(): void
    {
        $file = $this->tmpDir . '/cartloader.php';

        file_put_contents(
            $file,
            '<?php $GLOBALS["__cartloader_called"] = true;'
        );

        CustomizeLoader::load($this->tmpDir, ['oid' => 1]);

        $this->assertTrue($GLOBALS['__cartloader_called'] ?? false);
    }

    /** @test */
    public function loadWebhook_does_nothing_if_directory_missing(): void
    {
        CustomizeLoader::loadWebhook(
            $this->tmpDir . '/missing',
            []
        );

        $this->assertTrue(true);
    }

    /** @test */
    public function loadWebhook_does_nothing_if_webhook_file_missing(): void
    {
        CustomizeLoader::loadWebhook($this->tmpDir, []);

        $this->assertTrue(true);
    }

    /** @test */
    public function loadWebhook_executes_onStripeWebhook_function(): void
    {
        $file = $this->tmpDir . '/webhook.php';

        file_put_contents(
            $file,
            <<<PHP
            <?php
            function onStripeWebhook(array \$data): array {
                \$GLOBALS['__webhook_called_with'] = \$data;
                return ['ok' => true];
            }
            PHP
        );

        $payload = ['session_id' => 'cs_test_123'];

        CustomizeLoader::loadWebhook($this->tmpDir, $payload);

        $this->assertSame(
            $payload,
            $GLOBALS['__webhook_called_with'] ?? null
        );
    }
	
	public function test_it_returns_modified_data_from_customization()
	{
		$data = [
			'oid' => 123,
			'cart' => [['price' => 100]]
		];

		$result = CustomizeLoader::load('testsite', $data);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('cart', $result);
	}
	
}
