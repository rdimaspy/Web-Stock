<?php

session_start();

include "../config/database.php";

// cek login
if(!isset($_SESSION['user_id'])){
    header("Location: ../login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$business_name = $_POST['business_name'];
$currency = $_POST['currency'];
$notes = $_POST['notes'];

// Simpan bisnis terlebih dahulu tanpa foto
$query = mysqli_query($conn, "INSERT INTO businesses (user_id, business_name, currency, notes) VALUES ('$user_id', '$business_name', '$currency', '$notes')");

if(!$query){
    echo "<script>alert('Gagal membuat bisnis'); window.location.href='../create-business.php';</script>";
    exit;
}

// Ambil ID bisnis yang baru dibuat
$business_id = mysqli_insert_id($conn);
$photo_name = null;

// foto opsional
if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
    $photo = $_FILES['photo'];
    $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);

    // nama file baru
    $photo_name = time() . "." . $extension;

    // buat folder bisnis
    $folder = "../uploads/businesses/" . $business_id;

    if(!file_exists($folder)){
        mkdir($folder, 0777, true);
    }

    // upload file
    $upload_result = move_uploaded_file(
        $photo['tmp_name'],
        $folder . "/" . $photo_name
    );

    // update database
    $update = mysqli_query(
    $conn,

        "UPDATE businesses

        SET photo='$photo_name'

        WHERE id='$business_id'"
    );

}

echo "<script>alert('Bisnis berhasil dibuat'); window.location.href='../businesses.php';</script>";

?>