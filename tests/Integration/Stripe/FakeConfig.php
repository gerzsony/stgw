<?php
declare(strict_types=1);

namespace Tests\Integration\Stripe;

use App\Config\AppConfig;

class FakeConfig extends AppConfig
{
    private array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data + [
            'customization_dir' => sys_get_temp_dir() . '/test_customize',
        ];
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}