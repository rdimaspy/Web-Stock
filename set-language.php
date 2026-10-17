<?php
// Memulai sesi untuk menyimpan pilihan bahasa pengguna
session_start();

// Memastikan permintaan dikirim khusus menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.html");
    exit;
}

// Menangkap input pilihan bahasa dengan default bahasa Indonesia ('id')
$lang = $_POST['language'] ?? 'id';

// Memvalidasi agar kode bahasa yang masuk hanya 'id' atau 'en'
if (!in_array($lang, ['id', 'en'], true)) {
    $lang = 'id';
}

// Menyimpan pilihan bahasa pengguna ke dalam variabel sesi
$_SESSION['language'] = $lang;

// Menangkap URL halaman asal untuk proses pengalihan kembali
$redirect = $_POST['redirect'] ?? 'index.html';

// Memvalidasi URL redirect untuk mencegah serangan Open Redirect ke situs luar
if (
    !is_string($redirect) ||
    str_starts_with($redirect, '//') ||
    str_contains($redirect, '://') ||
    str_contains($redirect, "\n") ||
    str_contains($redirect, "\r")
) {
    $redirect = 'index.html';
}

// Mengalihkan kembali pengguna ke halaman sebelumnya
header("Location: " . $redirect);
exit;