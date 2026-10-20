<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Pengaturan bahasa
require_once __DIR__ . '/../includes/language.php';

// Formatter mata uang
require_once __DIR__ . '/../includes/currency.php';

// Atur zona waktu default ke WIB
date_default_timezone_set('Asia/Jakarta');

// Set batas waktu awal hari ini dan besok
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

// Parameter business_id, waktu mulai hari ini, dan awal esok hari
mysqli_stmt_bind_param(
    $stmt,
    "iss",
    $business_id,
    $todayStart,
    $tomorrowStart
);

// Eksekusi query statistik harian
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

// query stok produk dan eksekusinya
mysqli_stmt_bind_param($stmt, "i", $business_id);
mysqli_stmt_execute($stmt);

// ringkasan stok produk
$stockStats = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

// Query untuk mengambil 5 produk teratas berdasarkan unit yang paling banyak terjual HARI INI
$stmtTopProducts = mysqli_prepare(
    $conn,
    "SELECT 
        p.id AS product_id,
        p.product_name, 
        p.photo, 
        p.current_stock,
        COALESCE(SUM(t.quantity), 0) AS total_sold
    FROM products p
    INNER JOIN inventory_transactions t 
        ON p.id = t.product_id 
        AND t.transaction_type = 'stock_out'
        AND t.transaction_date >= ? 
        AND t.transaction_date < ?
    WHERE p.business_id = ?
    GROUP BY p.id
    ORDER BY total_sold DESC, p.current_stock DESC
    LIMIT 5"
);

// Bind parameter rentang tanggal hari ini & ID bisnis
mysqli_stmt_bind_param($stmtTopProducts, "ssi", $todayStart, $tomorrowStart, $business_id);
mysqli_stmt_execute($stmtTopProducts);
$topProducts = mysqli_stmt_get_result($stmtTopProducts);

?>


<!-- HTML -->
<!DOCTYPE html>
<!-- Set bahasa -->
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - StockIn</title>
    <script src="https://unpkg.com/lucide@latest" defer></script>
</head>

<!-- Styling utama layout dan warna latar halaman -->
<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'index.php';?>

