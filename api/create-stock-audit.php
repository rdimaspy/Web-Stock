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

// Menangkap dan memvalidasi input parameter dari form audit
$business_id    = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$product_id     = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$physical_stock = filter_input(INPUT_POST, 'physical_stock', FILTER_VALIDATE_INT);
$notes          = trim($_POST['notes'] ?? '');

// Memvalidasi agar nilai ID bisnis, ID produk, dan jumlah stok fisik valid
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
    // Memulai transaksi database agar perubahan stok dan riwayat tersimpan secara sinkron
    mysqli_begin_transaction($conn);

    // Menyiapkan query untuk mengambil data produk dan stok terkini
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, product_name, current_stock
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

    // Mengambil nilai stok sebelum proses audit dilakukan
    $stock_before = (int)$product['current_stock'];

    // Menghitung selisih antara stok hasil perhitungan fisik dan stok sistem
    $difference = $physical_stock - $stock_before;

    // Jika tidak terdapat selisih angka, batalkan perubahan transaksi database
    if ($difference === 0) {
        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Tidak ada perubahan stok'
        ]);
        exit;
    }

    // Menyiapkan query untuk memperbarui stok produk sesuai jumlah fisik terkini
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE products
        SET current_stock = ?
        WHERE id = ?
        AND business_id = ?"
    );

    // Eksekusi pembaruan jumlah stok produk
    mysqli_stmt_bind_param($stmt, "iii", $physical_stock, $product_id, $business_id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal update stok');
    }

    // Menyiapkan query untuk mencatat transaksi penyesuaian audit ke riwayat
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

    // Eksekusi penyimpanan catatan riwayat transaksi audit
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

    // Konfirmasi dan simpan seluruh perubahan transaksi database jika berhasil
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Audit stok berhasil disimpan'
    ]);

} catch (Exception $e) {
    // Batalkan seluruh perubahan database (rollback) jika terjadi kesalahan/error
    mysqli_rollback($conn);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}