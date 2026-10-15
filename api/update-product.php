<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../config/database.php';

function respond($status, $message, $extra = [])
{
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra));
    exit;
}

// 1. Validasi login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    respond('error', 'Sesi login berakhir. Silakan login kembali.');
}

// 2. Ambil data formulir
$business_id = filter_var(
    $_POST['business_id'] ?? null,
    FILTER_VALIDATE_INT
);

$product_id = filter_var(
    $_POST['product_id'] ?? null,
    FILTER_VALIDATE_INT
);

$product_name = trim($_POST['product_name'] ?? '');
$product_code = trim($_POST['product_code'] ?? '');

$category_id = !empty($_POST['category_id'])
    ? filter_var($_POST['category_id'], FILTER_VALIDATE_INT)
    : null;

$purchase_price = trim($_POST['purchase_price'] ?? '0');
$selling_price = trim($_POST['selling_price'] ?? '0');

$initial_stock = filter_var(
    $_POST['initial_stock'] ?? null,
    FILTER_VALIDATE_INT
);

$expiration_date = !empty($_POST['expiration_date'])
    ? $_POST['expiration_date']
    : null;

$notes = trim($_POST['notes'] ?? '');

// 3. Validasi data dasar
if (
    !$business_id ||
    !$product_id ||
    $product_name === '' ||
    $initial_stock === false ||
    $initial_stock === null ||
    $initial_stock < 0
) {
    http_response_code(400);
    respond('error', 'Data produk tidak lengkap atau tidak valid.');
}

// 4. Normalisasi harga desimal
$purchase_price = str_replace(',', '.', $purchase_price);
$selling_price = str_replace(',', '.', $selling_price);

if (
    !is_numeric($purchase_price) ||
    !is_numeric($selling_price) ||
    (float) $purchase_price < 0 ||
    (float) $selling_price < 0
) {
    http_response_code(400);
    respond('error', 'Harga harus berupa angka yang valid dan tidak boleh negatif.');
}

$purchase_price = (float) $purchase_price;
$selling_price = (float) $selling_price;

// 5. Pastikan bisnis milik pengguna
$stmt = mysqli_prepare(
    $conn,
    'SELECT id FROM businesses WHERE id = ? AND user_id = ? LIMIT 1'
);

mysqli_stmt_bind_param(
    $stmt,
    'ii',
    $business_id,
    $_SESSION['user_id']
);

mysqli_stmt_execute($stmt);
$business_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($business_result) === 0) {
    http_response_code(403);
    respond('error', 'Bisnis tidak ditemukan atau akses ditolak.');
}

// 6. Pastikan produk milik bisnis tersebut
$stmt = mysqli_prepare(
    $conn,
    'SELECT id, photo FROM products WHERE id = ? AND business_id = ? LIMIT 1'
);

mysqli_stmt_bind_param($stmt, 'ii', $product_id, $business_id);
mysqli_stmt_execute($stmt);

$product_result = mysqli_stmt_get_result($stmt);
$existing_product = mysqli_fetch_assoc($product_result);

if (!$existing_product) {
    http_response_code(404);
    respond('error', 'Produk tidak ditemukan.');
}

// 7. Validasi kategori
if ($category_id !== null) {
    if ($category_id === false) {
        http_response_code(400);
        respond('error', 'Kategori tidak valid.');
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id FROM categories WHERE id = ? AND business_id = ? LIMIT 1'
    );

    mysqli_stmt_bind_param($stmt, 'ii', $category_id, $business_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) {
        http_response_code(400);
        respond('error', 'Kategori tidak ditemukan pada bisnis ini.');
    }
}

// 8. Validasi tanggal kedaluwarsa
if ($expiration_date !== null) {
    $date = DateTime::createFromFormat('!Y-m-d', $expiration_date);

    if (!$date || $date->format('Y-m-d') !== $expiration_date) {
        http_response_code(400);
        respond('error', 'Tanggal kedaluwarsa tidak valid.');
    }
}

// 9. Update data produk tanpa mengubah stok saat ini
$stmt = mysqli_prepare(
    $conn,
    'UPDATE products
    SET category_id = ?,
        product_name = ?,
        product_code = ?,
        purchase_price = ?,
        selling_price = ?,
        initial_stock = ?,
        expiration_date = ?,
        notes = ?
    WHERE id = ? AND business_id = ?'
);

mysqli_stmt_bind_param(
    $stmt,
    'issddissii',
    $category_id,
    $product_name,
    $product_code,
    $purchase_price,
    $selling_price,
    $initial_stock,
    $expiration_date,
    $notes,
    $product_id,
    $business_id
);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    respond('error', 'Gagal memperbarui produk.');
}

// 10. Upload foto baru jika dipilih
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        respond('error', 'Upload foto gagal.');
    }

    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        respond('error', 'Ukuran foto maksimal 5 MB.');
    }

    $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

    if ($imageInfo === false) {
        http_response_code(400);
        respond('error', 'File yang diunggah bukan gambar yang valid.');
    }

    $mimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    $mimeType = $imageInfo['mime'];

    if (!isset($mimeTypes[$mimeType])) {
        http_response_code(400);
        respond('error', 'Format foto harus JPG, PNG, WEBP, atau GIF.');
    }

    $uploadDir = __DIR__ . '/../uploads/products/' . $product_id . '/';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        respond('error', 'Folder foto produk gagal dibuat.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $mimeTypes[$mimeType];
    $target = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
        http_response_code(500);
        respond('error', 'Foto produk gagal disimpan.');
    }

    $updatePhoto = mysqli_prepare(
        $conn,
        'UPDATE products SET photo = ? WHERE id = ? AND business_id = ?'
    );

    mysqli_stmt_bind_param(
        $updatePhoto,
        'sii',
        $filename,
        $product_id,
        $business_id
    );

    if (!mysqli_stmt_execute($updatePhoto)) {
        @unlink($target);
        http_response_code(500);
        respond('error', 'Gagal menyimpan informasi foto produk.');
    }

    // Hapus foto lama setelah foto baru berhasil disimpan.
    if (!empty($existing_product['photo'])) {
        $oldPhoto = $uploadDir . basename($existing_product['photo']);

        if (is_file($oldPhoto)) {
            @unlink($oldPhoto);
        }
    }
}

respond('success', 'Produk berhasil diperbarui.', [
    'product_id' => $product_id
]);