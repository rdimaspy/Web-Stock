<?php
// Memulai sesi untuk menyimpan data otentikasi login pengguna
session_start();

// Memuat file konfigurasi koneksi database
include "../config/database.php";

// Memastikan permintaan dikirimkan khusus melalui metode POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

// Menangkap dan membersihkan input email serta password dari form
$email    = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Memvalidasi agar input email dan password tidak dikirimkan dalam kondisi kosong
if ($email === "" || $password === "") {
    echo "<script>
        alert('Email dan password wajib diisi');
        window.location.href='../login.php';
    </script>";
    exit;
}

// Menyiapkan prepared statement untuk mencari data pengguna berdasarkan email
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, password
        FROM users
        WHERE email = ?
        LIMIT 1"
);

// Eksekusi prepared statement pencarian akun pengguna
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

// Memeriksa keberadaan akun dan memverifikasi kesesuaian hash password
if (!$user || !password_verify($password, $user["password"])) {
    echo "<script>
        alert('Email atau password salah');
        window.location.href='../login.php';
    </script>";
    exit;
}

// Regenerasi ID sesi untuk mencegah serangan session fixation setelah login berhasil
session_regenerate_id(true);

// Menyimpan informasi profil utama pengguna ke dalam variabel sesi
$_SESSION["user_id"]    = $user["id"];
$_SESSION["user_name"]  = $user["name"];
$_SESSION["user_email"] = $user["email"];

// Mengalihkan pengguna ke halaman daftar bisnis setelah berhasil autentikasi
header("Location: ../businesses.php");
exit;
?>