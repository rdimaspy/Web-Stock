<?php
// Endpoint API Penghapusan Toko / Bisnis Milik Pengguna
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

$user_id     = requireApiAuth();
$business_id = (int) ($_POST['business_id'] ?? 0);

if ($business_id <= 0) {
    jsonResponse('error', __('invalid_business'), 400);
}

$stmt = mysqli_prepare($conn, "DELETE FROM businesses WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $business_id, $user_id);

if (!mysqli_stmt_execute($stmt)) {
    jsonResponse('error', __('business_delete_failed'), 500);
}

if (mysqli_stmt_affected_rows($stmt) === 0) {
    jsonResponse('error', __('business_unauthorized'), 403);
}

jsonResponse('success', __('business_delete_success'));
