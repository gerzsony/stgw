<?php

declare(strict_types=1);

use App\Support\AppLogger;

require __DIR__ . '/../vendor/autoload.php';

// APP_ROOT definiálása teszthez
if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__ . '/..'));
}

// Teszt környezet változó
$_ENV['APP_ENV'] = 'test';
$_ENV['DB_USER'] = 'test_user';
$_ENV['DB_PASSWORD'] = 'test_pass';
$_ENV['DB_NAME'] = 'test_db';
$_ENV['DB_HOST'] = '127.0.0.1';

$logDir = APP_ROOT . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Create a simple NullLogger for tests if Monolog is not available
if (class_exists(\Monolog\Logger::class)) {
    $logger = new \Monolog\Logger('test');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler($logDir . '/test.log', \Monolog\Logger::DEBUG));
} else {
    // Fallback to Psr\Log\NullLogger if Monolog is not installed
    $logger = new \Psr\Log\NullLogger();
}

AppLogger::set($logger);