<?php

$host = getenv('DB_HOST') ?: 'db';
$username = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$database = getenv('DB_NAME') ?: 'stock_management';
$port = (int) (getenv('DB_PORT') ?: 3306);

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database,
    $port
);

// cek koneksi
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

?>