<?php
// Endpoint API Tambah Produk Baru
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

$user_id      = requireApiAuth();
$business_id  = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$product_name = trim($_POST['product_name'] ?? '');
$product_code = trim($_POST['product_code'] ?? '');

if (!$business_id || $product_name === '' || $product_code === '') {
    jsonResponse('error', __('product_name_code_req'), 400);
}

verifyBusinessAccess($conn, $business_id, $user_id);

// Cek duplikasi kode produk pada bisnis yang sama
$stmtCheck = mysqli_prepare($conn, "SELECT id FROM products WHERE business_id = ? AND product_code = ? LIMIT 1");
mysqli_stmt_bind_param($stmtCheck, "is", $business_id, $product_code);
mysqli_stmt_execute($stmtCheck);
if (mysqli_num_rows(mysqli_stmt_get_result($stmtCheck)) > 0) {
    jsonResponse('error', __('duplicate_product_code'), 400);
}

$category_id    = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
$purchase_price = str_replace(',', '.', trim($_POST['purchase_price'] ?? '0'));
$selling_price  = str_replace(',', '.', trim($_POST['selling_price'] ?? '0'));

if (!is_numeric($purchase_price) || !is_numeric($selling_price) || (float) $purchase_price < 0 || (float) $selling_price < 0) {
    jsonResponse('error', __('invalid_price'), 400);
}

$purchase_price  = (float) $purchase_price;
$selling_price   = (float) $selling_price;
$initial_stock   = (int) ($_POST['initial_stock'] ?? 0);
$expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
$notes           = $_POST['notes'] ?? null;

// Simpan produk ke database
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO products (business_id, category_id, product_name, product_code, purchase_price, selling_price, initial_stock, current_stock, expiration_date, notes)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param(
    $stmt, "iissddisss",
    $business_id, $category_id, $product_name, $product_code,
    $purchase_price, $selling_price, $initial_stock, $initial_stock,
    $expiration_date, $notes
);

if (!mysqli_stmt_execute($stmt)) {
    jsonResponse('error', __('product_save_error'), 500);
}

$product_id = mysqli_insert_id($conn);

// Proses unggahan foto produk (opsional)
if (isset($_FILES['photo'])) {
    $uploadDir = __DIR__ . '/../uploads/products/' . $product_id . '/';
    $filename = uploadPhotoFile($_FILES['photo'], $uploadDir);
    if ($filename) {
        $update = mysqli_prepare($conn, "UPDATE products SET photo = ? WHERE id = ?");
        mysqli_stmt_bind_param($update, "si", $filename, $product_id);
        mysqli_stmt_execute($update);
    }
}

jsonResponse('success', __('product_create_success'), 200, ['product_id' => $product_id]);
