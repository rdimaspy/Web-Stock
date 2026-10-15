<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "stock_management";
$port = 3307;

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