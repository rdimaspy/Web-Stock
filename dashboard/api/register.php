<?php

include "../config/database.php";

// Pastikan form dikirim menggunakan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../register.html");
    exit;
}

// Ambil input
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Validasi input
if ($name === "" || $email === "" || $password === "") {
    echo "<script>
        alert('Semua kolom wajib diisi');
        window.location.href='../register.html';
    </script>";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>
        alert('Format email tidak valid');
        window.location.href='../register.html';
    </script>";
    exit;
}

// Periksa apakah email sudah digunakan
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM users WHERE email = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo "<script>
        alert('Email sudah digunakan');
        window.location.href='../register.html';
    </script>";
    exit;
}

// Hash password
$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// Simpan akun
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users (name, email, password)
        VALUES (?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "sss",
    $name,
    $email,
    $hashedPassword
);

if (mysqli_stmt_execute($stmt)) {
    echo "<script>
        alert('Akun berhasil dibuat');
        window.location.href='../login.html';
    </script>";
    exit;
}

echo "<script>
    alert('Gagal membuat akun. Silakan coba lagi.');
    window.location.href='../register.html';
</script>";

exit;
?>