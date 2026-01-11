<?php
/**
 * Stripe sikeres fizetés rögzítése bookings táblába
 * Meghívható success.php-ból és webhook-ból is
 */

function saveStripeSuccessfulPayment(array $paymentIntent)
{
    // Check input data for many reasons
    if (
        empty($paymentIntent['status']) ||
        $paymentIntent['status'] !== 'succeeded'
    ) {
        return array('status'=>false, 'message' => "PaymentStatus is in" . $paymentIntent['status'] );
    }

    if (
        empty($paymentIntent['metadata']['booking_id']) ||
        !is_numeric($paymentIntent['metadata']['booking_id'])
    ) {
        return array('status'=>false, 'message' => "Booking id not exists :" . $paymentIntent['metadata']['booking_id']);
    }
	$bookingId = (int)$paymentIntent['metadata']['booking_id'];

    if (
        empty($paymentIntent['amount_received']) ||
        $paymentIntent['amount_received'] <= 0
    ) {
        return array('status'=>false, 'message' => "Amount not normal:" . $paymentIntent['amount_received']);
    }
	$amountHuf = $paymentIntent['amount_received'] / 100;


    // Get and Create Database connection
	require_once( __DIR__ . "/customutils.php");
	
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
       return array('status'=>false, 'message' => "Cannot connect to Database");
    }

    // --- 4. Ellenőrzés: már volt-e jóváírva? ---
    $checkSql = "
        SELECT booker_deposit_huf
        FROM bookings
        WHERE id = :id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute(['id' => $bookingId]);
    $row = $stmt->fetch();

    if (!$row) {
        return array('status'=>false, 'message' => "Booking item not found by id" . $bookingId);
    }

    if ((float)$row['booker_deposit_huf'] > 0) {
        return array('status'=>true, 'message' => "(Idempotent check) - The payment was previously made:" .  $row['booker_deposit_huf']);
    }

    // --- 5. Update ---
    $updateSql = "
        UPDATE bookings
        SET
            booker_deposit_huf = :amount,
            booker_deposit_origin = 'stripe',
            valid_record = 'yes'
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([
        'amount' => $amountHuf,
        'id'     => $bookingId,
    ]);

    return array('status'=>true, 'message' => "SAVE -" . $bookingId . " => " . $amountHuf );
}
