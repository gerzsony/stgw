<?php
declare(strict_types=1);


//echo("cartloader start"); // TODO

/**
 * Pre:
 * - $data["oid"] exists and number
 * - ST_WP_CONFIGPATH is pointing to wp-config.php (wordpress config)
 */

/* -------------------------------------------------
 * OID check
 * ------------------------------------------------- */

if (empty($data['oid']) || !ctype_digit((string)$data['oid'])) {
    throw new RuntimeException('Link error (oid)');
}

$bookingId = (int)$data['oid'];

//var_dump($bookingId);  // TODO



/* -------------------------------------------------
 * DB Connection
 * ------------------------------------------------- */
 
require_once( __DIR__ . "/cutomutils.php");

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    DB_HOST,
    DB_NAME
);

$pdo = new PDO(
    $dsn,
    DB_USER,
    DB_PASSWORD,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

/* -------------------------------------------------
 * Load from DB
 * ------------------------------------------------- */

$sql = "
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
      AND valid_record = 'yes'
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $bookingId]);

$booking = $stmt->fetch();

if (!$booking) {
    throw new RuntimeException('Nem található érvényes booking rekord.');
}

/* -------------------------------------------------
 * Fill variables
 * ------------------------------------------------- */

// 📧 Email
$email_address = $booking['booker_email'];
$price = ($booking['booker_days'] > 0) ? intval($booking['booker_huf'] / $booking['booker_days']) : 0;

// 🛒 Kosár (Stripe line_items kompatibilis alap)
$cart = [
    [
        'name'     => 'Szállásfoglalás – ' . $booking['booker_name'],
        'price'    => $price, // Ft, egész
        'qty'      => 1,
        'metadata' => [
            'booking_id' => $booking['id'],
            'persons'    => $booking['booker_persons'],
            'days'       => $booking['booker_days'],
        ],
    ]
];

/* -------------------------------------------------
 * Test data
 * ------------------------------------------------- */

// ideiglenes teszthez
/*
echo '<pre>';
var_dump($email_address, $cart);
exit;
*/

// innentől mehet tovább a Stripe Checkout Session létrehozás
