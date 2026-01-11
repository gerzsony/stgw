<?php

namespace App\Config;
use RuntimeException;

final class AppConfig
{
    private static ?self $instance = null;
    private array $data;

    private function __construct()
    {
        /*
        $this->data = [
            'env' => getenv('APP_ENV') ?: 'dev',
            'debug' => (int) getenv('APP_DEBUG') ?: 0,
            'result_url' => getenv('RESULT_URL') ?: '',
            'customization_dir' => getenv('CUSTOMIZATION_DIR') ?: __DIR__ . '/../../customize/',
            'back_url' => getenv('BACK_URL') ?: '',
            'paysite_title' => getenv('PAYSITE_TITLE') ?: '',
            'wp_configpath' => getenv('WP_CONFIGPATH') ?: '/../wp-config.php',
        ];
        */


        $this->data = [
            'env' => $_ENV['APP_ENV'] ?? 'dev',

            'debug' => isset($_ENV['APP_DEBUG'])
                ? (int) $_ENV['APP_DEBUG']
                : 0,

            'result_url' => $_ENV['RESULT_URL'] ?? '',

            'customization_dir' => $_ENV['CUSTOMIZATION_DIR']
                ?? realpath(__DIR__ . '/../../customize'),

            'back_url' => $_ENV['BACK_URL'] ?? '',

            'paysite_title' => $_ENV['PAYSITE_TITLE'] ?? '',

            'wp_configpath' => $_ENV['WP_CONFIGPATH']
                ?? realpath(__DIR__ . '/../../wp-config.php'),
        ];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}