<?php

namespace App\Config;

use App\Support\AppLogger;
use RuntimeException;

class DatabaseConfig
{
    protected bool $enabled = false;
    protected string $source = 'none'; // wp | env | none

    protected array $config = [
        'host'     => null,
        'dbname'   => null,
        'user'     => null,
        'password' => null,
        'charset'  => 'utf8mb4',
    ];

    public static function load(): self
    {
        $instance = new self();
        $logger = AppLogger::get();

        $logger->debug('DatabaseConfig: initialization started');

        // Try WordPress config
        $wpConfigPath = __DIR__ . "/../.." . $_ENV['WP_CONFIGPATH'] ?? null;

        if ($wpConfigPath) {
            $logger->debug('DatabaseConfig: WP_CONFIGPATH detected', [
                'path' => $wpConfigPath,
            ]);

            if (is_readable($wpConfigPath)) {
                try {
                    require_once $wpConfigPath;

                    $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
                    foreach ($required as $const) {
                        if (!defined($const)) {
                            throw new RuntimeException("Missing WP constant: {$const}");
                        }
                    }

                    /** @var string $DB_HOST */
                    /** @var string $DB_NAME */
                    /** @var string $DB_USER */
                    /** @var string $DB_PASSWORD */
                    /** @var string $DB_CHARSET */
                    // @phpstan-ignore-next-line                    
                    $instance->config = [
                        'host'     => defined('DB_HOST') ? DB_HOST : null,
                        'dbname'   => defined('DB_NAME') ? DB_NAME : null,
                        'user'     => defined('DB_USER') ? DB_USER : null,
                        'password' => defined('DB_PASSWORD') ? DB_PASSWORD : null,
                        'charset'  => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4',
                    ];

                    $instance->enabled = true;
                    $instance->source = 'wp';

                    $logger->info('DatabaseConfig: using WordPress database configuration', $instance->config);

                    return $instance;

                } catch (\Throwable $e) {
                    $logger->warning('DatabaseConfig: failed to load WordPress DB config', [
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $logger->warning('DatabaseConfig: WP_CONFIGPATH not readable', [
                    'path' => $wpConfigPath,
                ]);
            }
        } else {
            $logger->debug('DatabaseConfig: WP_CONFIGPATH not set');
        }

        // Try ENV based DB config
        $envRequired = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
        $envMissing = [];

        foreach ($envRequired as $key) {
            if (empty($_ENV[$key])) {
                $envMissing[] = $key;
            }
        }

        if (empty($envMissing)) {
            $instance->config = [
                'host'     => $_ENV['DB_HOST'],
                'dbname'   => $_ENV['DB_NAME'],
                'user'     => $_ENV['DB_USER'],
                'password' => $_ENV['DB_PASSWORD'],
                'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            ];

            $instance->enabled = true;
            $instance->source = 'env';

            $logger->info('DatabaseConfig: using ENV database configuration', [
                'host'    => $instance->config['host'],
                'dbname'  => $instance->config['dbname'],
                'charset' => $instance->config['charset'],
            ]);

            return $instance;
        }

        // using DB is switched off
        $logger->warning('DatabaseConfig: database disabled', [
            'reason' => 'No valid WP or ENV database configuration found',
            'missing_env_vars' => $envMissing,
        ]);

        return $instance;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getPdoDsn(): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        return sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['dbname'],
            $this->config['charset']
        );
    }

    public function getUser(): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        return $this->config['user'];
    }

        public function getPassword(): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        return $this->config['password'];
    }
   

    /**
     * Only for Debug reasons, not for connection (!)
     */
    public function getSafeConfig(): array
    {
        if (!$this->enabled) {
            return [
                'enabled' => false,
                'source'  => $this->source,
            ];
        }

        return [
            'enabled' => true,
            'source'  => $this->source,
            'host'    => $this->config['host'],
            'dbname'  => $this->config['dbname'],
            'charset' => $this->config['charset'],
        ];
    }
}
