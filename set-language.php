<?php
// Menyimpan pilihan bahasa aktif ke dalam session dan mengalihkan kembali pengguna
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$lang = $_POST['language'] ?? 'id';
$_SESSION['language'] = in_array($lang, ['id', 'en'], true) ? $lang : 'id';

$redirect = $_POST['redirect'] ?? 'index.php';
if (
    !is_string($redirect) ||
    str_starts_with($redirect, '//') ||
    str_contains($redirect, '://') ||
    str_contains($redirect, "\n") ||
    str_contains($redirect, "\r")
) {
    $redirect = 'index.php';
}

header("Location: " . $redirect);
exit;
