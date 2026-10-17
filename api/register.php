<?php
// Memuat file konfigurasi koneksi database
include "../config/database.php";

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
    echo "<script>
        alert('Semua kolom wajib diisi');
        window.location.href='../register.php';
    </script>";
    exit;
}

// Memvalidasi kesesuaian format alamat email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>
        alert('Format email tidak valid');
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
    echo "<script>
        alert('Email sudah digunakan');
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
    echo "<script>
        alert('Akun berhasil dibuat');
        window.location.href='../login.php';
    </script>";
    exit;
}

// Menangani kegagalan saat proses pendaftaran akun
echo "<script>
    alert('Gagal membuat akun. Silakan coba lagi.');
    window.location.href='../register.php';
</script>";

exit;
?>