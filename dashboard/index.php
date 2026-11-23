<?php
// Validasi sesi, akses bisnis aktif, bahasa, & mata uang
require_once __DIR__ . '/../includes/auth_business.php';

date_default_timezone_set('Asia/Jakarta');
$todayStart = date('Y-m-d 00:00:00');
$tomorrowStart = date('Y-m-d 00:00:00', strtotime('+1 day'));

// Query ringkasan transaksi stok masuk, stok keluar, dan omset hari ini
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN transaction_type = 'stock_in' THEN quantity ELSE 0 END), 0) AS total_stock_in,
        COALESCE(SUM(CASE WHEN transaction_type = 'stock_out' THEN quantity ELSE 0 END), 0) AS total_stock_out,
        COALESCE(SUM(CASE WHEN transaction_type = 'stock_out' THEN total_amount ELSE 0 END), 0) AS total_revenue
    FROM inventory_transactions
    WHERE business_id = ? AND transaction_date >= ? AND transaction_date < ?"
);
mysqli_stmt_bind_param($stmt, "iss", $business_id, $todayStart, $tomorrowStart);
mysqli_stmt_execute($stmt);
$dailyStats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Query total jumlah produk dan stok tersedia saat ini
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total_products, COALESCE(SUM(current_stock), 0) AS total_stock
     FROM products WHERE business_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $business_id);
mysqli_stmt_execute($stmt);
$stockStats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Query 5 produk terlaris hari ini
$stmtTop = mysqli_prepare(
    $conn,
    "SELECT p.id AS product_id, p.product_name, p.photo, p.current_stock,
            COALESCE(SUM(t.quantity), 0) AS total_sold
     FROM products p
     INNER JOIN inventory_transactions t
         ON p.id = t.product_id AND t.transaction_type = 'stock_out'
         AND t.transaction_date >= ? AND t.transaction_date < ?
     WHERE p.business_id = ?
     GROUP BY p.id
     ORDER BY total_sold DESC, p.current_stock DESC
     LIMIT 5"
);
mysqli_stmt_bind_param($stmtTop, "ssi", $todayStart, $tomorrowStart, $business_id);
mysqli_stmt_execute($stmtTop);
$topProducts = mysqli_stmt_get_result($stmtTop);

$currentPage = 'index.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - StockIn</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="../assets/js/app.js?v=2" defer></script>
</head>
<body>

<div class="dashboard-layout">
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <main class="dashboard-main">
        <?php require __DIR__ . '/components/header.php'; ?>

        <section class="page-section max-800">
            <div class="card-overview">
                <!-- Header Kartu Ringkasan -->
                <div class="preview-header" style="padding-bottom:18px;">
                    <div>
                        <h2 style="margin:0; color:#302921; font-size:20px;"><?= htmlspecialchars(__('business_overview'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <p style="margin:4px 0 0; color:#81776D; font-size:12px;"><?= htmlspecialchars(__('today_activity_summary'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span class="badge" style="background:#FAF7F2; border:1px solid #E9E1D7; color:#76553D;">
                        <?= htmlspecialchars(__('today'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <!-- Grid Statistik Harian -->
                <div class="stats-grid-2">
                    <div class="stat-box-soft">
                        <p style="margin:0; color:#81776D; font-size:12px;"><?= htmlspecialchars(__('total_products'), ENT_QUOTES, 'UTF-8') ?></p>
                        <h3 style="margin:8px 0 0; color:#302921; font-size:26px;"><?= number_format($stockStats['total_products'] ?? 0) ?></h3>
                        <p style="margin:4px 0 0; color:#6F8966; font-size:11px; font-weight:600;">
                            <?= number_format($stockStats['total_stock'] ?? 0) ?> <?= htmlspecialchars(__('total_units_available'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <div class="stat-box-soft">
                        <p style="margin:0; color:#81776D; font-size:12px;"><?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?></p>
                        <h3 style="margin:8px 0 0; color:#302921; font-size:26px;">+<?= number_format($dailyStats['total_stock_in'] ?? 0) ?></h3>
                        <p style="margin:4px 0 0; color:#6F8966; font-size:11px; font-weight:600;"><?= htmlspecialchars(__('units_received_today'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="stat-box-soft">
                        <p style="margin:0; color:#81776D; font-size:12px;"><?= htmlspecialchars(__('stock_out'), ENT_QUOTES, 'UTF-8') ?></p>
                        <h3 style="margin:8px 0 0; color:#302921; font-size:26px;">-<?= number_format($dailyStats['total_stock_out'] ?? 0) ?></h3>
                        <p style="margin:4px 0 0; color:#6F8966; font-size:11px; font-weight:600;"><?= htmlspecialchars(__('units_sold_today'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="stat-box-soft">
                        <p style="margin:0; color:#81776D; font-size:12px;"><?= htmlspecialchars(__('revenue'), ENT_QUOTES, 'UTF-8') ?></p>
                        <h3 style="margin:8px 0 0; color:#302921; font-size:22px; overflow-wrap:anywhere;">
                            <?= htmlspecialchars(formatMoneyPHP($dailyStats['total_revenue'] ?? 0, $business['currency'] ?? 'IDR'), ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <p style="margin:4px 0 0; color:#6F8966; font-size:11px; font-weight:600;"><?= htmlspecialchars(__('todays_sales'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <!-- Daftar 5 Produk Terlaris Hari Ini -->
                <h3 style="margin:20px 0 12px; color:#302921; font-size:14px;">
                    <?= htmlspecialchars(__('todays_best_selling'), ENT_QUOTES, 'UTF-8') ?>
                </h3>

                <?php if (mysqli_num_rows($topProducts) === 0): ?>
                    <p style="color:#81776D; font-size:13px; margin:10px 0;">
                        <?= htmlspecialchars(__('no_sales_recorded_today'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php else: ?>
                    <?php while ($prod = mysqli_fetch_assoc($topProducts)):
                        $productId = (int) $prod['product_id'];
                        $photoName = $prod['photo'] ?? '';
                        $photoSys = __DIR__ . '/../uploads/products/' . $productId . '/' . $photoName;
                        $photoWeb = '../uploads/products/' . $productId . '/' . $photoName;
                    ?>
                        <div class="top-product-row">
                            <?php if (!empty($photoName) && file_exists($photoSys)): ?>
                                <img src="<?= htmlspecialchars($photoWeb, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($prod['product_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    class="product-thumb-sm">
                            <?php else: ?>
                                <div class="product-initial-sm">
                                    <?= htmlspecialchars(strtoupper(substr($prod['product_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <div style="flex:1; min-width:0;">
                                <p style="margin:0; color:#302921; font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($prod['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>

                            <div style="text-align:right; flex-shrink:0;">
                                <strong style="display:block; color:#302921; font-size:13px;">
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

</body>
</html>
