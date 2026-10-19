<?php
// Memulai sesi dan memuat file koneksi database & pengelola bahasa
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';

// Menentukan format respons berupa JSON UTF-8
header('Content-Type: application/json; charset=utf-8');

// Memastikan permintaan dikirimkan khusus melalui metode POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => __('method_not_allowed')
    ]);
    exit;
}

// Menangkap dan membersihkan input nama, email, serta password
$name     = trim($_POST["name"] ?? "");
$email    = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Memvalidasi agar seluruh kolom terisi
if ($name === "" || $email === "" || $password === "") {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => __('all_fields_required')
    ]);
    exit;
}

// Memvalidasi kesesuaian format alamat email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => __('invalid_email_format')
    ]);
    exit;
}

// Validasi panjang password harus tepat 6 karakter
if (strlen($password) !== 6) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => __('password_exact_6')
    ]);
    exit;
}

// Menyiapkan prepared statement pengecekan email
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Menolak pendaftaran jika email sudah terdaftar
if (mysqli_num_rows($result) > 0) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => __('email_already_used')
    ]);
    exit;
}

// Mengenkripsi password pengguna
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Menyiapkan query simpan data pengguna baru
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users (name, email, password) VALUES (?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashedPassword);

// Mengembalikan respon JSON berdasarkan status simpan
if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'status'  => 'success',
        'message' => __('account_created')
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => __('account_create_failed')
    ]);
}