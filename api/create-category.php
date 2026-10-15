
<?php
header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/../config/database.php';

function respond($status, $message, $data = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $data));
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    respond('error', 'Sesi login berakhir. Silakan login kembali.');
}

$business_id = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$category_name = trim($_POST['category_name'] ?? '');

if (!$business_id || $category_name === '') {
    respond('error', 'Nama kategori wajib diisi.');
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT id FROM businesses WHERE id = ? AND user_id = ? LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'ii', $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    http_response_code(403);
    respond('error', 'Bisnis tidak ditemukan atau kamu tidak memiliki akses.');
}

// Cegah kategori dengan nama yang sama dalam bisnis yang sama.
$stmt = mysqli_prepare(
    $conn,
    'SELECT id FROM categories WHERE business_id = ? AND LOWER(category_name) = LOWER(?) LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'is', $business_id, $category_name);
mysqli_stmt_execute($stmt);

if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    respond('error', 'Kategori tersebut sudah ada.');
}

$stmt = mysqli_prepare(
    $conn,
    'INSERT INTO categories (business_id, category_name) VALUES (?, ?)'
);
mysqli_stmt_bind_param($stmt, 'is', $business_id, $category_name);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    respond('error', 'Kategori gagal disimpan.');
}

respond('success', 'Kategori berhasil ditambahkan.', [
    'category' => [
        'id' => mysqli_insert_id($conn),
        'category_name' => $category_name
    ]
]);
