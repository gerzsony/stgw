<?php

namespace App\Customize;

use App\Support\AppLogger;

final class CustomizeLoader
{
    /** @var string APP_ROOT */
    
    /*
    public static function load(string $baseDir, array $data): array
    {
        if (empty($data['oid']) || $data['oid'] <= 0) {
            AppLogger::get()->debug('CustomizeLoader: no oid, skipping');
            return $data['cart'] ?? [];
        }

        $file = '/customize/' . rtrim($baseDir, '/') . '/index.php';

        if (is_file($file)) {
            include_once $file;

            if (function_exists('onPaymentIndex')) {
                $cart = onPaymentIndex($data);

                AppLogger::get()->debug('Customize file loaded and function exists', [
                    'file' => $file,
                ]);

                return $cart ?? ($data['cart'] ?? []);
            }

            AppLogger::get()->warning('Customize file loaded but function missing', [
                'expected' => 'onPaymentIndex',
                'file' => $file,
            ]);
        }

        return $data['cart'] ?? [];
    }
    */
    /**
     * @param array $data
     * @return array Modified data
     */
    public static function load(string $siteName, array $data): array
    {
        if (empty($data['oid'])) {
            return $data;
        }

        $file = APP_ROOT . '/customize/' . $siteName . '/index.php';

        if (!is_file($file)) {
            return $data;
        }

        require_once $file;

        if (!function_exists('onPaymentIndex')) {
            return $data;
        }

        $result = onPaymentIndex($data);

        return is_array($result) ? $result : $data;
    }
	
    public static function loadResult(string $customDir, array $sessionData): void
    {
        $file = '/customize/'. rtrim($customDir, '/') . '/result.php';
        if (is_file($file)) {
            include_once $file;
            if (function_exists('saveStripeSuccessfulPayment')) {
                $result = saveStripeSuccessfulPayment($sessionData);
                AppLogger::get()->debug('Custom result save executed', ['result' => $result]);
            }
        }
    }

    public static function loadWebhook(string $customizationDir, array $sessionData): void
    {
        $logger = AppLogger::get();

        $file = '/customize/'. rtrim($customizationDir, '/\\') . '/webhook.php';

        if (!is_dir($customizationDir)) {
            $logger->debug('CustomizeLoader: customization directory not found', [
                'dir' => $customizationDir,
            ]);
            return;
        }

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
             *
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