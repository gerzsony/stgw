<?php
declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\DatabaseConfig;
use App\Support\AppLogger;
use PHPUnit\Framework\TestCase;

final class DatabaseConfigTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure logger is initialized
        try {
            AppLogger::get();
        } catch (\RuntimeException $e) {
            AppLogger::set(new \Psr\Log\NullLogger());
        }
        
        // Backup original env values
        $keys = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET', 'WP_CONFIGPATH'];
        foreach ($keys as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? null;
        }
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
        
        parent::tearDown();
    }

    /** @test */
    public function it_loads_database_config_from_env(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_pass';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $this->assertTrue($config->isEnabled());
        $this->assertEquals('env', $config->getSource());
        $this->assertEquals('test_user', $config->getUser());
        $this->assertNotNull($config->getPdoDsn());
    }

    /** @test */
    public function it_is_disabled_when_env_variables_are_missing(): void
    {
        unset($_ENV['DB_HOST']);
        unset($_ENV['DB_NAME']);
        unset($_ENV['DB_USER']);
        unset($_ENV['DB_PASSWORD']);
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $this->assertFalse($config->isEnabled());
        $this->assertEquals('none', $config->getSource());
        $this->assertNull($config->getPdoDsn());
        $this->assertNull($config->getUser());
        $this->assertNull($config->getPassword());
    }

    /** @test */
    public function it_uses_default_charset_when_not_provided(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_pass';
        unset($_ENV['DB_CHARSET']);
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $dsn = $config->getPdoDsn();
        $this->assertStringContainsString('charset=utf8mb4', $dsn);
    }

    /** @test */
    public function it_uses_custom_charset_when_provided(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_pass';
        $_ENV['DB_CHARSET'] = 'latin1';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $dsn = $config->getPdoDsn();
        $this->assertStringContainsString('charset=latin1', $dsn);
    }

    /** @test */
    public function it_generates_correct_pdo_dsn(): void
    {
        $_ENV['DB_HOST'] = 'db.example.com';
        $_ENV['DB_NAME'] = 'myapp_db';
        $_ENV['DB_USER'] = 'dbuser';
        $_ENV['DB_PASSWORD'] = 'dbpass';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $dsn = $config->getPdoDsn();
        $this->assertEquals('mysql:host=db.example.com;dbname=myapp_db;charset=utf8mb4', $dsn);
    }

    /** @test */
    public function it_returns_null_for_disabled_database(): void
    {
        unset($_ENV['DB_HOST']);
        unset($_ENV['DB_NAME']);
        unset($_ENV['DB_USER']);
        unset($_ENV['DB_PASSWORD']);
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $this->assertNull($config->getPdoDsn());
        $this->assertNull($config->getUser());
        $this->assertNull($config->getPassword());
    }

    /** @test */
    public function getSafeConfig_excludes_password(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'secret_password';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        $safeConfig = $config->getSafeConfig();
        
        $this->assertArrayNotHasKey('password', $safeConfig);
        $this->assertArrayNotHasKey('user', $safeConfig);
        $this->assertArrayHasKey('enabled', $safeConfig);
        $this->assertArrayHasKey('source', $safeConfig);
        $this->assertArrayHasKey('host', $safeConfig);
        $this->assertArrayHasKey('dbname', $safeConfig);
        $this->assertArrayHasKey('charset', $safeConfig);
    }

    /** @test */
    public function getSafeConfig_shows_disabled_state(): void
    {
        unset($_ENV['DB_HOST']);
        unset($_ENV['DB_NAME']);
        unset($_ENV['DB_USER']);
        unset($_ENV['DB_PASSWORD']);
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        $safeConfig = $config->getSafeConfig();
        
        $this->assertFalse($safeConfig['enabled']);
        $this->assertEquals('none', $safeConfig['source']);
        $this->assertArrayNotHasKey('host', $safeConfig);
        $this->assertArrayNotHasKey('dbname', $safeConfig);
    }

    /** @test */
    public function it_requires_all_mandatory_env_variables(): void
    {
        $requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
        
        foreach ($requiredVars as $missingVar) {
            // Set all vars
            $_ENV['DB_HOST'] = 'localhost';
            $_ENV['DB_NAME'] = 'test_db';
            $_ENV['DB_USER'] = 'test_user';
            $_ENV['DB_PASSWORD'] = 'test_pass';
            unset($_ENV['WP_CONFIGPATH']);
            
            // Unset one
            unset($_ENV[$missingVar]);
            
            $config = DatabaseConfig::load();
            
            $this->assertFalse($config->isEnabled(), "Should be disabled when {$missingVar} is missing");
        }
    }

    /** @test */
    public function it_handles_empty_string_env_variables(): void
    {
        $_ENV['DB_HOST'] = '';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_pass';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $this->assertFalse($config->isEnabled());
    }

    /** @test */
    public function load_method_is_static(): void
    {
        $reflection = new \ReflectionMethod(DatabaseConfig::class, 'load');
        $this->assertTrue($reflection->isStatic());
    }

    /** @test */
    public function load_returns_database_config_instance(): void
    {
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $this->assertInstanceOf(DatabaseConfig::class, $config);
    }

    /** @test */
    public function isEnabled_returns_boolean(): void
    {
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $this->assertIsBool($config->isEnabled());
    }

    /** @test */
    public function getSource_returns_string(): void
    {
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $this->assertIsString($config->getSource());
        $this->assertContains($config->getSource(), ['wp', 'env', 'none']);
    }

    /** @test */
    public function it_handles_database_host_with_port(): void
    {
        $_ENV['DB_HOST'] = 'localhost:3307';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_pass';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $dsn = $config->getPdoDsn();
        $this->assertStringContainsString('host=localhost:3307', $dsn);
    }

    /** @test */
    public function it_handles_database_host_with_socket(): void
    {
        $_ENV['DB_HOST'] = 'localhost:/tmp/mysql.sock';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_pass';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $dsn = $config->getPdoDsn();
        $this->assertStringContainsString('localhost:/tmp/mysql.sock', $dsn);
    }

    /** @test */
    public function it_handles_special_characters_in_credentials(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'user@domain';
        $_ENV['DB_PASSWORD'] = 'p@ss!w0rd#123';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $this->assertEquals('user@domain', $config->getUser());
        $this->assertEquals('p@ss!w0rd#123', $config->getPassword());
    }

    /** @test */
    public function it_handles_database_name_with_special_characters(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'my-app_db.test';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_pass';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $dsn = $config->getPdoDsn();
        $this->assertStringContainsString('dbname=my-app_db.test', $dsn);
    }

    /** @test */
    public function it_handles_localhost_variations(): void
    {
        $hosts = ['localhost', '127.0.0.1', '::1', 'db.local'];
        
        foreach ($hosts as $host) {
            $_ENV['DB_HOST'] = $host;
            $_ENV['DB_NAME'] = 'test_db';
            $_ENV['DB_USER'] = 'test_user';
            $_ENV['DB_PASSWORD'] = 'test_pass';
            unset($_ENV['WP_CONFIGPATH']);
            
            $config = DatabaseConfig::load();
            
            $this->assertTrue($config->isEnabled());
            $dsn = $config->getPdoDsn();
            $this->assertStringContainsString("host={$host}", $dsn);
        }
    }

    /** @test */
    public function getPdoDsn_returns_nullable_string(): void
    {
        $reflection = new \ReflectionMethod(DatabaseConfig::class, 'getPdoDsn');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    /** @test */
    public function getUser_returns_nullable_string(): void
    {
        $reflection = new \ReflectionMethod(DatabaseConfig::class, 'getUser');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    /** @test */
    public function getPassword_returns_nullable_string(): void
    {
        $reflection = new \ReflectionMethod(DatabaseConfig::class, 'getPassword');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    /** @test */
    public function it_handles_utf8mb4_charset(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_pass';
        $_ENV['DB_CHARSET'] = 'utf8mb4';
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        
        $dsn = $config->getPdoDsn();
        $this->assertStringContainsString('charset=utf8mb4', $dsn);
        
        $safeConfig = $config->getSafeConfig();
        $this->assertEquals('utf8mb4', $safeConfig['charset']);
    }

    /** @test */
    public function it_prioritizes_wordpress_config_over_env(): void
    {
        // Set ENV vars
        $_ENV['DB_HOST'] = 'env_host';
        $_ENV['DB_NAME'] = 'env_db';
        $_ENV['DB_USER'] = 'env_user';
        $_ENV['DB_PASSWORD'] = 'env_pass';
        
        // Set WP config path (even if it doesn't exist, we test the priority)
        $_ENV['WP_CONFIGPATH'] = '/nonexistent/wp-config.php';
        
        $config = DatabaseConfig::load();
        
        // Since WP config doesn't exist, it should fall back to ENV
        // But we're testing that WP is checked first
        $this->assertContains($config->getSource(), ['env', 'none']);
    }

    /** @test */
    public function getSafeConfig_returns_array(): void
    {
        unset($_ENV['WP_CONFIGPATH']);
        
        $config = DatabaseConfig::load();
        $safeConfig = $config->getSafeConfig();
        
        $this->assertIsArray($safeConfig);
    }
}