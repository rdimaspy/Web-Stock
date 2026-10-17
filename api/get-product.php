<?php
// Menentukan format respons server sebagai JSON
header('Content-Type: application/json');

// Memuat file konfigurasi koneksi database
require_once '../config/database.php';

// Memeriksa keberadaan parameter ID produk pada query string GET
if (!isset($_GET['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "ID produk tidak ditemukan"
    ]);
    exit;
}

// Konversi nilai ID produk ke tipe data integer
$id = (int) $_GET['id'];

// Mengambil data produk dari database menggunakan prepared statement demi keamanan
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Memeriksa apakah data produk ditemukan di database
if (mysqli_num_rows($result) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Produk tidak ditemukan"
    ]);
    exit;
}

// Mengambil baris data produk sebagai array
$product = mysqli_fetch_assoc($result);

// Mengembalikan respons sukses beserta detail data produk
echo json_encode([
    "status" => "success",
    "data" => $product
]);