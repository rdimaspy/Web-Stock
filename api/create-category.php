<?php
// Menentukan header respon dalam format JSON
header('Content-Type: application/json');

// Memulai sesi login dan memuat file koneksi database
session_start();
require_once __DIR__ . '/../config/database.php';

// Fungsi helper untuk mengembalikan respon JSON dan menghentikan eksekusi script
function respond($status, $message, $data = []) {
    echo json_encode(array_merge([
        'status'  => $status,
        'message' => $message
    ], $data));
    exit;
}

// Memeriksa status autentikasi pengguna
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    respond('error', 'Sesi login berakhir. Silakan login kembali.');
}

// Validasi input ID bisnis dan nama kategori dari request POST
$business_id   = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$category_name = trim($_POST['category_name'] ?? '');

// Menolak permintaan jika ID bisnis tidak valid atau nama kategori kosong
if (!$business_id || $category_name === '') {
    respond('error', 'Nama kategori wajib diisi.');
}

// Memeriksa apakah pengguna memiliki hak akses terhadap bisnis terkait
$stmt = mysqli_prepare(
    $conn,
    'SELECT id FROM businesses WHERE id = ? AND user_id = ? LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'ii', $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

// Menolak akses jika bisnis tidak ditemukan atau bukan milik pengguna
if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    http_response_code(403);
    respond('error', 'Bisnis tidak ditemukan atau kamu tidak memiliki akses.');
}

// Memeriksa duplikasi nama kategori dalam bisnis yang sama (case-insensitive)
$stmt = mysqli_prepare(
    $conn,
    'SELECT id FROM categories WHERE business_id = ? AND LOWER(category_name) = LOWER(?) LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'is', $business_id, $category_name);
mysqli_stmt_execute($stmt);

// Mengembalikan pesan error jika kategori sudah terdaftar sebelumnya
if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    respond('error', 'Kategori tersebut sudah ada.');
}

// Menyimpan data kategori baru ke database
$stmt = mysqli_prepare(
    $conn,
    'INSERT INTO categories (business_id, category_name) VALUES (?, ?)'
);
mysqli_stmt_bind_param($stmt, 'is', $business_id, $category_name);

// Menangani kegagalan saat proses penyimpanan ke database
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    respond('error', 'Kategori gagal disimpan.');
}

// Mengembalikan respon sukses beserta data kategori yang baru ditambahkan
respond('success', 'Kategori berhasil ditambahkan.', [
    'category' => [
        'id'            => mysqli_insert_id($conn),
        'category_name' => $category_name
    ]
]);