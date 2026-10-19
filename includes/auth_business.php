<?php
// Mulai atau lanjutkan sesi pengguna yang aktif
session_start();

// Kembalikan pengguna ke halaman login, jika belum memiliki sesi login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Konfigurasi dan koneksi ke database
require_once __DIR__ . '/../config/database.php';

// Validasi dan ambil ID bisnis dari parameter URL
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);
if (!$business_id || $business_id < 1) {
    header("Location: ../businesses.php");
    exit;
}

// Prepare statement untuk memastikan bisnis terdaftar dan milik pengguna aktif
$stmt = mysqli_prepare($conn, "SELECT id, business_name, currency FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");

// Parameter business_id dan user_id ke query, Eksekusi query, Ambil data detail bisnis
mysqli_stmt_bind_param($stmt, "ii", $business_id, $_SESSION['user_id']);    
mysqli_stmt_execute($stmt);
$business = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Hentikan proses jika bisnis tidak ditemukan atau bukan milik pengguna aktif
if (!$business) {
    http_response_code(404);
    exit("Bisnis tidak ditemukan atau tidak memiliki akses.");
}
?>