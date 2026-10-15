
<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.html");
    exit;
}

$lang = $_POST['language'] ?? 'id';

// Hanya izinkan bahasa yang tersedia
if (!in_array($lang, ['id', 'en'], true)) {
    $lang = 'id';
}

$_SESSION['language'] = $lang;

// Kembali ke halaman sebelumnya
$redirect = $_POST['redirect'] ?? 'index.html';

// Tolak redirect ke URL eksternal
if (
    !is_string($redirect) ||
    str_starts_with($redirect, '//') ||
    str_contains($redirect, '://') ||
    str_contains($redirect, "\n") ||
    str_contains($redirect, "\r")
) {
    $redirect = 'index.html';
}

header("Location: " . $redirect);
exit;
