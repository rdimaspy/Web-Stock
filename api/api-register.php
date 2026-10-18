<?php
// Memuat file konfigurasi koneksi database & pengelola bahasa
include "../config/database.php";
require_once __DIR__ . '/../includes/language.php';

// Memastikan permintaan dikirimkan khusus melalui metode POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../register.php");
    exit;
}

// Menangkap dan membersihkan input nama, email, serta password dari form
$name     = trim($_POST["name"] ?? "");
$email    = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Memvalidasi agar seluruh kolom formulir terisi
if ($name === "" || $email === "" || $password === "") {
    $msg = json_encode(__('all_fields_required'));
    echo "<script>
        alert({$msg});
        window.location.href='../register.php';
    </script>";
    exit;
}

// Memvalidasi kesesuaian format alamat email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $msg = json_encode(__('invalid_email_format'));
    echo "<script>
        alert({$msg});
        window.location.href='../register.php';
    </script>";
    exit;
}

// Validasi panjang password harus tepat 6 karakter
if (strlen($password) !== 6) {
    $msg = json_encode(__('password_exact_6'));
    echo "<script>
        alert({$msg});
        window.location.href='../register.php';
    </script>";
    exit;
}

// Menyiapkan prepared statement untuk mengecek keberadaan email pada database
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM users WHERE email = ? LIMIT 1"
);

// Eksekusi prepared statement pengecekan email
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// Menolak pendaftaran jika email sudah terdaftar sebelumnya
if (mysqli_num_rows($result) > 0) {
    $msg = json_encode(__('email_already_used'));
    echo "<script>
        alert({$msg});
        window.location.href='../register.php';
    </script>";
    exit;
}

// Mengenkripsi password pengguna dengan algoritma hash bawaan PHP
$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// Menyiapkan query untuk menyimpan data akun pengguna baru
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users (name, email, password)
        VALUES (?, ?, ?)"
);

// Eksekusi penyimpanan data pengguna ke database
mysqli_stmt_bind_param(
    $stmt,
    "sss",
    $name,
    $email,
    $hashedPassword
);

// Menampilkan pemberitahuan dan mengalihkan pengguna ke halaman login jika sukses
if (mysqli_stmt_execute($stmt)) {
    $msg = json_encode(__('account_created'));
    echo "<script>
        alert({$msg});
        window.location.href='../login.php';
    </script>";
    exit;
}

// Menangani kegagalan saat proses pendaftaran akun
$msgFail = json_encode(__('account_create_failed'));
echo "<script>
    alert({$msgFail});
    window.location.href='../register.php';
</script>";

exit;
?>