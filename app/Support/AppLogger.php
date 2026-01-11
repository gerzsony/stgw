<?php

namespace App\Support;

use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Logger singleton class
 *
 *
 * use:
 *
 *   \App\Support\Logger::get()->debug('message', ['context' => $data]);
 */
final class AppLogger
{
    /**
     * init
     */
    private static ?LoggerInterface $instance = null;

    /**
     * set
     */
    public static function set(LoggerInterface $logger): void
    {
        self::$instance = $logger;
    }

    /**
     *
     * @throws RuntimeException if not defined
     */
    public static function get(): LoggerInterface
    {
        if (!self::$instance) {
            throw new RuntimeException('Logger not initialized. Bootstrap must set the logger first.');
        }

        return self::$instance;
    }
}
