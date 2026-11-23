<?php
// Endpoint API Pencatatan Audit Stok (Stock Audit)
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

requireApiAuth();

$business_id    = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$product_id     = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$physical_stock = filter_input(INPUT_POST, 'physical_stock', FILTER_VALIDATE_INT);
$notes          = trim($_POST['notes'] ?? '');

if (!$business_id || !$product_id || $physical_stock === false || $physical_stock === null || $physical_stock < 0) {
    jsonResponse('error', __('invalid_data'), 400);
}

try {
    mysqli_begin_transaction($conn);

    $stmt = mysqli_prepare($conn, "SELECT id, product_name, current_stock FROM products WHERE id = ? AND business_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $business_id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$product) {
        throw new Exception(__('product_not_found'));
    }

    $stock_before = (int) $product['current_stock'];
    $difference   = $physical_stock - $stock_before;

    if ($difference === 0) {
        mysqli_commit($conn);
        jsonResponse('success', __('no_stock_change'));
    }

    // Sesuaikan stok sistem dengan hasil audit fisik
    $stmt = mysqli_prepare($conn, "UPDATE products SET current_stock = ? WHERE id = ? AND business_id = ?");
    mysqli_stmt_bind_param($stmt, "iii", $physical_stock, $product_id, $business_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(__('stock_update_failed'));
    }

    // Catat riwayat transaksi audit stok
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO inventory_transactions
            (business_id, product_id, product_name, transaction_type, quantity, stock_before, stock_after, notes)
         VALUES (?, ?, ?, 'stock_audit', ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param(
        $stmt, "iisiiis",
        $business_id, $product_id, $product['product_name'],
        $difference, $stock_before, $physical_stock, $notes
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(__('audit_save_failed'));
    }

    mysqli_commit($conn);
    jsonResponse('success', __('audit_save_success'));
} catch (Exception $e) {
    mysqli_rollback($conn);
    jsonResponse('error', $e->getMessage(), 500);
}
