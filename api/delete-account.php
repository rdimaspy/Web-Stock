<?php
// Memulai sesi untuk mengakses data login pengguna
session_start();

// Memuat file konfigurasi koneksi database
require_once __DIR__ . '/../config/database.php';

// Menentukan format respons server sebagai JSON
header('Content-Type: application/json');

// Memeriksa autentikasi pengguna yang sedang login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Memastikan permintaan hanya dikirim melalui metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Ambil ID pengguna dari sesi aktif
$user_id = (int)$_SESSION['user_id'];

// Hapus data pengguna dari database berdasarkan ID
$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);

// Eksekusi penghapusan akun dan bersihkan sesi login jika berhasil
if (mysqli_stmt_execute($stmt)) {
    // Hapus seluruh data sesi login
    session_unset();
    session_destroy();

    echo json_encode([
        'status' => 'success',
        'message' => 'Akun kamu beserta seluruh data bisnis berhasil dihapus.'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal menghapus akun.'
    ]);
}