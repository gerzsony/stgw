<?php

// -------------------------------------------------
// Load important things
// -------------------------------------------------

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__ . '/..'));
}
require_once APP_ROOT . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\StripeConfig; // as StripeConfig;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Support\AppLogger;
use App\Config\AppConfig;


// -------------------------------------------------
// ENV
// -------------------------------------------------

$root = realpath(__DIR__ . '/..');
$dotenv = Dotenv::createImmutable($root, '.env');
$dotenv->safeLoad();

$env = $_ENV['APP_ENV'] ?: 'dev';
$dotenvEnv = Dotenv::createImmutable($root, ".env.$env");
$dotenvEnv->safeLoad();

// -------------------------------------------------
// Normal config
// -------------------------------------------------

$config = AppConfig::getInstance();
$backUrl = $config->get('back_url');
$paysiteTitle = $config->get('paysite_title');


// -------------------------------------------------
// Extended debug only in Dev mode
// -------------------------------------------------

if (($config->get('debug') > 1) &&  ($config->get('env') === 'dev')) {
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
}


// -------------------------------------------------
// Session 
// -------------------------------------------------

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------
// Stripe
// -------------------------------------------------



\Stripe\Stripe::setApiKey(
    StripeConfig::secretKey()
);

//\Stripe\Stripe::setApiKey(ST_SK_KEY);

// -------------------------------------------------
// LOGGER INIT (PSR-3 / Monolog)
// -------------------------------------------------


$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

//die($logDir); // TODO

$monolog = new Logger('app');

$handler = new StreamHandler(
    $logDir . '/app.log',
    $config->get('debug')  ? Logger::DEBUG : Logger::WARNING
);

$handler->setFormatter(new LineFormatter(
    "[%datetime%] %level_name%: %message% %context%\n",
    'Y.m-d H:i'
));

$monolog->pushHandler($handler);

AppLogger::set($monolog);
AppLogger::get()->debug('---- Application starting ----');
