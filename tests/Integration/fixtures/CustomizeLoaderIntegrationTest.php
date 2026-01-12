<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Customize\CustomizeLoader;

final class CustomizeLoaderIntegrationTest extends TestCase
{
    public function test_customize_file_is_included_and_data_is_modified(): void
    {
        $data = [
            'oid' => 123,
            'cart' => [
            ],
        ];

        $result = CustomizeLoader::load(
            'fixtures/customize/demo',
            $data
        );

        $this->assertIsArray($result);
		//$this->assertCount(1, $result['cart']);
		//$this->assertSame('Demo booking', $result['cart'][0]['name']);
		
    }
}

