<?php

session_start();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Ambil data dari request
$business_id = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$product_id  = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity    = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$notes       = trim($_POST['notes'] ?? '');

// Validasi input dasar
if (!$business_id || !$product_id || !$quantity || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak valid'
    ]);
    exit;
}

// Transaksi Database
try {
    mysqli_begin_transaction($conn);

// Ambil data produk dan pastikan milik bisnis tersebut
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, product_name, current_stock, selling_price
        FROM products
        WHERE id = ?
        AND business_id = ?
        LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $product_id,
        $business_id
    );

    mysqli_stmt_execute($stmt);

    $product = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );


    if (!$product) {
        throw new Exception('Produk tidak ditemukan');
    }

    // Cek stok cukup
    if ((int)$product['current_stock'] < $quantity) {
        echo json_encode([
            'success' => false,
            'message' => 'Stok tidak mencukupi'
        ]);
        exit;
    }

    // Simpan kondisi stok sebelum transaksi
    $stock_before = (int)$product['current_stock'];

    // Hitung stok setelah keluar
    $stock_after = $stock_before - $quantity;

    // Hitung total penjualan
    $total_amount = $quantity * $product['selling_price'];

    // Update stok produk
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE products
        SET current_stock = ?
        WHERE id = ?
        AND business_id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $stock_after,
        $product_id,
        $business_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal update stok');
    }

    // Simpan history transaksi
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO inventory_transactions
        (
            business_id,
            product_id,
            product_name,
            transaction_type,
            quantity,
            stock_before,
            stock_after,
            selling_price,
            total_amount,
            notes
        )
        VALUES
        (?, ?, ?, 'stock_out', ?, ?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "iisiiidds",
        $business_id,
        $product_id,
        $product['product_name'],
        $quantity,
        $stock_before,
        $stock_after,
        $product['selling_price'],
        $total_amount,
        $notes
    );

    if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Gagal menyimpan history');
        }

    // Simpan perubahan
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Stok keluar berhasil disimpan'
    ]);

} catch (Exception $e) {
// Kalau error balik ke awal
    mysqli_rollback($conn);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}