<?php

namespace App\Http;

use RuntimeException;

/**
 * elements must be this type

$cart = [
    [
        'name'  => 'Szálláshely 4 főre',
        'price' => 29900, // HUF fillérben
        'qty'   => 1,
		'metadata' => [],
    ],
    [
        'name'  => 'Szálláshely 3 főre',
        'price' => 19900, // HUF fillérben
        'qty'   => 2,
		'metadata' => [],
    ],	
];
*/

final class PaymentRequest
{
	
    private string $sessionId;

    private function __construct(string $sessionId)
    {
        $this->sessionId = $sessionId;
    }
	
    public static function fromRequest(array $request): array
    {
        if (empty($request['did'])) {
            throw new RuntimeException('Missing payment data');
        }

        $json = base64_decode($request['did'], true);
        if ($json === false) {
            throw new RuntimeException('Invalid base64 payment data');
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON payment data');
        }

        return $data;
    }
	
    public static function fromResultRequest(array $get): self
    {
        if (!isset($get['sid']) || strlen($get['sid']) === 0) {
            throw new RuntimeException('Missing or invalid session ID');
        }

        return new self($get['sid']);
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }
	
}
