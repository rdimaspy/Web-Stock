<?php
// Memulai sesi login dan memuat konfigurasi koneksi database
session_start();
require_once __DIR__ . '/../config/database.php';

// Menentukan format respons berupa JSON
header('Content-Type: application/json');

// Memeriksa autentikasi pengguna; kembalikan pesan error jika belum login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Menangkap dan memvalidasi input parameter dari request POST
$business_id = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$product_id  = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity    = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$notes       = trim($_POST['notes'] ?? '');

// Memvalidasi input agar ID bisnis, ID produk, dan jumlah stok keluar bernilai minimal 1
if (!$business_id || !$product_id || !$quantity || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak valid'
    ]);
    exit;
}

try {
    // Memulai transaksi database agar pembaruan stok dan pencatatan riwayat tersimpan atomik
    mysqli_begin_transaction($conn);

    // Menyiapkan query untuk memeriksa keberadaan produk pada bisnis aktif
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, product_name, current_stock, selling_price
        FROM products
        WHERE id = ?
        AND business_id = ?
        LIMIT 1"
    );

    // Eksekusi prepared statement pencarian produk
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $business_id);
    mysqli_stmt_execute($stmt);

    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Menangani kondisi jika data produk tidak ditemukan
    if (!$product) {
        throw new Exception('Produk tidak ditemukan');
    }

    // Memvalidasi kecukupan stok produk sebelum transaksi barang keluar
    if ((int)$product['current_stock'] < $quantity) {
        echo json_encode([
            'success' => false,
            'message' => 'Stok tidak mencukupi'
        ]);
        exit;
    }

    // Menyimpan jumlah stok awal sebelum pengurangan dilakukan
    $stock_before = (int)$product['current_stock'];

    // Mengurangi stok awal dengan jumlah barang keluar
    $stock_after = $stock_before - $quantity;

    // Menghitung akumulasi total nilai transaksi penjualan
    $total_amount = $quantity * $product['selling_price'];

    // Menyiapkan query untuk memperbarui sisa stok produk terkini
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE products
        SET current_stock = ?
        WHERE id = ?
        AND business_id = ?"
    );

    // Eksekusi pembaruan sisa stok produk
    mysqli_stmt_bind_param($stmt, "iii", $stock_after, $product_id, $business_id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal update stok');
    }

    // Menyiapkan query untuk mencatat riwayat transaksi stok keluar
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

    // Eksekusi penyimpanan bukti riwayat transaksi ke database
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

    // Konfirmasi dan simpan seluruh perubahan transaksi database jika sukses
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Stok keluar berhasil disimpan'
    ]);

} catch (Exception $e) {
    // Membatalkan semua perubahan database (rollback) jika terjadi kesalahan
    mysqli_rollback($conn);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}