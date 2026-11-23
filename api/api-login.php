<?php
// Endpoint API Autentikasi Login Pengguna
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', __('method_not_allowed'), 405);
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    jsonResponse('error', __('email_password_required'), 400);
}

// Cari akun pengguna berdasarkan email
$stmt = mysqli_prepare($conn, "SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse('error', __('invalid_credentials'), 401);
}

// Regenerasi ID sesi & simpan data pengguna aktif
session_regenerate_id(true);
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_name']  = $user['name'];
$_SESSION['user_email'] = $user['email'];

jsonResponse('success', __('login_success'));
