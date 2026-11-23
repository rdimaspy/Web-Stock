<?php
// Validasi sesi, akses bisnis aktif, bahasa, & mata uang
require_once __DIR__ . '/../includes/auth_business.php';

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

// Query 10 produk teratas berdasarkan pergerakan stok keluar
$sql_chart = "SELECT p.product_name,
                  COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_in' THEN it.quantity ELSE 0 END), 0) AS stock_in,
                  COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_out' THEN it.quantity ELSE 0 END), 0) AS stock_out,
                  COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_out' THEN (it.selling_price - it.purchase_price) * it.quantity ELSE 0 END), 0) AS profit
              FROM products p
              LEFT JOIN inventory_transactions it ON p.id = it.product_id AND DATE(it.transaction_date) BETWEEN ? AND ?
              WHERE p.business_id = ?
              GROUP BY p.id
              ORDER BY stock_out DESC LIMIT 10";

$stmt = mysqli_prepare($conn, $sql_chart);
mysqli_stmt_bind_param($stmt, "ssi", $date_from, $date_to, $business_id);
mysqli_stmt_execute($stmt);
$res_chart = mysqli_stmt_get_result($stmt);

$product_names = $stock_ins = $stock_outs = $profits = [];
while ($row = mysqli_fetch_assoc($res_chart)) {
    $product_names[] = $row['product_name'];
    $stock_ins[]     = (int) $row['stock_in'];
    $stock_outs[]    = (int) $row['stock_out'];
    $profits[]       = (float) $row['profit'];
}

// Helper untuk merender kop cetak laporan analitik
function renderAnalyticsPrintHeader(array $business, string $dateFrom, string $dateTo): void {
    ?>
    <div class="print-header">
        <h2 style="margin:0; font-size:22px; color:#302921; text-transform:uppercase;">
            <?= htmlspecialchars($business['business_name'] ?? 'NAMA TOKO', ENT_QUOTES, 'UTF-8') ?>
        </h2>
        <p style="margin:4px 0 0; font-size:13px; color:#81776D; font-weight:600;">
            <?= htmlspecialchars(__('analytics_report_title'), ENT_QUOTES, 'UTF-8') ?>
        </p>
        <p style="margin:4px 0 0; font-size:12px; color:#81776D;">
            <?= htmlspecialchars(__('period'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($dateFrom) ?> <?= htmlspecialchars(__('to'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($dateTo) ?>
        </p>
        <hr style="border:0; border-top:2px solid #76553D; margin:12px 0 18px;">
    </div>
    <?php
}

$currentPage = 'reports-analytics.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('analytics_chart'), ENT_QUOTES, 'UTF-8') ?> - StockIn</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/toast.js?v=2" defer></script>
    <script src="../assets/js/app.js?v=2" defer></script>
    <script src="../assets/js/reports.js?v=2" defer></script>
</head>
<body class="analytics-print-body">

<div class="dashboard-layout">
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <main class="dashboard-main">
        <?php require __DIR__ . '/components/header.php'; ?>

        <section class="page-section">
            <div class="page-header">
                <h1 class="page-title"><?= htmlspecialchars(__('analytics_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="page-desc"><?= htmlspecialchars(__('analytics_desc'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <!-- Filter Tanggal & Tombol Ekspor PDF -->
            <form method="GET" class="filter-bar">
                <input type="hidden" name="business_id" value="<?= (int) $business_id ?>">

                <div>
                    <label class="form-label"><?= htmlspecialchars(__('date_from'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div>
                    <label class="form-label"><?= htmlspecialchars(__('date_to'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('apply'), ENT_QUOTES, 'UTF-8') ?></button>

                <button type="button" onclick="exportReportToPDF()" class="btn btn-primary">
                    <i data-lucide="file-down" style="width:18px; height:18px;"></i>
                    <?= htmlspecialchars(__('export_pdf'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>

            <!-- Area Konten Grafik Analitik -->
            <div id="reportContent">
                <?php renderAnalyticsPrintHeader($business, $date_from, $date_to); ?>

                <div class="chart-card chart-container-print">
                    <h3 style="font-size:18px; color:#76553D; margin:0 0 15px;">
                        <?= htmlspecialchars(__('stock_movement_chart'), ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <div class="chart-box">
                        <canvas id="stockMovementChart"></canvas>
                    </div>
                </div>

                <div class="chart-card chart-container-print print-page-break" style="margin-top:20px;">
                    <?php renderAnalyticsPrintHeader($business, $date_from, $date_to); ?>
                    <h3 style="font-size:18px; color:#2E5A27; margin:0 0 15px;">
                        <?= htmlspecialchars(__('profit_trend_chart'), ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <div class="chart-box">
                        <canvas id="profitTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
window.REPORT_CONFIG = {
    hasData: <?= count($product_names) > 0 ? 'true' : 'false' ?>,
    noDataMsg: <?= json_encode(__('no_data_to_export')) ?>,
    pdfSuccessMsg: <?= json_encode(__('pdf_export_success')) ?>,
    chartData: {
        labels: <?= json_encode($product_names) ?>,
        stockIn: <?= json_encode($stock_ins) ?>,
        stockOut: <?= json_encode($stock_outs) ?>,
        profit: <?= json_encode($profits) ?>,
        stockInLabel: <?= json_encode(__('stock_in')) ?>,
        stockOutLabel: <?= json_encode(__('stock_out')) ?>,
        profitLabel: <?= json_encode(__('profit')) ?>
    }
};
</script>
</body>
</html>
