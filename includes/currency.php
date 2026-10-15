
<?php

// Format nominal berdasarkan mata uang bisnis.
function formatMoneyPHP($amount, $currency = 'IDR')
{
    $formats = [
        'IDR' => ['decimal' => ',', 'thousands' => '.'],
        'USD' => ['decimal' => '.', 'thousands' => ','],
        'EUR' => ['decimal' => ',', 'thousands' => '.'],
        'JPY' => ['decimal' => '.', 'thousands' => ','],
        'GBP' => ['decimal' => '.', 'thousands' => ','],
        'CNY' => ['decimal' => '.', 'thousands' => ','],
    ];

    $symbols = [
        'IDR' => 'Rp',
        'USD' => '$',
        'EUR' => '€',
        'JPY' => '¥',
        'GBP' => '£',
        'CNY' => 'CN¥',
    ];

    $currency = strtoupper($currency);

    $format = $formats[$currency] ?? $formats['IDR'];
    $symbol = $symbols[$currency] ?? 'Rp';

    $formattedAmount = number_format(
        (float) $amount,
        2,
        $format['decimal'],
        $format['thousands']
    );

    return $symbol . ' ' . $formattedAmount;
}
