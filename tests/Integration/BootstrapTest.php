<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BootstrapTest extends TestCase
{
    public function testApplicationRootIsDefined(): void
    {
        $this->assertTrue(
            defined('APP_ROOT'),
            'APP_ROOT konstans nincs definiálva'
        );

        $this->assertDirectoryExists(
            APP_ROOT,
            'APP_ROOT nem létező könyvtárra mutat'
        );
    }

    public function testCustomizationDirectoryExists(): void
    {
        $customizeDir = APP_ROOT . '/customize';

        $this->assertDirectoryExists(
            $customizeDir,
            'A customize könyvtár hiányzik'
        );
    }
}
