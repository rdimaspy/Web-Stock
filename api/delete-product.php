<?php
// Menentukan header respon server dalam format JSON UTF-8
header('Content-Type: application/json; charset=utf-8');

// Memulai sesi login pengguna dan memuat file koneksi database
session_start();
require_once __DIR__ . '/../config/database.php';

// Fungsi helper untuk merender respon JSON dan menghentikan eksekusi script
function respond($status, $message, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'message' => $message
    ]);
    exit;
}

// Memeriksa status autentikasi pengguna
if (!isset($_SESSION['user_id'])) {
    respond('error', 'Sesi login berakhir. Silakan login kembali.', 401);
}

// Memastikan permintaan API dikirim khusus menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Metode permintaan tidak diizinkan.', 405);
}

// Menangkap dan memvalidasi ID pengguna dari sesi serta ID produk dan ID bisnis dari POST
$user_id     = (int) $_SESSION['user_id'];
$product_id  = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$business_id = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);

// Memastikan ID produk dan ID bisnis bernilai valid
if (!$product_id || !$business_id) {
    respond('error', 'Data produk tidak valid.', 400);
}

// Menyiapkan query untuk memastikan produk terdaftar pada bisnis milik pengguna
$stmt = mysqli_prepare(
    $conn,
    "SELECT p.id, p.photo
    FROM products p
    INNER JOIN businesses b ON b.id = p.business_id
    WHERE p.id = ? AND p.business_id = ? AND b.user_id = ?
    LIMIT 1"
);

// Eksekusi prepared statement pencarian hak akses produk
mysqli_stmt_bind_param(
    $stmt,
    'iii',
    $product_id,
    $business_id,
    $user_id
);

mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Menolak akses jika data produk tidak ditemukan atau bukan milik pengguna
if (!$product) {
    respond('error', 'Produk tidak ditemukan atau kamu tidak memiliki akses.', 404);
}

// Menyiapkan query untuk menghapus data produk dari database secara permanen
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM products
    WHERE id = ? AND business_id = ?"
);

mysqli_stmt_bind_param($stmt, 'ii', $product_id, $business_id);

// Menangani kegagalan eksekusi query penghapusan produk
if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
    respond('error', 'Produk gagal dihapus.', 500);
}

// Menentukan lokasi folder penyimpanan foto produk
$productFolder = __DIR__ . '/../uploads/products/' . $product_id;

// Memeriksa dan menghapus file foto produk dari server jika ada
if (!empty($product['photo'])) {
    $photoName = basename($product['photo']);
    $photoPath = $productFolder . '/' . $photoName;

    if (is_file($photoPath) && !is_link($photoPath)) {
        @unlink($photoPath);
    }
}

// Memeriksa dan menghapus folder penyimpanan produk jika dalam kondisi kosong
if (is_dir($productFolder) && !is_link($productFolder)) {
    @rmdir($productFolder);
}

// Mengembalikan respons sukses penghapusan data produk
respond('success', 'Produk berhasil dihapus.');