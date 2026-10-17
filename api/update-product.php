<?php
// Menentukan header respon server dalam format JSON UTF-8
header('Content-Type: application/json; charset=utf-8');

// Memulai sesi login pengguna dan memuat file koneksi database
session_start();
require_once __DIR__ . '/../config/database.php';

// Helper buat nampilkan respon JSON dan menghentikan proses API
function respond($status, $message, $extra = [])
{
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra));
    exit;
}

// Memeriksa status autentikasi sesi login pengguna
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    respond('error', 'Sesi login berakhir. Silakan login kembali.');
}

// Menangkap dan memvalidasi input parameter dari formulir
$business_id = filter_var(
    $_POST['business_id'] ?? null,
    FILTER_VALIDATE_INT
);

// Menangkap dan memvalidasi ID produk dari form
$product_id = filter_var(
    $_POST['product_id'] ?? null,
    FILTER_VALIDATE_INT
);

// Membersihkan spasi pada input nama produk dan kode produk
$product_name = trim($_POST['product_name'] ?? '');
$product_code = trim($_POST['product_code'] ?? '');

// Memvalidasi ID kategori jika diisi oleh pengguna
$category_id = !empty($_POST['category_id'])
    ? filter_var($_POST['category_id'], FILTER_VALIDATE_INT)
    : null;

// Menangkap input harga beli dan harga jual dari formulir
$purchase_price = trim($_POST['purchase_price'] ?? '0');
$selling_price = trim($_POST['selling_price'] ?? '0');

// Menangkap dan memvalidasi input stok awal
$initial_stock = filter_var(
    $_POST['initial_stock'] ?? null,
    FILTER_VALIDATE_INT
);

// Menangkap input tanggal kedaluwarsa jika tersedia
$expiration_date = !empty($_POST['expiration_date'])
    ? $_POST['expiration_date']
    : null;

// Membersihkan spasi pada input catatan produk
$notes = trim($_POST['notes'] ?? '');

// Validasi kelengkapan data dasar produk
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

// Normalisasi format desimal untuk harga beli dan harga jual
$purchase_price = str_replace(',', '.', $purchase_price);
$selling_price = str_replace(',', '.', $selling_price);

// Validasi nominal harga agar tidak bernilai negatif
if (
    !is_numeric($purchase_price) ||
    !is_numeric($selling_price) ||
    (float) $purchase_price < 0 ||
    (float) $selling_price < 0
) {
    // Mengeset kode respon HTTP 400 Bad Request
    http_response_code(400);
    respond('error', 'Harga harus berupa angka yang valid dan tidak boleh negatif.');
}

// Mengonversi nilai harga ke tipe data float
$purchase_price = (float) $purchase_price;
$selling_price = (float) $selling_price;

// Memeriksa apakah bisnis terdaftar dan milik pengguna aktif
$stmt = mysqli_prepare(
    $conn,
    'SELECT id FROM businesses WHERE id = ? AND user_id = ? LIMIT 1'
);

// Mengikat parameter ID bisnis dan ID pengguna ke statement
mysqli_stmt_bind_param(
    $stmt,
    'ii',
    $business_id,
    $_SESSION['user_id']
);

// Mengeksekusi query pengecekan bisnis
mysqli_stmt_execute($stmt);

// Mengambil hasil eksekusi query bisnis
$business_result = mysqli_stmt_get_result($stmt);

// Memastikan bisnis terdaftar dan milik pengguna aktif
if (mysqli_num_rows($business_result) === 0) {
    // Mengeset kode respon HTTP 403 Forbidden
    http_response_code(403);
    respond('error', 'Bisnis tidak ditemukan atau akses ditolak.');
}

// Memeriksa keberadaan produk pada bisnis tersebut
$stmt = mysqli_prepare(
    $conn,
    'SELECT id, photo FROM products WHERE id = ? AND business_id = ? LIMIT 1'
);

// Mengikat parameter ID produk dan ID bisnis ke statement
mysqli_stmt_bind_param($stmt, 'ii', $product_id, $business_id);

// Mengeksekusi query pengecekan produk
mysqli_stmt_execute($stmt);

// Mengambil hasil eksekusi data produk
$product_result = mysqli_stmt_get_result($stmt);

// Menyimpan data produk lama ke dalam array asosiatif
$existing_product = mysqli_fetch_assoc($product_result);

