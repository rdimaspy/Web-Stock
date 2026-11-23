<?php
// Endpoint API Pencatatan Transaksi Stok Masuk (Stock In)
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

$userId     = requireApiAuth();
$businessId = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$productId  = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity   = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$notes      = trim($_POST['notes'] ?? '') ?: null;

if (!$businessId || $businessId < 1 || !$productId || $productId < 1) {
    jsonResponse('error', __('invalid_business_product'), 400);
}
if (!$quantity || $quantity < 1) {
    jsonResponse('error', __('invalid_quantity'), 400);
}

try {
    mysqli_begin_transaction($conn);

    // Kunci baris produk untuk menjaga konsistensi stok (FOR UPDATE)
    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.id, p.product_name, p.product_code, p.purchase_price, p.selling_price, p.current_stock
         FROM products p INNER JOIN businesses b ON b.id = p.business_id
         WHERE p.id = ? AND p.business_id = ? AND b.user_id = ? LIMIT 1 FOR UPDATE"
    );
    mysqli_stmt_bind_param($stmt, "iii", $productId, $businessId, $userId);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$product) {
        mysqli_rollback($conn);
        jsonResponse('error', __('product_access_denied'), 404);
    }

    $stockBefore = (int) $product['current_stock'];
    if ($quantity > 2147483647 - $stockBefore) {
        mysqli_rollback($conn);
        jsonResponse('error', __('stock_limit_exceeded'), 400);
    }

    $stockAfter      = $stockBefore + $quantity;
    $purchasePrice   = (float) $product['purchase_price'];
    $sellingPrice    = (float) $product['selling_price'];
    $productName     = (string) $product['product_name'];
    $productCode     = $product['product_code'];
    $transactionDate = date('Y-m-d H:i:s');

    // Update stok produk
    $stmt = mysqli_prepare($conn, "UPDATE products SET current_stock = ? WHERE id = ? AND business_id = ?");
    mysqli_stmt_bind_param($stmt, "iii", $stockAfter, $productId, $businessId);
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException(__('err_update_stock'));
    }

    // Catat riwayat transaksi stok masuk
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO inventory_transactions
            (business_id, product_id, product_name, product_code, transaction_type, quantity, stock_before, stock_after, purchase_price, selling_price, total_amount, notes, transaction_date)
         VALUES (?, ?, ?, ?, 'stock_in', ?, ?, ?, ?, ?, 0.00, ?, ?)"
    );
    mysqli_stmt_bind_param(
        $stmt, "iissiiiddss",
        $businessId, $productId, $productName, $productCode,
        $quantity, $stockBefore, $stockAfter,
        $purchasePrice, $sellingPrice, $notes, $transactionDate
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException(__('err_save_transaction'));
    }

    $transactionId = mysqli_insert_id($conn);
    mysqli_commit($conn);

    jsonResponse('success', __('stock_in_save_success'), 200, [
        'transaction_id' => $transactionId,
        'product_id'     => $productId,
        'quantity_added' => $quantity,
        'stock_before'   => $stockBefore,
        'stock_after'    => $stockAfter,
    ]);
} catch (Throwable $e) {
    @mysqli_rollback($conn);
    error_log('Stock In error: ' . $e->getMessage());
    jsonResponse('error', __('stock_in_save_failed'), 500);
}
