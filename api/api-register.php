<?php
// Endpoint API Pendaftaran Akun Pengguna Baru
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', __('method_not_allowed'), 405);
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    jsonResponse('error', __('all_fields_required'), 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse('error', __('invalid_email_format'), 400);
}

if (strlen($password) !== 6) {
    jsonResponse('error', __('password_exact_6'), 400);
}

// Pastikan email belum pernah terdaftar
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
    jsonResponse('error', __('email_already_used'), 400);
}

// Simpan pengguna baru dengan password terenkripsi
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashedPassword);

if (!mysqli_stmt_execute($stmt)) {
    jsonResponse('error', __('account_create_failed'), 500);
}

jsonResponse('success', __('account_created'));
