<?php
session_start();

// Pengaturan bahasa
require_once __DIR__ . '/../includes/language.php';

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

// Hubungkan ke database
include "../config/database.php";

// Formatter mata uang
require_once __DIR__ . '/../includes/currency.php';

// Ambil ID bisnis dari URL
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);

if (!$business_id) {
    header("Location: ../businesses.php");
    exit;
}

// Pastikan bisnis dimiliki oleh pengguna yang sedang login
$stmt = mysqli_prepare($conn, "SELECT id, business_name, photo, currency FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$business = mysqli_fetch_assoc($result);

// Jika bisnis tidak ditemukan atau bukan milik pengguna
if (!$business) {
    http_response_code(404);
    exit("Bisnis tidak ditemukan atau kamu tidak memiliki akses.");
}


date_default_timezone_set('Asia/Jakarta');

$todayStart = date('Y-m-d 00:00:00');
$tomorrowStart = date('Y-m-d 00:00:00', strtotime('+1 day'));

// Statistik transaksi hari ini.
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COALESCE(SUM(CASE
            WHEN transaction_type = 'stock_in' THEN quantity
            ELSE 0
        END), 0) AS total_stock_in,

        COALESCE(SUM(CASE
            WHEN transaction_type = 'stock_out' THEN quantity
            ELSE 0
        END), 0) AS total_stock_out,

        COALESCE(SUM(CASE
            WHEN transaction_type = 'stock_out' THEN total_amount
            ELSE 0
        END), 0) AS total_revenue,

        COALESCE(SUM(CASE
            WHEN transaction_type = 'stock_out'
            THEN (selling_price - purchase_price) * quantity
            ELSE 0
        END), 0) AS total_profit

    FROM inventory_transactions
    WHERE business_id = ?
    AND transaction_date >= ?
    AND transaction_date < ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "iss",
    $business_id,
    $todayStart,
    $tomorrowStart
);

mysqli_stmt_execute($stmt);
$dailyStats = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

// Jumlah produk dan total stok tersedia saat ini.
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COUNT(*) AS total_products,
        COALESCE(SUM(current_stock), 0) AS total_stock
    FROM products
    WHERE business_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $business_id);
mysqli_stmt_execute($stmt);

$stockStats = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

?>


<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stock</title>
</head>

<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'index.php';?>

    <div style="display:flex; min-height:100vh;">

    <!-- Sidebar -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

        <!-- KONTEN UTAMA -->
        <main style="margin-left:230px; flex:1; min-width:0;">

            <!-- HEADER -->
            <?php require __DIR__ . '/components/header.php'; ?>

            <!-- AREA DASHBOARD -->
            <section style="padding:32px;">

                <h1 style="margin:0; font-size:29px;">Dashboard</h1>

                <p style="color:#81776D; margin-top:10px;">
                    <?= htmlspecialchars(__('welcome')) ?>
                    <?= htmlspecialchars(__('dashboard_description')) ?>
                </p>

                <div style="margin-top:28px; padding:25px; background:white; border:1px solid #E9E1D7; border-radius:14px;">
                    <h3 style="margin-top:0;">
                        <?= htmlspecialchars(__('dashboard_ready')) ?>
                    </h3>
                    <p style="color:#81776D; margin-bottom:0;">
                        <?= htmlspecialchars(__('dashboard_placeholder')) ?>
                    </p>
                </div>

            </section>

        </main>

    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>
