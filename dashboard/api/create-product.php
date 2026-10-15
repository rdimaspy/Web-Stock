<?php
header('Content-Type: application/json');

session_start();

include "../config/database.php";

// cek login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

// ambil data form
$business_id = $_POST['business_id'] ?? null;

$product_name = $_POST['product_name'] ?? '';

$product_code = trim($_POST['product_code'] ?? '');
if($product_code == ''){
    $product_code = 'PRD-' . strtoupper(substr(uniqid(), -6));
}

$category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

$purchase_price = trim($_POST['purchase_price'] ?? '0');
$selling_price = trim($_POST['selling_price'] ?? '0');

$purchase_price = str_replace(',', '.', $purchase_price);
$selling_price = str_replace(',', '.', $selling_price);

if (
    !is_numeric($purchase_price) ||
    !is_numeric($selling_price) ||
    (float) $purchase_price < 0 ||
    (float) $selling_price < 0
) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Harga harus berupa angka yang valid dan tidak boleh negatif."
    ]);
    exit;
}

$purchase_price = (float) $purchase_price;
$selling_price = (float) $selling_price;

$initial_stock = $_POST['initial_stock'] ?? 0;

$expiration_date = !empty($_POST['expiration_date']) 
    ? $_POST['expiration_date'] 
    : null;
    
$notes = $_POST['notes'] ?? null;

// cek data wajib
if (!$business_id || !$product_name) {
    exit("Data tidak lengkap");
}

// cek bisnis milik user
$stmt = mysqli_prepare($conn, "SELECT id FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    exit("Business tidak ditemukan");
}

// Simpan produk ke database
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO products
    (
        business_id,
        category_id,
        product_name,
        product_code,
        purchase_price,
        selling_price,
        initial_stock,
        current_stock,
        expiration_date,
        notes
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "iissddisss",
    $business_id,
    $category_id,
    $product_name,
    $product_code,
    $purchase_price,
    $selling_price,
    $initial_stock,
    $initial_stock,
    $expiration_date,
    $notes
);

if(mysqli_stmt_execute($stmt)){
    $product_id = mysqli_insert_id($conn);

    // upload foto produk
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
        $uploadDir = "../uploads/products/" . $product_id . "/";

        if(!is_dir($uploadDir)){
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = time() . "." . $extension;
        $target = $uploadDir . $filename;

        if(move_uploaded_file($_FILES['photo']['tmp_name'], $target)){
            $update = mysqli_prepare($conn, "UPDATE products SET photo = ? WHERE id = ?");
            mysqli_stmt_bind_param($update, "si", $filename, $product_id);
            mysqli_stmt_execute($update);
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Produk berhasil dibuat",
        "product_id" => $product_id
    ]);

    exit;
}