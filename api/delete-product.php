
<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../config/database.php';

function respond($status, $message, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'message' => $message
    ]);
    exit;
}

// Pastikan pengguna sudah login.
if (!isset($_SESSION['user_id'])) {
    respond('error', 'Sesi login berakhir. Silakan login kembali.', 401);
}

// Endpoint hanya menerima POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Metode permintaan tidak diizinkan.', 405);
}

$user_id = (int) $_SESSION['user_id'];
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$business_id = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);

if (!$product_id || !$business_id) {
    respond('error', 'Data produk tidak valid.', 400);
}

// Pastikan produk berada di bisnis milik pengguna.
$stmt = mysqli_prepare(
    $conn,
    "SELECT p.id, p.photo
    FROM products p
    INNER JOIN businesses b ON b.id = p.business_id
    WHERE p.id = ? AND p.business_id = ? AND b.user_id = ?
    LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    'iii',
    $product_id,
    $business_id,
    $user_id
);

mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    respond('error', 'Produk tidak ditemukan atau kamu tidak memiliki akses.', 404);
}

// Hapus data produk secara permanen.
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM products
    WHERE id = ? AND business_id = ?"
);

mysqli_stmt_bind_param($stmt, 'ii', $product_id, $business_id);

if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
    respond('error', 'Produk gagal dihapus.', 500);
}

// Hapus foto produk jika ada.
$productFolder = __DIR__ . '/../uploads/products/' . $product_id;

if (!empty($product['photo'])) {
    $photoName = basename($product['photo']);
    $photoPath = $productFolder . '/' . $photoName;

    if (is_file($photoPath) && !is_link($photoPath)) {
        @unlink($photoPath);
    }
}

// Hapus folder jika sudah kosong.
if (is_dir($productFolder) && !is_link($productFolder)) {
    @rmdir($productFolder);
}

respond('success', 'Produk berhasil dihapus.');
