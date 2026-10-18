<?php
// Menentukan header respon dalam format JSON UTF-8
header('Content-Type: application/json; charset=utf-8');

// Memulai sesi login, mengatur zona waktu WIB, dan memuat file koneksi database
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';

// Fungsi helper untuk merender respon JSON dan menghentikan eksekusi script
function respond(string $status, string $message, int $httpCode = 200, array $extra = []): void
{
    http_response_code($httpCode);
    echo json_encode(array_merge([
        'status'  => $status,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// Memeriksa status otentikasi sesi login pengguna
if (!isset($_SESSION['user_id'])) {
    respond('error', __('session_expired'), 401);
}

// Memastikan request API dikirimkan khusus dengan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', __('method_not_allowed'), 405);
}

// Menangkap dan mengamankan input parameter
$userId     = (int) $_SESSION['user_id'];
$businessId = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$productId  = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity   = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$notesInput = $_POST['notes'] ?? '';
$notes      = is_string($notesInput) ? trim($notesInput) : '';
$notes      = $notes === '' ? null : $notes;

// Validasi keberadaan ID bisnis dan ID produk
if (!$businessId || $businessId < 1 || !$productId || $productId < 1) {
    respond('error', __('invalid_business_product'), 400);
}

// Validasi kuantitas barang masuk agar bernilai minimal 1
if ($quantity === false || $quantity === null || $quantity < 1) {
    respond('error', __('invalid_quantity'), 400);
}

try {
    // Memulai transaksi database agar proses update stok dan pencatatan riwayat bersifat atomik
    if (!mysqli_begin_transaction($conn)) {
        respond('error', __('transaction_start_failed'), 500);
    }

    // Mengambil dan mengunci record produk (FOR UPDATE) untuk menghindari kondisi race condition
    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            p.id,
            p.product_name,
            p.product_code,
            p.purchase_price,
            p.selling_price,
            p.current_stock
        FROM products AS p
        INNER JOIN businesses AS b ON b.id = p.business_id
        WHERE p.id = ?
            AND p.business_id = ?
            AND b.user_id = ?
        LIMIT 1
        FOR UPDATE"
    );

    if (!$stmt) {
        throw new RuntimeException(__('err_prep_product'));
    }

    // Eksekusi statement pencarian produk milik bisnis pengguna
    mysqli_stmt_bind_param($stmt, 'iii', $productId, $businessId, $userId);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // Menolak permintaan jika produk tidak terdaftar pada bisnis pengguna
    if (!$product) {
        mysqli_rollback($conn);
        respond('error', __('product_access_denied'), 404);
    }

    // Menghitung jumlah stok awal dan batas maksimum kapasitas
    $stockBefore = (int) $product['current_stock'];
    $maxStock    = 2147483647;

    // Memvalidasi agar penambahan stok tidak melebih kapasitas integer
    if ($quantity > $maxStock - $stockBefore) {
        mysqli_rollback($conn);
        respond('error', __('stock_limit_exceeded'), 400);
    }

    // Menyiapkan variabel kalkulasi stok baru, harga, serta waktu transaksi
    $stockAfter      = $stockBefore + $quantity;
    $purchasePrice   = (float) $product['purchase_price'];
    $sellingPrice    = (float) $product['selling_price'];
    $productName     = (string) $product['product_name'];
    $productCode     = $product['product_code'] !== null ? (string) $product['product_code'] : null;
    $transactionDate = date('Y-m-d H:i:s');

    // Menyiapkan query untuk memperbarui nilai stok produk
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE products
        SET current_stock = ?
        WHERE id = ? AND business_id = ?"
    );

    if (!$stmt) {
        throw new RuntimeException(__('err_prep_update'));
    }

    // Eksekusi pembaruan stok produk terkini
    mysqli_stmt_bind_param($stmt, 'iii', $stockAfter, $productId, $businessId);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) !== 1) {
        mysqli_stmt_close($stmt);
        throw new RuntimeException(__('err_update_stock'));
    }
    mysqli_stmt_close($stmt);

    // Menyiapkan query untuk mencatat bukti riwayat transaksi stok masuk
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO inventory_transactions
            (business_id, product_id, product_name, product_code,
            transaction_type, quantity, stock_before, stock_after,
            purchase_price, selling_price, total_amount, notes, transaction_date)
        VALUES (?, ?, ?, ?, 'stock_in', ?, ?, ?, ?, ?, 0.00, ?, ?)"
    );

    if (!$stmt) {
        throw new RuntimeException(__('err_prep_transaction'));
    }

    // Eksekusi penyimpanan catatan riwayat transaksi ke database
    mysqli_stmt_bind_param(
        $stmt,
        'iissiiiddss',
        $businessId,
        $productId,
        $productName,
        $productCode,
        $quantity,
        $stockBefore,
        $stockAfter,
        $purchasePrice,
        $sellingPrice,
        $notes,
        $transactionDate
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new RuntimeException(__('err_save_transaction'));
    }

    $transactionId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Konfirmasi dan commit seluruh transaksi database jika sukses
    if (!mysqli_commit($conn)) {
        throw new RuntimeException(__('err_commit_transaction'));
    }

    // Mengembalikan respon JSON sukses penambahan stok masuk
    respond('success', __('stock_in_save_success'), 200, [
        'transaction_id' => $transactionId,
        'product_id'     => $productId,
        'quantity_added' => $quantity,
        'stock_before'   => $stockBefore,
        'stock_after'    => $stockAfter,
    ]);
    } catch (Throwable $e) {
        @mysqli_rollback($conn);
        error_log('Stock In error: ' . $e->getMessage());
        respond('error', __('stock_in_save_failed'), 500);
    }