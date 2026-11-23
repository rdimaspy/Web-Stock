<?php
// Gunakan bahasa yang tersimpan di session (default: id)
$lang = $_SESSION['language'] ?? 'id';
if (!in_array($lang, ['id', 'en'], true)) {
    $lang = 'id';
}

// Muat kamus terjemahan sesuai bahasa aktif
$translations = require __DIR__ . '/../lang/' . $lang . '.php';

// Ambil teks terjemahan berdasarkan key
function __(string $key): string
{
    global $translations;
    return $translations[$key] ?? $key;
}

// Render komponen logo brand StockIn secara konsisten di seluruh halaman
function renderLogo(string $extraClass = ''): void
{
    $cls = trim('brand-logo ' . $extraClass);
    echo '<div class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '">'
        . '<span class="brand-logo-text">Stock</span>'
        . '<div class="brand-logo-box">'
        . '<span class="brand-logo-shadow"></span>'
        . '<span class="brand-logo-in">In</span>'
        . '</div></div>';
}
