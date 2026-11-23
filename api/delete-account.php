<?php
// Endpoint API Penghapusan Akun Pengguna Secara Permanen
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

$user_id = requireApiAuth();

$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);

if (!mysqli_stmt_execute($stmt)) {
    jsonResponse('error', __('account_delete_failed'), 500);
}

session_unset();
session_destroy();
jsonResponse('success', __('account_delete_success'));
