<?php
// Endpoint API Ambil Detail Satu Produk (untuk Modal Edit)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!isset($_GET['id'])) {
    jsonResponse('error', __('product_id_not_found'), 400);
}

$id = (int) $_GET['id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    jsonResponse('error', __('product_not_found'), 404);
}

jsonResponse('success', 'OK', 200, ['data' => $product]);
