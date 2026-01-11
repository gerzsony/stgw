<?php
declare(strict_types=1);

namespace App\Customize;

final class CustomizeLoader
{
    /**
     * @param string $baseDir
     * @param array<string, mixed> $data
     */
    public static function load(string $baseDir, array $data): void {}

    /**
     * @param string $customDir
     * @param array<string, mixed> $sessionData
     */
    public static function loadResult(string $customDir, array $sessionData): void {}

    /**
     * @param string $customizationDir
     * @param array<string, mixed> $sessionData
     */
    public static function loadWebhook(string $customizationDir, array $sessionData): void {}
}
