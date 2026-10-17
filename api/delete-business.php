<?php
// Memulai sesi login pengguna
session_start();

// Memuat file konfigurasi koneksi database
require_once __DIR__ . '/../config/database.php';

// Menentukan format respons server sebagai JSON
header('Content-Type: application/json');

// Memeriksa status autentikasi pengguna; kembalikan status 401 jika belum login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Memastikan permintaan hanya dikirimkan melalui metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Menangkap dan memvalidasi ID bisnis serta ID pengguna dari sesi
$business_id_to_delete = (int)($_POST['business_id'] ?? 0);
$user_id               = (int)$_SESSION['user_id'];

// Memastikan nilai ID bisnis yang dikirimkan valid (lebih dari 0)
if ($business_id_to_delete <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Bisnis tidak valid.']);
    exit;
}

// Menyiapkan prepared statement untuk menghapus data bisnis milik pengguna aktif
$stmt = mysqli_prepare($conn, "DELETE FROM businesses WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $business_id_to_delete, $user_id);

// Eksekusi proses penghapusan data bisnis dari database
if (mysqli_stmt_execute($stmt)) {
    // Memeriksa apakah ada baris data bisnis yang berhasil terhapus
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Toko berhasil dihapus secara permanen.'
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Toko tidak ditemukan atau kamu tidak memiliki hak akses.'
        ]);
    }
} else {
    // Mengembalikan respons error jika prepared statement gagal dieksekusi
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal menghapus toko dari database.'
    ]);
}
exit;