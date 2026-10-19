<?php
// Memulai sesi untuk mengakses data otentikasi pengguna
session_start();

// Memuat file konfigurasi koneksi database & pengelola bahasa
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';

// Menentukan format respons berupa JSON UTF-8
header('Content-Type: application/json; charset=utf-8');

// Memeriksa status login pengguna, kembalikan respons error 401 jika belum terautentikasi
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => __('session_expired')
    ]);
    exit;
}

// Menangkap data inputan dari form pembuatan bisnis
$user_id       = $_SESSION['user_id'];
$business_name = trim($_POST['business_name'] ?? '');
$currency      = trim($_POST['currency'] ?? 'IDR');
$notes         = trim($_POST['notes'] ?? '');

// Validasi nama bisnis tidak boleh kosong
if (empty($business_name)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => __('business_name_user_required')
    ]);
    exit;
}

// Menyimpan data bisnis baru ke database menggunakan prepared statement demi keamanan
$stmt = mysqli_prepare($conn, "INSERT INTO businesses (user_id, business_name, currency, notes) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "isss", $user_id, $business_name, $currency, $notes);
$query = mysqli_stmt_execute($stmt);

// Menangani kegagalan saat menyimpan data bisnis ke database
if (!$query) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => __('business_create_failed')
    ]);
    exit;
}

// Mengambil ID bisnis yang baru saja berhasil dimasukkan ke database
$business_id = mysqli_insert_id($conn);

// Memproses unggahan foto logo/sampul bisnis jika tersedia (opsional)
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photo     = $_FILES['photo'];
    $extension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
    $allowed   = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($extension, $allowed, true)) {
        // Membuat nama file foto unik berdasarkan timestamp
        $photo_name = time() . "." . $extension;

        // Menentukan lokasi folder penyimpanan foto bisnis
        $uploadDir = __DIR__ . "/../uploads/businesses/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $target = $uploadDir . $photo_name;

        // Memindahkan file foto sementara ke direktori target
        if (move_uploaded_file($photo['tmp_name'], $target)) {
            // Memperbarui nama file foto pada record bisnis di database
            $stmtPhoto = mysqli_prepare($conn, "UPDATE businesses SET photo = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmtPhoto, "si", $photo_name, $business_id);
            mysqli_stmt_execute($stmtPhoto);
        }
    }
}

// Mengembalikan respons sukses
echo json_encode([
    'status'  => 'success',
    'message' => __('business_create_success')
]);