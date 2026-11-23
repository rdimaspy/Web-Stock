<?php
// Endpoint API Pencatatan Transaksi Stok Keluar (Stock Out)
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

requireApiAuth();

$business_id = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$product_id  = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity    = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$notes       = trim($_POST['notes'] ?? '');

if (!$business_id || !$product_id || !$quantity || $quantity < 1) {
    jsonResponse('error', __('invalid_data'), 400);
}

try {
    mysqli_begin_transaction($conn);

    // Ambil informasi stok dan harga produk
    $stmt = mysqli_prepare($conn, "SELECT id, product_name, current_stock, purchase_price, selling_price FROM products WHERE id = ? AND business_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $business_id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$product) {
        throw new Exception(__('product_not_found'));
    }

    $stock_before = (int) $product['current_stock'];
    if ($stock_before < $quantity) {
        mysqli_rollback($conn);
        jsonResponse('error', __('insufficient_stock'), 400);
    }

    $stock_after  = $stock_before - $quantity;
    $total_amount = $quantity * (float) $product['selling_price'];

    // Kurangi stok berjalan produk
    $stmt = mysqli_prepare($conn, "UPDATE products SET current_stock = ? WHERE id = ? AND business_id = ?");
    mysqli_stmt_bind_param($stmt, "iii", $stock_after, $product_id, $business_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(__('stock_update_failed'));
    }

    // Catat riwayat transaksi stok keluar
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO inventory_transactions
            (business_id, product_id, product_name, transaction_type, quantity, stock_before, stock_after, purchase_price, selling_price, total_amount, notes)
         VALUES (?, ?, ?, 'stock_out', ?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param(
        $stmt, "iisiiiddds",
        $business_id, $product_id, $product['product_name'],
        $quantity, $stock_before, $stock_after,
        $product['purchase_price'], $product['selling_price'], $total_amount, $notes
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(__('history_save_failed'));
    }

    mysqli_commit($conn);
    jsonResponse('success', __('stock_out_save_success'));
} catch (Exception $e) {
    mysqli_rollback($conn);
    jsonResponse('error', $e->getMessage(), 500);
}
