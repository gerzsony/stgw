<?php
function onPaymentIndex(array $data): array
{
    // szimuláljuk a "DB-ből jött" adatot
    $data['cart'][] = [
        'name' => 'Demo booking',
        'qty'  => 1,
        'price'=> 10000,
    ];

    return $data;
}

