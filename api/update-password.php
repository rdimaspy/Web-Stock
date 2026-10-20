<?php
// Memulai sesi & penanganan respon JSON murni
session_start();
header('Content-Type: application/json; charset=utf-8');

// Muat koneksi database dan pengelola bahasa
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';

// Validasi autentikasi pengguna
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => __('session_expired')
    ]);
    exit;
}

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => __('method_not_allowed')
    ]);
    exit;
}

$user_id       = (int)$_SESSION['user_id'];
$business_id   = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);
$business_name = trim($_POST['business_name'] ?? '');
$name          = trim($_POST['name'] ?? '');
$notes         = trim($_POST['notes'] ?? '');

// Ambil input kata sandi dari formulir
$current_password = $_POST['current_password'] ?? '';
$new_password     = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validasi input wajib nama bisnis dan nama pengguna
if (empty($business_name) || empty($name)) {
    echo json_encode([
        'status'  => 'error',
        'message' => __('business_name_user_required')
    ]);
    exit;
}

// Validasi hak akses bisnis milik pengguna aktif
$stmtBiz = mysqli_prepare($conn, "SELECT id FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmtBiz, "ii", $business_id, $user_id);
mysqli_stmt_execute($stmtBiz);
$bizResult = mysqli_stmt_get_result($stmtBiz);

if (!mysqli_fetch_assoc($bizResult)) {
    echo json_encode([
        'status'  => 'error',
        'message' => __('business_unauthorized')
    ]);
    exit;
}

// LOGIKA PEMERIKSAAN KATA SANDI (Hanya dijalankan jika salah satu kolom kata sandi diisi)
$update_password_flag = false;
if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
    
    if (empty($current_password)) {
        echo json_encode(['status' => 'error', 'message' => __('current_password_req')]);
        exit;
    }
    
    if (empty($new_password) || empty($confirm_password)) {
        echo json_encode(['status' => 'error', 'message' => __('new_password_req')]);
        exit;
    }

    if (mb_strlen($new_password) !== 6) {
        echo json_encode(['status' => 'error', 'message' => __('password_exact_6')]);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => __('password_mismatch')]);
        exit;
    }

    // Verifikasi kata sandi lama dari database
    $stmtPass = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtPass, "i", $user_id);
    mysqli_stmt_execute($stmtPass);
    $resPass = mysqli_stmt_get_result($stmtPass);
    $userAcc = mysqli_fetch_assoc($resPass);

    if (!$userAcc || !password_verify($current_password, $userAcc['password'])) {
        echo json_encode(['status' => 'error', 'message' => __('wrong_current_password')]);
        exit;
    }

    $update_password_flag = true;
}

// 1. Update data profil pengguna
$stmtUser = mysqli_prepare($conn, "UPDATE users SET name = ? WHERE id = ?");
mysqli_stmt_bind_param($stmtUser, "si", $name, $user_id);
mysqli_stmt_execute($stmtUser);

// 2. Update data bisnis
$stmtUpdateBiz = mysqli_prepare($conn, "UPDATE businesses SET business_name = ?, notes = ? WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmtUpdateBiz, "ssii", $business_name, $notes, $business_id, $user_id);
mysqli_stmt_execute($stmtUpdateBiz);

// 3. Update kata sandi pengguna (jika diisi dan valid)
if ($update_password_flag) {
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmtUpPass = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmtUpPass, "si", $new_hash, $user_id);
    mysqli_stmt_execute($stmtUpPass);
}

// 4. Penanganan unggah foto bisnis (jika ada file diunggah)
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['photo']['tmp_name'];
    $fileName    = $_FILES['photo']['name'];
    $fileSize    = $_FILES['photo']['size'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (in_array($fileExtension, $allowedExtensions, true) && $fileSize <= 2 * 1024 * 1024) {
        $uploadDir = __DIR__ . '/../uploads/businesses/' . $business_id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = 'logo_' . time() . '.' . $fileExtension;
        $destPath    = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $stmtPhoto = mysqli_prepare($conn, "UPDATE businesses SET photo = ? WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmtPhoto, "sii", $newFileName, $business_id, $user_id);
            mysqli_stmt_execute($stmtPhoto);
        }
    }
}

echo json_encode([
    'status'  => 'success',
    'message' => __('settings_update_success')
]);
exit;