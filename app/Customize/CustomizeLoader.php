<?php
namespace App\Customize;

use App\Support\AppLogger;

final class CustomizeLoader
{
    /**
     * Load customization for payment
     * 
     * @param string $siteName Site directory name or full path
     * @param array $data Payment data with 'oid' and 'cart'
     * @return array Modified data
     */
    public static function load(string $siteName, array $data): array
    {
        if (empty($data['oid'])) {
            return $data;
        }
        
        // Support both full paths and site names
        $baseDir = str_starts_with($siteName, '/') 
            ? $siteName 
            : (defined('APP_ROOT') ? APP_ROOT . '/customize/' . $siteName : $siteName);
        
        // Try index.php first (new style)
        $file = rtrim($baseDir, '/') . '/index.php';
        if (is_file($file)) {
            require_once $file;
            if (function_exists('onPaymentIndex')) {
                $result = onPaymentIndex($data);
                return is_array($result) ? $result : $data;
            }
        }
        
        // Fallback to cartloader.php (old style) for backwards compatibility
        $cartloaderFile = rtrim($baseDir, '/') . '/cartloader.php';
        if (is_file($cartloaderFile)) {
            require_once $cartloaderFile;
            // cartloader.php may modify $data or set globals
            return $data;
        }
        
        return $data;
    }

    /**
     * Load result customization
     */
    public static function loadResult(string $customDir, array $sessionData): void
    {
        $baseDir = defined('APP_ROOT') ? APP_ROOT . '/customize/' : '/customize/';
        $file = $baseDir . rtrim($customDir, '/') . '/result.php';
        
        if (is_file($file)) {
            include_once $file;
            if (function_exists('saveStripeSuccessfulPayment')) {
                $result = saveStripeSuccessfulPayment($sessionData);
                AppLogger::get()->debug('Custom result save executed', ['result' => $result]);
            }
        }
    }

    /**
     * Load webhook customization
     */
    public static function loadWebhook(string $customizationDir, array $sessionData): void
    {
        $logger = AppLogger::get();
        
        // Check if directory exists
        if (!is_dir($customizationDir)) {
            $logger->debug('CustomizeLoader: customization directory not found', [
                'dir' => $customizationDir,
            ]);
            return;
        }
        
        // Build file path
        $file = rtrim($customizationDir, '/\\') . '/webhook.php';
        
        if (!is_file($file)) {
            $logger->debug('CustomizeLoader: webhook customization not present', [
                'file' => $file,
            ]);
            return;
        }
        
        try {
            require_once $file;
            if (!function_exists('onStripeWebhook')) {
                $logger->warning('CustomizeLoader: webhook file loaded but function missing', [
                    'expected' => 'onStripeWebhook',
                    'file' => $file,
                ]);
                return;
            }
            
            // @phpstan-ignore-next-line
            /**
             * @noinspection PhpUndefinedFunctionInspection
             * @function array onStripeWebhook(array $sessionData)
             */
            $result = onStripeWebhook($sessionData);
            $logger->debug('CustomizeLoader: webhook customization executed', [
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            $logger->error('CustomizeLoader: webhook customization failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}