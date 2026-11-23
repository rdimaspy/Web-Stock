<?php
// Endpoint API Pembuatan Kategori Produk Baru
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

$user_id       = requireApiAuth();
$business_id   = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$category_name = trim($_POST['category_name'] ?? '');

if (!$business_id || $category_name === '') {
    jsonResponse('error', __('category_name_required'), 400);
}

verifyBusinessAccess($conn, $business_id, $user_id);

// Cek duplikasi nama kategori dalam bisnis yang sama (case-insensitive)
$stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE business_id = ? AND LOWER(category_name) = LOWER(?) LIMIT 1");
mysqli_stmt_bind_param($stmt, "is", $business_id, $category_name);
mysqli_stmt_execute($stmt);
if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    jsonResponse('error', __('category_exists'), 400);
}

// Simpan kategori baru
$stmt = mysqli_prepare($conn, "INSERT INTO categories (business_id, category_name) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, "is", $business_id, $category_name);
if (!mysqli_stmt_execute($stmt)) {
    jsonResponse('error', __('category_save_failed'), 500);
}

jsonResponse('success', __('category_save_success'), 200, [
    'category' => [
        'id'            => mysqli_insert_id($conn),
        'category_name' => $category_name,
    ],
]);
