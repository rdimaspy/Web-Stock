<?php
// Helper terpusat untuk respon JSON API, validasi autentikasi, query kategori, dan upload foto

// Mengirimkan respon JSON terstandarisasi dan menghentikan eksekusi script
function jsonResponse(string $status, string $message, int $httpCode = 200, array $extra = []): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'status'  => $status,
        'success' => $status === 'success',
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// Memastikan request menggunakan metode POST dan user sudah login (khusus API)
function requireApiAuth(bool $requirePost = true): int
{
    if (!isset($_SESSION['user_id'])) {
        jsonResponse('error', __('session_expired'), 401);
    }
    if ($requirePost && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', __('method_not_allowed'), 405);
    }
    return (int) $_SESSION['user_id'];
}

// Memastikan bisnis milik pengguna yang sedang login
function verifyBusinessAccess(mysqli $conn, int $businessId, int $userId): array
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $businessId, $userId);
    mysqli_stmt_execute($stmt);
    $business = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$business) {
        jsonResponse('error', __('business_unauthorized'), 403);
    }
    return $business;
}

// Mengambil daftar kategori produk untuk bisnis aktif
function getBusinessCategories(mysqli $conn, int $businessId): mysqli_result
{
    $stmt = mysqli_prepare($conn, "SELECT id, category_name FROM categories WHERE business_id = ? ORDER BY category_name ASC");
    mysqli_stmt_bind_param($stmt, "i", $businessId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// Mengambil daftar produk untuk bisnis aktif
function getBusinessProducts(mysqli $conn, int $businessId): mysqli_result
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, product_name, product_code, current_stock, selling_price, category_id
         FROM products WHERE business_id = ? ORDER BY product_name ASC"
    );
    mysqli_stmt_bind_param($stmt, "i", $businessId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// Memproses upload file gambar dengan validasi ekstensi & pembuatan folder otomatis
function uploadPhotoFile(array $file, string $targetDir, string $prefix = '', int $maxSizeMB = 5): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > $maxSizeMB * 1024 * 1024) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return null;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = $prefix . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], rtrim($targetDir, '/') . '/' . $filename)) {
        return $filename;
    }
    return null;
}

