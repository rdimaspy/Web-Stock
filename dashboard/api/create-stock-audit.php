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

// Ambil data dari form
$business_id  = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$product_id   = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$physical_stock = filter_input(INPUT_POST, 'physical_stock', FILTER_VALIDATE_INT);
$notes = trim($_POST['notes'] ?? '');

// Validasi input
if (
    !$business_id ||
    !$product_id ||
    $physical_stock === false ||
    $physical_stock === null ||
    $physical_stock < 0
) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak valid'
    ]);
    exit;
}

try {
    // Semua perubahan database harus berhasil bersama
    mysqli_begin_transaction($conn);

    // Ambil stok terbaru dari database
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, product_name, current_stock
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

    // Stok sebelum audit
    $stock_before = (int)$product['current_stock'];

    // Hitung perbedaan stok
    $difference = $physical_stock - $stock_before;

    // kalau tidak ada perbedaan, stok ga berubah
    if ($difference === 0) {

        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Tidak ada perubahan stok'
        ]);
        exit;
    }

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
        $physical_stock,
        $product_id,
        $business_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal update stok');
    }

    // Simpan audit ke history
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
            notes
        )
        VALUES
        (?, ?, ?, 'stock_audit', ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "iisiiis",
        $business_id,
        $product_id,
        $product['product_name'],
        $difference,
        $stock_before,
        $physical_stock,
        $notes
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal menyimpan audit');
    }

    // Simpan semua perubahan
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Audit stok berhasil disimpan'
    ]);

} catch (Exception $e) {
    // Batalkan semua perubahan jika gagal
    mysqli_rollback($conn);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}