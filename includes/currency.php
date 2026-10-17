<?php

// Fungsi untuk memformat angka nominal menjadi string mata uang lengkap dengan simbolnya
function formatMoneyPHP($amount, $currency = 'IDR')
{
    // Pemisah desimal dan ribuan untuk tiap mata uang
    $formats = [
        'IDR' => ['decimal' => ',', 'thousands' => '.'],
        'USD' => ['decimal' => '.', 'thousands' => ','],
        'EUR' => ['decimal' => ',', 'thousands' => '.'],
        'JPY' => ['decimal' => '.', 'thousands' => ','],
        'GBP' => ['decimal' => '.', 'thousands' => ','],
        'CNY' => ['decimal' => '.', 'thousands' => ','],
    ];

    // Pemetaan simbol prefix mata uang
    $symbols = [
        'IDR' => 'Rp',
        'USD' => '$',
        'EUR' => '€',
        'JPY' => '¥',
        'GBP' => '£',
        'CNY' => 'CN¥',
    ];

    // Memastikan kode mata uang berformat huruf kapital
    $currency = strtoupper($currency);

    // Menentukan aturan format dan simbol sesuai mata uang atau default IDR
    $format = $formats[$currency] ?? $formats['IDR'];
    $symbol = $symbols[$currency] ?? 'Rp';

    // Memformat angka dengan 2 digit desimal serta pemisah ribuan
    $formattedAmount = number_format(
        (float) $amount,
        2,
        $format['decimal'],
        $format['thousands']
    );

    // Mengembalikan hasil kombinasi simbol mata uang dan angka terformat
    return $symbol . ' ' . $formattedAmount;
}