<!-- Layout utama halaman Produk -->
<div style="display:flex; min-height:100vh;">
    
    <!-- SIDEBAR -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- KONTEN UTAMA -->
    <main style="margin-left:230px; flex:1; min-width:0;">
        
        <!-- HEADER -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- AREA DASHBOARD -->
        <!-- Container utama dashboard -->
        <section class="dashboard-container" style="padding:32px; max-width:800px;">

            <!-- KARTU RINGKASAN BISNIS UTAMA -->
            <div style="padding:24px; background:#FFFFFF; border:1px solid #E9E1D7; border-radius:18px; box-shadow:0 15px 40px rgba(92, 69, 48, 0.06);">

                <!-- HEADER KARTU -->
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding-bottom:18px; border-bottom:1px solid #E9E1D7;">
                    <div>
                        <h2 style="margin:0; color:#302921; font-size:20px; font-weight:700;">
                            <?= htmlspecialchars(__('business_overview'), ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <p style="margin:4px 0 0; color:#81776D; font-size:12px;">
                            <?= htmlspecialchars(__('today_activity_summary'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <span style="padding:6px 12px; background:#FAF7F2; border:1px solid #E9E1D7; border-radius:8px; color:#76553D; font-size:12px; font-weight:600;">
                        <?= htmlspecialchars(__('today'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <!-- GRID STATISTIK (2 KOLOM) -->
                <div class="stats-grid" style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:14px; padding:20px 0;">

                    <!-- Total Produk -->
                    <div style="padding:16px; border:1px solid #E9E1D7; border-radius:12px; background:#FAF7F2;">
                        <p style="margin:0; color:#81776D; font-size:12px; font-weight:500;">
                            <?= htmlspecialchars(__('total_products'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <h3 style="margin:8px 0 0; color:#302921; font-size:26px; font-weight:700;">
                            <?= number_format($stockStats['total_products'] ?? 0) ?>
                        </h3>
                        <p style="margin:4px 0 0; color:#6F8966; font-size:11px; font-weight:600;">
                            <?= number_format($stockStats['total_stock'] ?? 0) ?> <?= htmlspecialchars(__('total_units_available'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <!-- Stok Masuk Hari Ini -->
                    <div style="padding:16px; border:1px solid #E9E1D7; border-radius:12px; background:#FAF7F2;">
                        <p style="margin:0; color:#81776D; font-size:12px; font-weight:500;">
                            <?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <h3 style="margin:8px 0 0; color:#302921; font-size:26px; font-weight:700;">
                            +<?= number_format($dailyStats['total_stock_in'] ?? 0) ?>
                        </h3>
                        <p style="margin:4px 0 0; color:#6F8966; font-size:11px; font-weight:600;">
                            <?= htmlspecialchars(__('units_received_today'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <!-- Stok Keluar Hari Ini -->
                    <div style="padding:16px; border:1px solid #E9E1D7; border-radius:12px; background:#FAF7F2;">
                        <p style="margin:0; color:#81776D; font-size:12px; font-weight:500;">
                            <?= htmlspecialchars(__('stock_out'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <h3 style="margin:8px 0 0; color:#302921; font-size:26px; font-weight:700;">
                            -<?= number_format($dailyStats['total_stock_out'] ?? 0) ?>
                        </h3>
                        <p style="margin:4px 0 0; color:#6F8966; font-size:11px; font-weight:600;">
                            <?= htmlspecialchars(__('units_sold_today'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <!-- Omset Hari Ini -->
                    <div style="padding:16px; border:1px solid #E9E1D7; border-radius:12px; background:#FAF7F2;">
                        <p style="margin:0; color:#81776D; font-size:12px; font-weight:500;">
                            <?= htmlspecialchars(__('revenue'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <h3 style="margin:8px 0 0; color:#302921; font-size:22px; font-weight:700; overflow-wrap:anywhere;">
                            <!-- Menggunakan format angka standar jika fungsi formatCurrency belum tersedia -->
                            <?= function_exists('formatCurrency') 
                                ? formatCurrency($dailyStats['total_revenue'] ?? 0, $businessCurrency ?? 'IDR') 
                                : 'Rp ' . number_format($dailyStats['total_revenue'] ?? 0, 0, ',', '.') ?>
                        </h3>
                        <p style="margin:4px 0 0; color:#6F8966; font-size:11px; font-weight:600;">
                            <?= htmlspecialchars(__('todays_sales'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                </div>

                <!-- MENAMPILKAN PRODUK TERLARIS HARI INI (TOP 5) -->
                <h3 style="margin:20px 0 12px; color:#302921; font-size:14px; font-weight:700;">
                    <?= htmlspecialchars(__('todays_best_selling'), ENT_QUOTES, 'UTF-8') ?>
                </h3>

                <?php if (mysqli_num_rows($topProducts) === 0): ?>
                    <!-- Tampilan jika belum ada transaksi penjualan hari ini -->
                    <p style="color:#81776D; font-size:13px; margin:10px 0;">
                        <?= htmlspecialchars(__('no_sales_recorded_today'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php else: ?>
                    <!-- Looping untuk menampilkan 5 produk terlaris hari ini -->
                    <?php while ($prod = mysqli_fetch_assoc($topProducts)): 
                        $initial = strtoupper(substr($prod['product_name'], 0, 1));
                    ?>
                        <div style="display:flex; align-items:center; gap:14px; padding:12px 0; border-top:1px solid #F1EBE4;">
                            <!-- FOTO ATAU BADGE INISIAL PRODUK -->
                            <?php 
                            // Menyusun lokasi file berdasarkan ID produk dan nama foto
                            $productId = (int) $prod['product_id'];
                            $photoName = $prod['photo'] ?? '';

                            // Jalur sistem & jalur web relatif untuk gambar
                            $photoPathSystem = __DIR__ . '/../uploads/products/' . $productId . '/' . $photoName;
                            $photoPathWeb = '../uploads/products/' . $productId . '/' . $photoName;
                            ?>

                            <?php if (!empty($photoName) && file_exists($photoPathSystem)): ?>
                                <!-- Tampilkan foto produk jika file tersedia di folder uploads/products/{product_id}/ -->
                                <img src="<?= htmlspecialchars($photoPathWeb, ENT_QUOTES, 'UTF-8') ?>" 
                                    alt="<?= htmlspecialchars($prod['product_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    style="width:40px; height:40px; flex-shrink:0; border-radius:10px; object-fit:cover; border:1px solid #E9E1D7;">
                            <?php else: ?>
                                <!-- Cadangan: Badge inisial huruf jika foto tidak ada -->
                                <div style="width:40px; height:40px; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:#F3E8DC; color:#76553D; border-radius:10px; font-weight:700; font-size:15px;">
                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <!-- Nama produk -->
                            <div style="flex:1; min-width:0;">
                                <p style="margin:0; color:#302921; font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($prod['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>

                            <!-- Penjualan hari ini & sisa stok -->
                            <div style="text-align:right; flex-shrink:0;">
                                <strong style="display:block; color:#302921; font-size:13px; font-weight:700;">
                                    <?= number_format($prod['total_sold']) ?> <?= htmlspecialchars(__('sold_today'), ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                                <span style="color:#81776D; font-size:10px;">
                                    <?= htmlspecialchars(__('stock'), ENT_QUOTES, 'UTF-8') ?>: <?= number_format($prod['current_stock']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>

            </div>

        </section>
    </main>
</div>

<!-- ICON -->
<script src="https://unpkg.com/lucide@latest"></script>

<script>lucide.createIcons();</script>

</body>
</html>
