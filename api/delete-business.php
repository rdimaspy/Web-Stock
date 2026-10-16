<?php
// Memulai sesi login pengguna
session_start();

// Memuat koneksi database
require_once __DIR__ . '/../config/database.php';

// Menentukan format respons server sebagai JSON
header('Content-Type: application/json');

// Memeriksa autentikasi pengguna
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Memastikan permintaan dikirim via metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Ambil ID bisnis yang dikirimkan
$business_id_to_delete = (int)($_POST['business_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if ($business_id_to_delete <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID Bisnis tidak valid.']);
    exit;
}

// Hapus data toko dari database berdasarkan ID bisnis dan ID pengguna
$stmt = mysqli_prepare($conn, "DELETE FROM businesses WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $business_id_to_delete, $user_id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Toko berhasil dihapus secara permanen.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Toko tidak ditemukan atau kamu tidak memiliki hak akses.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal menghapus toko dari database.'
    ]);
}
exit;