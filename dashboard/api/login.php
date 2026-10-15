<?php

session_start();

include "../config/database.php";

// Pastikan form dikirim menggunakan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.html");
    exit;
}

// Ambil input
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Validasi input
if ($email === "" || $password === "") {
    echo "<script>
        alert('Email dan password wajib diisi');
        window.location.href='../login.html';
    </script>";
    exit;
}

// Cari user berdasarkan email
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, password
        FROM users
        WHERE email = ?
        LIMIT 1"
    );

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Periksa akun dan password
if (!$user || !password_verify($password, $user["password"])) {
    echo "<script>
        alert('Email atau password salah');
        window.location.href='../login.html';
    </script>";
    exit;
}

// Buat session setelah login berhasil
session_regenerate_id(true);

$_SESSION["user_id"] = $user["id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];

// Redirect ke daftar bisnis
header("Location: ../businesses.php");
exit;
?>