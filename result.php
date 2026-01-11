<?php

require_once __DIR__ . '/bootstrap/bootstrap.php';

use App\Support\AppLogger;
use App\Http\PaymentRequest;
use App\Config\DatabaseConfig;
use App\Stripe\CheckoutService;
use App\Customize\CustomizeLoader;
use App\Repository\PaymentEventRepository;

try {
    // Ellenőrizzük a szükséges GET paramétert
    $paymentRequest = PaymentRequest::fromResultRequest($_GET);
} catch (\Throwable $e) {
    AppLogger::get()->warning('Invalid result request', ['error' => $e->getMessage()]);
	http_response_code(400);
    die('Not valid link / Nem megfelelő hívás');
}

try {
    // Stripe session lekérése
    $session = CheckoutService::retrieve($paymentRequest->getSessionId());

    AppLogger::get()->debug("Result Session", ['session' => $session->toArray()]);
} catch (\Throwable $e) {
    AppLogger::get()->error('Stripe session retrieval failed', ['error' => $e->getMessage()]);
	http_response_code(502);
    die('Stripe session could not be retrieved');
}

//Basic database save
$repo = new PaymentEventRepository(DatabaseConfig::load());
$repo->recordEvent([
    'stripe_session_id' => $session->id,
    'stripe_event_id'   => null,
    'event_source'      => 'result',
    'event_type'        => 'checkout.session.returned',
    'event_status'      => $session->payment_status,
    'http_method'       => $_SERVER['REQUEST_METHOD'] ?? null,
    'request_ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent'        => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'payload'           => $session->toArray(),
]);

// Customize logika opcionálisan
CustomizeLoader::loadResult(
    $config->get('customization_dir'),
    $session->toArray()
);

// Nézet betöltése
AppLogger::get()->debug('Rendering result page', [
    'session_id' => $paymentRequest->getSessionId()
]);
include_once 'resources/views/resultTemplate.php';