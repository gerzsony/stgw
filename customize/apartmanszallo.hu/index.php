<?php

declare(strict_types=1);

use App\Support\AppLogger;
use App\Config\DatabaseConfig;

/**
 * @param array $data Incoming payment request data
 * @return array Modified data
 */
function onPaymentIndex(array $data): array
{
    $logger = AppLogger::get();

    /* -------------------------------------------------
     * OID check
     * ------------------------------------------------- */
    if (empty($data['oid']) || !ctype_digit((string)$data['oid'])) {
        $logger->debug('Customization[index]: no valid oid provided');
        return $data; // silent skip
    }

    $bookingId = (int)$data['oid'];
    $logger->info('Customization[index]: loading booking', [
        'oid' => $bookingId
    ]);

    /* -------------------------------------------------
     * DB connection (universal)
     * ------------------------------------------------- */
    $dbConfig = DatabaseConfig::load();

    if (!$dbConfig->isEnabled()) {
        $logger->warning('Customization[index]: DB not available, skipping booking load');
        return $data;
    }

    try {
        $pdo = new PDO(
            $dbConfig->getPdoDsn(),
            $dbConfig->getUser(),
            $dbConfig->getPassword(),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (\Throwable $e) {
        $logger->error('Customization[index]: DB connection failed', [
            'error' => $e->getMessage()
        ]);
        return $data;
    }

    /* -------------------------------------------------
     * Load booking
     * ------------------------------------------------- */
    $stmt = $pdo->prepare("
        SELECT
            id,
            booker_name,
            booker_email,
            booker_huf,
            booker_deposit_huf,
            booker_persons,
            booker_days
        FROM bookings
        WHERE id = :id

        LIMIT 1
    ");  //          AND valid_record = 'yes'

    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $logger->warning('Customization[index]: booking not found or invalid', [
            'oid' => $bookingId
        ]);
        return $data;
    }

    /* -------------------------------------------------
     * Build cart + metadata
     * ------------------------------------------------- */
    $pricePerDay = ($booking['booker_days'] > 0)
        ? (int)($booking['booker_huf'] / $booking['booker_days'])
        : 0;

    $data['customer_email'] = $booking['booker_email'];

    $data['cart'] = [
        [
            'name'  => 'Szállásfoglalás – ' . $booking['booker_name'],
            'price' => $pricePerDay,
            'qty'   => 1,
            'metadata' => [
                'booking_id' => $booking['id'],
                'persons'    => $booking['booker_persons'],
                'days'       => $booking['booker_days'],
                'source'     => 'apartmanszallo',
            ],
        ],
    ];

    // opcionális globális metadata Stripe-hoz
    /*
    $data['metadata'] = array_merge(
        $data['metadata'] ?? [],
        [
            'booking_id' => $booking['id'],
            'source'     => 'apartmanszallo',
        ]
    );
    */

    $logger->info('Customization[index]: cart populated', [
        'oid' => $bookingId,
        'cart' => $data['cart'],
    ]);

    return $data;
}


