<?php

/*
require_once __DIR__ . '/bootstrap/bootstrap.php';

use App\Support\AppLogger;

$payload = @file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'];

try {

    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig,
        StripeConfig::webhookSecret()
    );   

} catch (Exception $e) {
    http_response_code(400);
    exit;
}

if ($event->type === 'checkout.session.completed') {
    $sessionData = $event->data->object;
	
    \App\Support\AppLogger::get()->debug( "Webhook session:" ,  ['session' => $sessionData]); 
	
	$customizefile = $config->get('customization_dir') . "resultsave.php";

	if (is_file($customizefile)) {
		include_once($customizefile);
		$saveResult = saveStripeSuccessfulPayment($sessionData->toArray());
        \App\Support\AppLogger::get()->debug( "Webhook saveresult:" , $saveResult); 
	}
	
	
    // Itt garantált, hogy fizetett
    // → rendelés mentése DB-be
    // → számlázás
    // → email
}

http_response_code(200);
*/


require_once __DIR__ . '/bootstrap/bootstrap.php';

use App\Support\AppLogger;
use App\Http\WebhookRequest;
use App\Config\DatabaseConfig;
use App\Stripe\WebhookVerifier;
use App\Customize\CustomizeLoader;
use App\Stripe\WebhookIdempotency;
use App\Repository\PaymentEventRepository;
use App\Stripe\CheckoutSessionCompletedHandler;

try {
    [$payload, $signature] = WebhookRequest::fromGlobals();
    $event = WebhookVerifier::verify($payload, $signature);
} catch (\Throwable $e) {
    AppLogger::get()->warning('Webhook verification failed', [
        'error' => $e->getMessage()
    ]);
    http_response_code(400);
    exit;
}

if (WebhookIdempotency::alreadyProcessed($event->id)) {
    AppLogger::get()->info('Webhook already processed', [
        'event_id' => $event->id
    ]);
    http_response_code(200);
    exit;
}

switch ($event->type) {
    case 'checkout.session.completed':
        CheckoutSessionCompletedHandler::handle(
            $event->data->object,
            $config
        );
        break;

    default:
        AppLogger::get()->debug('Unhandled webhook event', [
            'type' => $event->type
        ]);
}

WebhookIdempotency::markProcessed($event->id);

//Basic database save
$session = $event->data->object;
$repo = new PaymentEventRepository(DatabaseConfig::load());
$repo->recordEvent([
    'stripe_session_id' => $session->id,
    'stripe_event_id'   => $event->id,
    'event_source'      => 'webhook',
    'event_type'        => $event->type,
    'event_status'      => $session->payment_status,
    'http_method'       => $_SERVER['REQUEST_METHOD'] ?? null,
    'request_ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent'        => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'payload'           => $session->toArray(),
]);

CustomizeLoader::loadWebhook(
    $config->get('customization_dir'),
    $session->toArray(),
);

http_response_code(200);