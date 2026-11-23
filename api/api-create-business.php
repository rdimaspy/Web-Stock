<?php
// Endpoint API Pembuatan Bisnis / Toko Baru
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

$user_id       = requireApiAuth();
$business_name = trim($_POST['business_name'] ?? '');
$currency      = trim($_POST['currency'] ?? 'IDR');
$notes         = trim($_POST['notes'] ?? '');

if ($business_name === '') {
    jsonResponse('error', __('business_name_user_required'), 400);
}

// Simpan data bisnis baru
$stmt = mysqli_prepare($conn, "INSERT INTO businesses (user_id, business_name, currency, notes) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "isss", $user_id, $business_name, $currency, $notes);
if (!mysqli_stmt_execute($stmt)) {
    jsonResponse('error', __('business_create_failed'), 500);
}

$business_id = mysqli_insert_id($conn);

// Proses unggahan foto bisnis (opsional)
if (isset($_FILES['photo'])) {
    $uploadDir = __DIR__ . '/../uploads/businesses/' . $business_id . '/';
    $photoName = uploadPhotoFile($_FILES['photo'], $uploadDir);
    if ($photoName) {
        $stmtPhoto = mysqli_prepare($conn, "UPDATE businesses SET photo = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmtPhoto, "si", $photoName, $business_id);
        mysqli_stmt_execute($stmtPhoto);
    }
}

jsonResponse('success', __('business_create_success'));
