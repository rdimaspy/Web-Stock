<?php
// Validasi sesi login dan hak akses bisnis pengguna, dan pengelola bahasa
require_once __DIR__ . '/../includes/auth_business.php';
require_once __DIR__ . '/../includes/language.php';

// Menentukan format respons berupa JSON
header('Content-Type: application/json');

// Memastikan request hanya dikirim melalui metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => __('method_not_allowed')]);
    exit;
}

// Membersihkan input nama bisnis, catatan bisnis, dan nama pengguna
$business_name = trim($_POST['business_name'] ?? '');
$notes         = trim($_POST['notes'] ?? '');
$name          = trim($_POST['name'] ?? '');

// Memvalidasi agar kolom wajib tidak dikirim dalam kondisi kosong
if (empty($business_name) || empty($name)) {
    echo json_encode(['status' => 'error', 'message' => __('business_name_user_required')]);
    exit;
}

// Memperbarui informasi nama bisnis dan catatan bisnis pada database
$stmtBusiness = mysqli_prepare($conn, "UPDATE businesses SET business_name = ?, notes = ? WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmtBusiness, "ssii", $business_name, $notes, $business_id, $_SESSION['user_id']);
$updateBusinessSuccess = mysqli_stmt_execute($stmtBusiness);

// HANYA UPDATE FOTO JIKA USER MEMILIH BERKAS BARU
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/../uploads/businesses/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($extension, $allowedExtensions, true)) {
        $filename = time() . "." . $extension;
        $target   = $uploadDir . $filename;

        // Pindahkan file foto baru
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            // Update nama file foto baru di database
            $stmtPhoto = mysqli_prepare($conn, "UPDATE businesses SET photo = ? WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmtPhoto, "sii", $filename, $business_id, $_SESSION['user_id']);
            mysqli_stmt_execute($stmtPhoto);
        }
    }
}

// Memperbarui nama lengkap profil pengguna pada database
$stmtUser = mysqli_prepare($conn, "UPDATE users SET name = ? WHERE id = ?");
mysqli_stmt_bind_param($stmtUser, "si", $name, $_SESSION['user_id']);
$updateUserSuccess = mysqli_stmt_execute($stmtUser);

// Memperbarui nama pada variabel sesi login agar sinkron di seluruh halaman
if ($updateUserSuccess) {
    $_SESSION['user_name'] = $name;
}

// Mengirimkan respons hasil eksekusi pembaruan data
if ($updateBusinessSuccess && $updateUserSuccess) {
    echo json_encode([
        'status' => 'success',
        'message' => __('settings_update_success')
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => __('settings_update_failed')
    ]);
}