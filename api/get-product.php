<?php

header('Content-Type: application/json');

require_once '../config/database.php';


if(!isset($_GET['id'])){

    echo json_encode([
        "status" => "error",
        "message" => "ID produk tidak ditemukan"
    ]);

    exit;

}


$id = (int) $_GET['id'];


$query = mysqli_query(
    $conn,
    "SELECT *
    FROM products
    WHERE id = $id"
);


if(mysqli_num_rows($query) == 0){

    echo json_encode([
        "status" => "error",
        "message" => "Produk tidak ditemukan"
    ]);

    exit;

}


$product = mysqli_fetch_assoc($query);


echo json_encode([
    "status" => "success",
    "data" => $product
]);