<?php
// Endpoint API Pembaruan Pengaturan Toko, Profil Akun, & Kata Sandi
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/helpers.php';

$user_id       = requireApiAuth();
$business_id   = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$business_name = trim($_POST['business_name'] ?? '');
$name          = trim($_POST['name'] ?? '');
$notes         = trim($_POST['notes'] ?? '');

$current_password = $_POST['current_password'] ?? '';
$new_password     = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($business_name === '' || $name === '') {
    jsonResponse('error', __('business_name_user_required'), 400);
}

$existingBiz = verifyBusinessAccess($conn, (int) $business_id, $user_id);

// Validasi perubahan kata sandi jika kolom password diisi
$update_password_flag = false;
if ($current_password !== '' || $new_password !== '' || $confirm_password !== '') {
    if ($current_password === '') {
        jsonResponse('error', __('current_password_req'), 400);
    }
    if ($new_password === '' || $confirm_password === '') {
        jsonResponse('error', __('new_password_req'), 400);
    }
    if (mb_strlen($new_password) !== 6) {
        jsonResponse('error', __('password_exact_6'), 400);
    }
    if ($new_password !== $confirm_password) {
        jsonResponse('error', __('password_mismatch'), 400);
    }

    $stmtPass = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtPass, "i", $user_id);
    mysqli_stmt_execute($stmtPass);
    $userAcc = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtPass));

    if (!$userAcc || !password_verify($current_password, $userAcc['password'])) {
        jsonResponse('error', __('wrong_current_password'), 400);
    }
    $update_password_flag = true;
}

// 1. Update nama profil pengguna
$stmtUser = mysqli_prepare($conn, "UPDATE users SET name = ? WHERE id = ?");
mysqli_stmt_bind_param($stmtUser, "si", $name, $user_id);
mysqli_stmt_execute($stmtUser);
$_SESSION['user_name'] = $name;

// 2. Update nama & catatan bisnis
$stmtUpdateBiz = mysqli_prepare($conn, "UPDATE businesses SET business_name = ?, notes = ? WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmtUpdateBiz, "ssii", $business_name, $notes, $business_id, $user_id);
mysqli_stmt_execute($stmtUpdateBiz);

// 3. Update kata sandi jika diminta
if ($update_password_flag) {
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmtUpPass = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmtUpPass, "si", $new_hash, $user_id);
    mysqli_stmt_execute($stmtUpPass);
}

// 4. Unggah foto logo bisnis baru jika ada
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/businesses/' . $business_id . '/';
    $newFileName = uploadPhotoFile($_FILES['photo'], $uploadDir, 'logo_', 2);

    if ($newFileName) {
        if (!empty($existingBiz['photo']) && file_exists($uploadDir . $existingBiz['photo'])) {
            @unlink($uploadDir . $existingBiz['photo']);
        }
        $stmtPhoto = mysqli_prepare($conn, "UPDATE businesses SET photo = ? WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmtPhoto, "sii", $newFileName, $business_id, $user_id);
        mysqli_stmt_execute($stmtPhoto);
    }
}

jsonResponse('success', __('settings_update_success'));
