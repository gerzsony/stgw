<?php

namespace App\Repository;

use App\Support\AppLogger;
use App\Config\DatabaseConfig;
use PDO;
use Throwable;

class PaymentEventRepository
{
    protected ?PDO $pdo = null;
    protected bool $enabled = false;

    public function __construct(DatabaseConfig $dbConfig)
    {
        if (!$dbConfig->isEnabled()) {
            AppLogger::get()->info('PaymentEventRepository: DB disabled by config');
            return;
        }

        try {
            $this->pdo = new PDO(
             $dbConfig->getPdoDsn(),
        $dbConfig->getUser(),
        $dbConfig->getPassword(),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            );

            $this->enabled = true;

            AppLogger::get()->info('PaymentEventRepository: PDO connection established', [
                'db' => $dbConfig->getSafeConfig(),
            ]);

        } catch (Throwable $e) {
            AppLogger::get()->error('PaymentEventRepository: PDO connection failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function recordEvent(array $event): void
    {
        // Mindig loggoljuk
        AppLogger::get()->debug('PaymentEventRepository: recordEvent called', [
            'event' => $event,
        ]);

        if (!$this->enabled || !$this->pdo) {
            AppLogger::get()->debug('PaymentEventRepository: skipped (DB disabled)');
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO st_payment_events (
                    stripe_session_id,
                    stripe_event_id,
                    event_source,
                    event_type,
                    event_status,
                    http_method,
                    request_ip,
                    user_agent,
                    payload_json
                ) VALUES (
                    :stripe_session_id,
                    :stripe_event_id,
                    :event_source,
                    :event_type,
                    :event_status,
                    :http_method,
                    :request_ip,
                    :user_agent,
                    :payload_json
                )
            ");

            $stmt->execute([
                ':stripe_session_id' => $event['stripe_session_id'],
                ':stripe_event_id'   => $event['stripe_event_id'] ?? null,
                ':event_source'      => $event['event_source'],
                ':event_type'        => $event['event_type'],
                ':event_status'      => $event['event_status'] ?? null,
                ':http_method'       => $event['http_method'] ?? null,
                ':request_ip'        => $event['request_ip'] ?? null,
                ':user_agent'        => $event['user_agent'] ?? null,
                ':payload_json'      => json_encode($event['payload'], JSON_THROW_ON_ERROR),
            ]);

        } catch (Throwable $e) {
            AppLogger::get()->error('PaymentEventRepository: insert failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
