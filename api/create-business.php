<?php
// Memulai sesi untuk mengakses data otentikasi pengguna
session_start();

// Memuat file konfigurasi koneksi database
include "../config/database.php";

// Memeriksa status login pengguna, arahkan ke login jika belum terautentikasi
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

// Menangkap data inputan dari form pembuatan bisnis
$user_id       = $_SESSION['user_id'];
$business_name = $_POST['business_name'];
$currency      = $_POST['currency'];
$notes         = $_POST['notes'];

// Menyimpan data bisnis baru ke database terlebih dahulu tanpa foto
$query = mysqli_query($conn, "INSERT INTO businesses (user_id, business_name, currency, notes) VALUES ('$user_id', '$business_name', '$currency', '$notes')");

// Menangani kegagalan saat menyimpan data bisnis ke database
if (!$query) {
    echo "<script>alert('Gagal membuat bisnis'); window.location.href='../create-business.php';</script>";
    exit;
}

// Mengambil ID bisnis yang baru saja berhasil dimasukkan ke database
$business_id = mysqli_insert_id($conn);
$photo_name  = null;

// Memproses unggahan foto logo/sampul bisnis jika tersedia (opsional)
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $photo     = $_FILES['photo'];
    $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);

    // Membuat nama file foto unik berdasarkan stempel waktu (timestamp)
    $photo_name = time() . "." . $extension;

    // Menentukan lokasi folder penyimpanan foto khusus ID bisnis ini
    $folder = "../uploads/businesses/" . $business_id;

    // Membuat folder baru jika folder bisnis tersebut belum ada
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }

    // Memindahkan file foto dari direktori sementara ke folder tujuan
    $upload_result = move_uploaded_file(
        $photo['tmp_name'],
        $folder . "/" . $photo_name
    );

    // Memperbarui nama file foto pada record bisnis di database
    $update = mysqli_query(
        $conn,
        "UPDATE businesses SET photo='$photo_name' WHERE id='$business_id'"
    );
}

// Menampilkan pesan sukses dan mengalihkan pengguna kembali ke daftar bisnis
echo "<script>alert('Bisnis berhasil dibuat'); window.location.href='../businesses.php';</script>";

?>