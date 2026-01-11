<?php
declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\AppConfig;
use PHPUnit\Framework\TestCase;

final class AppConfigTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Backup original env values
        $keys = ['APP_ENV', 'APP_DEBUG', 'RESULT_URL', 'CUSTOMIZATION_DIR', 'BACK_URL', 'PAYSITE_TITLE', 'WP_CONFIGPATH'];
        foreach ($keys as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? null;
        }
        
        // Reset singleton instance via reflection
        $reflection = new \ReflectionClass(AppConfig::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    protected function tearDown(): void
    {
        // Restore original env values
        foreach ($this->originalEnv as $key => $value) {
            if ($value !== null) {
                $_ENV[$key] = $value;
            } else {
                unset($_ENV[$key]);
            }
        }
        
        // Reset singleton instance
        $reflection = new \ReflectionClass(AppConfig::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        
        parent::tearDown();
    }

    /** @test */
    public function it_returns_singleton_instance(): void
    {
        $instance1 = AppConfig::getInstance();
        $instance2 = AppConfig::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    /** @test */
    public function it_uses_default_env_when_not_set(): void
    {
        unset($_ENV['APP_ENV']);
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('dev', $config->get('env'));
    }

    /** @test */
    public function it_reads_env_from_environment(): void
    {
        $_ENV['APP_ENV'] = 'production';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('production', $config->get('env'));
    }

    /** @test */
    public function it_uses_zero_as_default_debug_value(): void
    {
        unset($_ENV['APP_DEBUG']);
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals(0, $config->get('debug'));
    }

    /** @test */
    public function it_converts_debug_to_integer(): void
    {
        $_ENV['APP_DEBUG'] = '1';
        
        $config = AppConfig::getInstance();
        
        $this->assertIsInt($config->get('debug'));
        $this->assertEquals(1, $config->get('debug'));
    }

    /** @test */
    public function it_reads_result_url_from_environment(): void
    {
        $_ENV['RESULT_URL'] = 'https://example.com/result';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('https://example.com/result', $config->get('result_url'));
    }

    /** @test */
    public function it_uses_empty_string_as_default_for_result_url(): void
    {
        unset($_ENV['RESULT_URL']);
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('', $config->get('result_url'));
    }

    /** @test */
    public function it_reads_customization_dir_from_environment(): void
    {
        $testDir = sys_get_temp_dir() . '/test_customize';
        mkdir($testDir, 0777, true);
        
        $_ENV['CUSTOMIZATION_DIR'] = $testDir;
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals($testDir, $config->get('customization_dir'));
        
        rmdir($testDir);
    }

    /** @test */
    public function it_uses_default_customization_dir_when_not_set(): void
    {
        unset($_ENV['CUSTOMIZATION_DIR']);
        
        $config = AppConfig::getInstance();
        
        $customizationDir = $config->get('customization_dir');
        
        $this->assertIsString($customizationDir);
        $this->assertStringContainsString('customize', $customizationDir);
    }

    /** @test */
    public function it_reads_back_url_from_environment(): void
    {
        $_ENV['BACK_URL'] = 'https://example.com/back';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('https://example.com/back', $config->get('back_url'));
    }

    /** @test */
    public function it_uses_empty_string_as_default_for_back_url(): void
    {
        unset($_ENV['BACK_URL']);
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('', $config->get('back_url'));
    }

    /** @test */
    public function it_reads_paysite_title_from_environment(): void
    {
        $_ENV['PAYSITE_TITLE'] = 'My Payment Site';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('My Payment Site', $config->get('paysite_title'));
    }

    /** @test */
    public function it_uses_empty_string_as_default_for_paysite_title(): void
    {
        unset($_ENV['PAYSITE_TITLE']);
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('', $config->get('paysite_title'));
    }

    /** @test */
    public function it_reads_wp_configpath_from_environment(): void
    {
        $_ENV['WP_CONFIGPATH'] = '/custom/path/wp-config.php';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('/custom/path/wp-config.php', $config->get('wp_configpath'));
    }

    /** @test */
    public function it_uses_default_wp_configpath_when_not_set(): void
    {
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = AppConfig::getInstance();
        
        $wpConfigPath = $config->get('wp_configpath');
        
        $this->assertIsString($wpConfigPath);
        $this->assertStringContainsString('wp-config.php', $wpConfigPath);
    }

    /** @test */
    public function get_returns_default_value_for_missing_key(): void
    {
        $config = AppConfig::getInstance();
        
        $result = $config->get('nonexistent_key', 'default_value');
        
        $this->assertEquals('default_value', $result);
    }

    /** @test */
    public function get_returns_null_when_no_default_provided(): void
    {
        $config = AppConfig::getInstance();
        
        $result = $config->get('nonexistent_key');
        
        $this->assertNull($result);
    }

    /** @test */
    public function it_handles_all_config_keys(): void
    {
        $_ENV['APP_ENV'] = 'test';
        $_ENV['APP_DEBUG'] = '1';
        $_ENV['RESULT_URL'] = 'https://result.com';
        $_ENV['CUSTOMIZATION_DIR'] = '/custom';
        $_ENV['BACK_URL'] = 'https://back.com';
        $_ENV['PAYSITE_TITLE'] = 'Test Title';
        $_ENV['WP_CONFIGPATH'] = '/wp/config.php';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('test', $config->get('env'));
        $this->assertEquals(1, $config->get('debug'));
        $this->assertEquals('https://result.com', $config->get('result_url'));
        $this->assertEquals('/custom', $config->get('customization_dir'));
        $this->assertEquals('https://back.com', $config->get('back_url'));
        $this->assertEquals('Test Title', $config->get('paysite_title'));
        $this->assertEquals('/wp/config.php', $config->get('wp_configpath'));
    }

    /** @test */
    public function class_is_final(): void
    {
        $reflection = new \ReflectionClass(AppConfig::class);
        $this->assertTrue($reflection->isFinal());
    }

    /** @test */
    public function constructor_is_private(): void
    {
        $reflection = new \ReflectionClass(AppConfig::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertTrue($constructor->isPrivate());
    }

    /** @test */
    public function getInstance_is_static(): void
    {
        $reflection = new \ReflectionMethod(AppConfig::class, 'getInstance');
        $this->assertTrue($reflection->isStatic());
    }

    /** @test */
    public function getInstance_returns_self(): void
    {
        $reflection = new \ReflectionMethod(AppConfig::class, 'getInstance');
        $returnType = $reflection->getReturnType();
        
        $this->assertEquals('self', $returnType->getName());
    }

    /** @test */
    public function it_handles_debug_value_zero(): void
    {
        $_ENV['APP_DEBUG'] = '0';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals(0, $config->get('debug'));
        $this->assertIsInt($config->get('debug'));
    }

    /** @test */
    public function it_handles_debug_value_one(): void
    {
        $_ENV['APP_DEBUG'] = '1';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals(1, $config->get('debug'));
        $this->assertIsInt($config->get('debug'));
    }

    /** @test */
    public function it_handles_different_env_values(): void
    {
        $environments = ['dev', 'test', 'staging', 'production'];
        
        foreach ($environments as $env) {
            // Reset singleton
            $reflection = new \ReflectionClass(AppConfig::class);
            $instance = $reflection->getProperty('instance');
            $instance->setAccessible(true);
            $instance->setValue(null, null);
            
            $_ENV['APP_ENV'] = $env;
            $config = AppConfig::getInstance();
            
            $this->assertEquals($env, $config->get('env'));
        }
    }

    /** @test */
    public function it_handles_url_with_query_parameters(): void
    {
        $_ENV['RESULT_URL'] = 'https://example.com/result?session={CHECKOUT_SESSION_ID}';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('https://example.com/result?session={CHECKOUT_SESSION_ID}', $config->get('result_url'));
    }

    /** @test */
    public function it_handles_unicode_in_paysite_title(): void
    {
        $_ENV['PAYSITE_TITLE'] = 'Szálláshely fizetés';
        
        $config = AppConfig::getInstance();
        
        $this->assertEquals('Szálláshely fizetés', $config->get('paysite_title'));
    }

    /** @test */
    public function it_preserves_singleton_across_multiple_calls(): void
    {
        $_ENV['APP_ENV'] = 'initial';
        
        $config1 = AppConfig::getInstance();
        $this->assertEquals('initial', $config1->get('env'));
        
        // Change env (but singleton should keep original value)
        $_ENV['APP_ENV'] = 'changed';
        
        $config2 = AppConfig::getInstance();
        $this->assertSame($config1, $config2);
        $this->assertEquals('initial', $config2->get('env'), 'Singleton should preserve initial values');
    }

    /** @test */
    public function get_method_accepts_mixed_default_value(): void
    {
        $config = AppConfig::getInstance();
        
        // Test different default types
        $this->assertEquals('string', $config->get('nonexistent', 'string'));
        $this->assertEquals(123, $config->get('nonexistent', 123));
        $this->assertEquals(['array'], $config->get('nonexistent', ['array']));
        $this->assertTrue($config->get('nonexistent', true));
    }
}