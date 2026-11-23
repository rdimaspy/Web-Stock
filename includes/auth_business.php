<?php
// Validasi sesi login dan hak akses bisnis aktif untuk seluruh halaman dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/helpers.php';

$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);
if (!$business_id || $business_id < 1) {
    header("Location: ../businesses.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, business_name, currency, photo, notes FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$business = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$business) {
    http_response_code(404);
    exit("Bisnis tidak ditemukan atau tidak memiliki akses.");
}
