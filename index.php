<?php

require_once __DIR__ . '/bootstrap/bootstrap.php';

use App\Http\SessionState;
use App\Support\AppLogger;
use App\Http\PaymentRequest;
use App\Config\DatabaseConfig;
use App\Stripe\CheckoutService;
use App\Stripe\LineItemFactory;
use App\Customize\CustomizeLoader;
use App\Repository\PaymentEventRepository;


try {
    $data = PaymentRequest::fromRequest($_REQUEST);
} catch (\Throwable $e) {
    AppLogger::get()->warning('Invalid payment request', ['error' => $e->getMessage()]);
    die("Broken payment link / Hibás fizetési link");
}

AppLogger::get()->debug('Incoming Request data', ['data' => $data]);

$data = CustomizeLoader::load(
    $config->get('customization_dir'),
    $data
);

/** @var array $cart */
$cart = $data['cart'] ?? [];

$lineItems = LineItemFactory::fromCart($cart);

$sessionData = [
    'mode' => 'payment',
    'line_items' => $lineItems,
    'success_url' => $config->get('result_url') . '?sid={CHECKOUT_SESSION_ID}',
    'cancel_url'  => $config->get('result_url') . '?sid={CHECKOUT_SESSION_ID}'
];

$email_address = $data['customer_email'] ?? '';
if (strlen($email_address) > 0){
	$sessionData['customer_email'] = $email_address;
}

if (isset($data['back_url'])) {
    SessionState::setBackUrl($data['back_url']);
} else {
    SessionState::setBackUrl('');
}

if (isset($data['paysite_title'])) {
    SessionState::setPaysiteTitle($data['paysite_title']);
} else {
    SessionState::setPaysiteTitle('');
}


AppLogger::get()->debug( "Session data:" , $sessionData); 

$session = CheckoutService::create($sessionData);

$repo = new PaymentEventRepository(DatabaseConfig::load());
$repo->recordEvent([
    'stripe_session_id' => $session->id,
    'stripe_event_id'   => null,
    'event_source'      => 'index',
    'event_type'        => 'checkout.session.created',
    'event_status'      => 'created',
    'http_method'       => $_SERVER['REQUEST_METHOD'] ?? null,
    'request_ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent'        => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'payload'           => $session->toArray(),
]);

header("Location: " . $session->url);
exit;