<?php
// Gunakan bahasa yang tersimpan di session
$lang = $_SESSION['language'] ?? 'id';

// Batasi bahasa yang tersedia
if (!in_array($lang, ['id', 'en'], true)) {
    $lang = 'id';
}

// Muat kamus terjemahan
$translations = require __DIR__ . '/../lang/' . $lang . '.php';

// Fungsi untuk mengambil teks terjemahan
function __(string $key): string
{
    global $translations;

    return $translations[$key] ?? $key;
}
