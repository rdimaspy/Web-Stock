<?php
// Endpoint API Pembaruan Data Produk
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

$user_id         = requireApiAuth();
$business_id     = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$product_id      = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$product_name    = trim($_POST['product_name'] ?? '');
$product_code    = trim($_POST['product_code'] ?? '');
$category_id     = !empty($_POST['category_id']) ? filter_var($_POST['category_id'], FILTER_VALIDATE_INT) : null;
$purchase_price  = str_replace(',', '.', trim($_POST['purchase_price'] ?? '0'));
$selling_price   = str_replace(',', '.', trim($_POST['selling_price'] ?? '0'));
$initial_stock   = filter_input(INPUT_POST, 'initial_stock', FILTER_VALIDATE_INT);
$expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
$notes           = trim($_POST['notes'] ?? '');

if (!$business_id || !$product_id || $product_name === '' || $initial_stock === false || $initial_stock === null || $initial_stock < 0) {
    jsonResponse('error', __('incomplete_product_data'), 400);
}

if (!is_numeric($purchase_price) || !is_numeric($selling_price) || (float) $purchase_price < 0 || (float) $selling_price < 0) {
    jsonResponse('error', __('invalid_price'), 400);
}

$purchase_price = (float) $purchase_price;
$selling_price  = (float) $selling_price;

verifyBusinessAccess($conn, $business_id, $user_id);

// Ambil data produk lama
$stmt = mysqli_prepare($conn, "SELECT id, photo FROM products WHERE id = ? AND business_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $product_id, $business_id);
mysqli_stmt_execute($stmt);
$existing_product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$existing_product) {
    jsonResponse('error', __('product_not_found'), 404);
}

// Validasi kategori jika dipilih
if ($category_id !== null) {
    if ($category_id === false) {
        jsonResponse('error', __('invalid_category'), 400);
    }
    $stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE id = ? AND business_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $category_id, $business_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) {
        jsonResponse('error', __('category_not_found'), 400);
    }
}

// Validasi format tanggal kedaluwarsa (Y-m-d)
if ($expiration_date !== null) {
    $date = DateTime::createFromFormat('!Y-m-d', $expiration_date);
    if (!$date || $date->format('Y-m-d') !== $expiration_date) {
        jsonResponse('error', __('invalid_expiry_date'), 400);
    }
}

// Update data produk di database
$stmt = mysqli_prepare(
    $conn,
    "UPDATE products
     SET category_id = ?, product_name = ?, product_code = ?, purchase_price = ?, selling_price = ?, initial_stock = ?, expiration_date = ?, notes = ?
     WHERE id = ? AND business_id = ?"
);
mysqli_stmt_bind_param(
    $stmt, "issddissii",
    $category_id, $product_name, $product_code,
    $purchase_price, $selling_price, $initial_stock,
    $expiration_date, $notes, $product_id, $business_id
);

if (!mysqli_stmt_execute($stmt)) {
    jsonResponse('error', __('product_update_failed'), 500);
}

// Proses unggahan foto produk baru jika ada
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $uploadDir = __DIR__ . '/../uploads/products/' . $product_id . '/';
    $filename = uploadPhotoFile($_FILES['photo'], $uploadDir);
    if (!$filename) {
        jsonResponse('error', __('photo_upload_failed'), 400);
    }

    $updatePhoto = mysqli_prepare($conn, "UPDATE products SET photo = ? WHERE id = ? AND business_id = ?");
    mysqli_stmt_bind_param($updatePhoto, "sii", $filename, $product_id, $business_id);
    if (!mysqli_stmt_execute($updatePhoto)) {
        @unlink($uploadDir . $filename);
        jsonResponse('error', __('photo_info_save_failed'), 500);
    }

    if (!empty($existing_product['photo'])) {
        $oldPhoto = $uploadDir . basename($existing_product['photo']);
        if (is_file($oldPhoto)) @unlink($oldPhoto);
    }
}

jsonResponse('success', __('product_update_success'), 200, ['product_id' => $product_id]);
