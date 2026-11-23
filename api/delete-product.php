<?php
// Endpoint API Hapus Produk Beserta File Fotonya
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

$user_id     = requireApiAuth();
$product_id  = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$business_id = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);

if (!$product_id || !$business_id) {
    jsonResponse('error', __('invalid_product_data'), 400);
}

// Pastikan produk terdaftar pada bisnis milik pengguna aktif
$stmt = mysqli_prepare(
    $conn,
    "SELECT p.id, p.photo FROM products p
     INNER JOIN businesses b ON b.id = p.business_id
     WHERE p.id = ? AND p.business_id = ? AND b.user_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "iii", $product_id, $business_id, $user_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    jsonResponse('error', __('product_access_denied'), 404);
}

// Hapus record produk dari database
$stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ? AND business_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $product_id, $business_id);
if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
    jsonResponse('error', __('product_delete_failed'), 500);
}

// Bersihkan file foto dan folder produk di server
$productFolder = __DIR__ . '/../uploads/products/' . $product_id;
if (!empty($product['photo'])) {
    $photoPath = $productFolder . '/' . basename($product['photo']);
    if (is_file($photoPath) && !is_link($photoPath)) @unlink($photoPath);
}
if (is_dir($productFolder) && !is_link($productFolder)) @rmdir($productFolder);

jsonResponse('success', __('product_delete_success'));