// Memastikan data produk ditemukan di database
if (!$existing_product) {
    // Mengeset kode respon HTTP 404 Not Found
    http_response_code(404);
    respond('error', 'Produk tidak ditemukan.');
}

// Memvalidasi kategori produk jika dipilih
if ($category_id !== null) {
    // Menolak jika ID kategori tidak bernilai integer valid
    if ($category_id === false) {
        http_response_code(400);
        respond('error', 'Kategori tidak valid.');
    }

    // Menyiapkan query untuk memastikan kategori terdaftar di bisnis ini
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id FROM categories WHERE id = ? AND business_id = ? LIMIT 1'
    );

    // Mengikat parameter ID kategori dan ID bisnis
    mysqli_stmt_bind_param($stmt, 'ii', $category_id, $business_id);
    
    // Mengeksekusi query verifikasi kategori
    mysqli_stmt_execute($stmt);

    // Mengembalikan error jika kategori tidak ditemukan pada bisnis
    if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) {
        http_response_code(400);
        respond('error', 'Kategori tidak ditemukan pada bisnis ini.');
    }
}

// Memvalidasi kesesuaian format tanggal kedaluwarsa (Y-m-d)
if ($expiration_date !== null) {
    $date = DateTime::createFromFormat('!Y-m-d', $expiration_date);

    // Memastikan objek tanggal valid dan sesuai dengan string input
    if (!$date || $date->format('Y-m-d') !== $expiration_date) {
        http_response_code(400);
        respond('error', 'Tanggal kedaluwarsa tidak valid.');
    }
}

// Memperbarui informasi produk ke database tanpa mengubah nilai stok berjalan
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

// Mengikat seluruh parameter data produk yang akan diperbarui
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

// Mengembalikan error jika query update produk gagal dieksekusi
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    respond('error', 'Gagal memperbarui produk.');
}

// Memproses unggahan foto produk baru jika dipilih oleh pengguna
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Validasi error saat proses upload foto
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        respond('error', 'Upload foto gagal.');
    }

    // Validasi batas maksimum ukuran file foto (5 MB)
    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        respond('error', 'Ukuran foto maksimal 5 MB.');
    }

    // Memeriksa keabsahan file gambar
    $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

    if ($imageInfo === false) {
        http_response_code(400);
        respond('error', 'File yang diunggah bukan gambar yang valid.');
    }

    // Daftar format ekstensi foto yang diizinkan
    $mimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    $mimeType = $imageInfo['mime'];

    // Menolak file yang memiliki format ekstensi di luar daftar izin
    if (!isset($mimeTypes[$mimeType])) {
        http_response_code(400);
        respond('error', 'Format foto harus JPG, PNG, WEBP, atau GIF.');
    }

    // Menentukan lokasi folder penyimpanan foto produk
    $uploadDir = __DIR__ . '/../uploads/products/' . $product_id . '/';

    // Membuat folder baru jika belum tersedia
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        respond('error', 'Folder foto produk gagal dibuat.');
    }

    // Membuat nama unik file foto baru secara acak
    $filename = bin2hex(random_bytes(16)) . '.' . $mimeTypes[$mimeType];
    $target   = $uploadDir . $filename;

    // Pindahkan file foto sementara ke direktori target
    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
        http_response_code(500);
        respond('error', 'Foto produk gagal disimpan.');
    }

    // Memperbarui nama file foto baru pada database
    $updatePhoto = mysqli_prepare(
        $conn,
        'UPDATE products SET photo = ? WHERE id = ? AND business_id = ?'
    );

    // Mengikat parameter nama file foto, ID produk, dan ID bisnis
    mysqli_stmt_bind_param(
        $updatePhoto,
        'sii',
        $filename,
        $product_id,
        $business_id
    );

    // Menghapus file foto yang baru diunggah jika pembaruan database gagal
    if (!mysqli_stmt_execute($updatePhoto)) {
        @unlink($target);
        http_response_code(500);
        respond('error', 'Gagal menyimpan informasi foto produk.');
    }

    // Menghapus file foto lama dari server setelah foto baru berhasil disimpan
    if (!empty($existing_product['photo'])) {
        $oldPhoto = $uploadDir . basename($existing_product['photo']);

        if (is_file($oldPhoto)) {
            @unlink($oldPhoto);
        }
    }
}

// Mengembalikan respons sukses pembaruan data produk
respond('success', 'Produk berhasil diperbarui.', [
    'product_id' => $product_id
]);