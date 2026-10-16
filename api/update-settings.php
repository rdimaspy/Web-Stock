<?php
// Validasi sesi login dan hak akses bisnis pengguna
require_once __DIR__ . '/../includes/auth_business.php';

// Memuat kamus bahasa jika belum didefinisikan sebelumnya
if (!isset($lang)) {
    require_once __DIR__ . '/../includes/language.php';
}

// Menentukan format respons berupa JSON
header('Content-Type: application/json');

// Memastikan request hanya dikirim melalui metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Membersihkan input nama bisnis, mata uang, dan nama pengguna
$business_name = trim($_POST['business_name'] ?? '');
$currency      = trim($_POST['currency'] ?? 'IDR');
$name          = trim($_POST['name'] ?? '');

// Memvalidasi agar kolom wajib tidak dikirim dalam kondisi kosong
if (empty($business_name) || empty($name)) {
    echo json_encode(['status' => 'error', 'message' => 'Nama bisnis dan nama pengguna tidak boleh kosong.']);
    exit;
}

// Memperbarui informasi nama bisnis dan mata uang pada database
$stmtBusiness = mysqli_prepare($conn, "UPDATE businesses SET business_name = ?, currency = ? WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmtBusiness, "ssii", $business_name, $currency, $business_id, $_SESSION['user_id']);
$updateBusinessSuccess = mysqli_stmt_execute($stmtBusiness);

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
        'message' => (isset($lang) && $lang === 'en') ? 'Settings updated successfully!' : 'Pengaturan berhasil diperbarui!'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => (isset($lang) && $lang === 'en') ? 'Failed to update settings.' : 'Gagal memperbarui pengaturan database.'
    ]);
}