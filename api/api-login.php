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

// Menangkap dan membersihkan input email serta password dari form
$email    = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Memvalidasi agar input email dan password tidak kosong
if ($email === "" || $password === "") {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => __('email_password_required')
    ]);
    exit;
}

// Menyiapkan prepared statement untuk mencari data pengguna berdasarkan email
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1"
);

// Eksekusi prepared statement pencarian akun pengguna
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

// Memeriksa keberadaan akun dan memverifikasi kesesuaian password
if (!$user || !password_verify($password, $user["password"])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => __('invalid_credentials')
    ]);
    exit;
}

// Regenerasi ID sesi demi keamanan
session_regenerate_id(true);

// Menyimpan informasi profil utama pengguna ke sesi
$_SESSION["user_id"]    = $user["id"];
$_SESSION["user_name"]  = $user["name"];
$_SESSION["user_email"] = $user["email"];

// Mengembalikan respon JSON sukses
echo json_encode([
    'status'  => 'success',
    'message' => __('login_success')
]);