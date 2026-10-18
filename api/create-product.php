<?php
// Menentukan header respon dalam format JSON
header('Content-Type: application/json');

// Memulai sesi login dan memuat file koneksi database & pengelola bahasa
session_start();
include "../config/database.php";
require_once __DIR__ . '/../includes/language.php';

// Memeriksa status autentikasi pengguna
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status"  => "error",
        "message" => __('unauthorized')
    ]);
    exit;
}

// Menangkap dan mengamankan input data form produk
$business_id  = $_POST['business_id'] ?? null;
$product_name = trim($_POST['product_name'] ?? '');
$product_code = trim($_POST['product_code'] ?? '');

// Memastikan parameter utama (bisnis, nama produk, dan kode produk) tidak kosong
if (!$business_id || !$product_name || $product_code === '') {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => __('product_name_code_req')
    ]);
    exit;
}

// Memeriksa hak akses dan keberadaan bisnis milik pengguna
$stmt = mysqli_prepare($conn, "SELECT id FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    http_response_code(403);
    echo json_encode([
        "status"  => "error",
        "message" => __('business_unauthorized')
    ]);
    exit;
}

// Cek duplikasi kode produk pada bisnis yang sama
$stmtCheck = mysqli_prepare($conn, "SELECT id FROM products WHERE business_id = ? AND product_code = ? LIMIT 1");
mysqli_stmt_bind_param($stmtCheck, "is", $business_id, $product_code);
mysqli_stmt_execute($stmtCheck);
if (mysqli_num_rows(mysqli_stmt_get_result($stmtCheck)) > 0) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => __('duplicate_product_code')
    ]);
    exit;
}

// Validasi kategori produk
$category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

// Format ulang input harga beli dan harga jual (mengganti koma dengan titik)
$purchase_price = trim($_POST['purchase_price'] ?? '0');
$selling_price  = trim($_POST['selling_price'] ?? '0');
$purchase_price = str_replace(',', '.', $purchase_price);
$selling_price  = str_replace(',', '.', $selling_price);

// Validasi nominal harga beli dan harga jual agar tidak bernilai negatif
if (
    !is_numeric($purchase_price) ||
    !is_numeric($selling_price) ||
    (float) $purchase_price < 0 ||
    (float) $selling_price < 0
) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => __('invalid_price')
    ]);
    exit;
}

$purchase_price = (float) $purchase_price;
$selling_price  = (float) $selling_price;

// Menangkap data stok awal, tanggal kadaluarsa, dan catatan produk
$initial_stock   = $_POST['initial_stock'] ?? 0;
$expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
$notes           = $_POST['notes'] ?? null;

// Menyiapkan prepared statement untuk menyimpan data produk ke database
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO products
    (
        business_id,
        category_id,
        product_name,
        product_code,
        purchase_price,
        selling_price,
        initial_stock,
        current_stock,
        expiration_date,
        notes
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

// Parameeter
mysqli_stmt_bind_param(
    $stmt,
    "iissddisss",
    $business_id,
    $category_id,
    $product_name,
    $product_code,
    $purchase_price,
    $selling_price,
    $initial_stock,
    $initial_stock,
    $expiration_date,
    $notes
);

// Eksekusi simpan produk dan penanganan unggah foto produk
if (mysqli_stmt_execute($stmt)) {
    $product_id = mysqli_insert_id($conn);

    // Memproses unggahan foto produk jika dikirimkan oleh pengguna
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $uploadDir = "../uploads/products/" . $product_id . "/";

        // Membuat direktori penyimpanan foto produk jika belum tersedia
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename  = time() . "." . $extension;
        $target    = $uploadDir . $filename;

        // Pindahkan file foto sementara dan perbarui record nama foto produk di database
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $update = mysqli_prepare($conn, "UPDATE products SET photo = ? WHERE id = ?");
            mysqli_stmt_bind_param($update, "si", $filename, $product_id);
            mysqli_stmt_execute($update);
        }
    }

    // Mengembalikan respon sukses pembuatan produk baru
    echo json_encode([
        "status"     => "success",
        "message"    => __('product_create_success'),
        "product_id" => $product_id
    ]);

    exit;
}