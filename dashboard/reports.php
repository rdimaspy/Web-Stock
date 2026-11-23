<?php
// Validasi sesi, akses bisnis aktif, bahasa, & mata uang
require_once __DIR__ . '/../includes/auth_business.php';

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   $date_to   = date('Y-m-d');

$allowedSort = ['product_name', 'stock_in', 'stock_out', 'profit', 'current_stock'];
$sort = in_array($_GET['sort'] ?? '', $allowedSort, true) ? $_GET['sort'] : 'profit';
$order = strtoupper($_GET['order'] ?? 'DESC');
if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';

// Query akumulasi stok masuk, stok keluar, dan keuntungan per produk
$sql = "SELECT p.id, p.product_name, p.current_stock,
            COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_in' THEN it.quantity ELSE 0 END), 0) AS stock_in,
            COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_out' THEN it.quantity ELSE 0 END), 0) AS stock_out,
            COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_out' THEN (it.selling_price - it.purchase_price) * it.quantity ELSE 0 END), 0) AS profit
        FROM products p
        LEFT JOIN inventory_transactions it
            ON p.id = it.product_id AND DATE(it.transaction_date) BETWEEN ? AND ?
        WHERE p.business_id = ?
        GROUP BY p.id
        ORDER BY $sort $order";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssi", $date_from, $date_to, $business_id);
mysqli_stmt_execute($stmt);
$reports = mysqli_stmt_get_result($stmt);

// Hitung ringkasan total laporan
$total_products = $total_stock = $total_stock_in = $total_stock_out = 0;
$total_profit = 0.0;
while ($row = mysqli_fetch_assoc($reports)) {
    $total_products++;
    $total_stock     += (int) $row['current_stock'];
    $total_stock_in  += (int) $row['stock_in'];
    $total_stock_out += (int) $row['stock_out'];
    $total_profit    += (float) $row['profit'];
}
mysqli_data_seek($reports, 0);

// Hitung total nilai modal stok berjalan
$stmt_modal = mysqli_prepare($conn, "SELECT COALESCE(SUM(current_stock * purchase_price), 0) AS total_modal FROM products WHERE business_id = ?");
mysqli_stmt_bind_param($stmt_modal, "i", $business_id);
mysqli_stmt_execute($stmt_modal);
$total_modal_value = (float) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_modal))['total_modal'] ?? 0);

$currentPage = 'reports.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('reports'), ENT_QUOTES, 'UTF-8') ?> - StockIn</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/toast.js?v=2" defer></script>
    <script src="../assets/js/app.js?v=2" defer></script>
    <script src="../assets/js/reports.js?v=2" defer></script>
</head>
<body>

<div class="dashboard-layout">
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <main class="dashboard-main">
        <?php require __DIR__ . '/components/header.php'; ?>

        <section class="page-section">
            <div class="page-header">
                <h1 class="page-title"><?= htmlspecialchars(__('reports'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="page-desc"><?= htmlspecialchars(__('reports_description'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <!-- Filter Periode & Pengurutan Laporan -->
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

                <div>
                    <label class="form-label"><?= htmlspecialchars(__('sort_by'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="sort" class="form-control">
                        <?php foreach (['profit' => 'profit', 'stock_in' => 'stock_in', 'stock_out' => 'stock_out', 'current_stock' => 'current_stock', 'product_name' => 'product_name'] as $val => $labelKey): ?>
                            <option value="<?= $val ?>" <?= $sort === $val ? 'selected' : '' ?>>
                                <?= htmlspecialchars(__($labelKey), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label"><?= htmlspecialchars(__('order'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="order" class="form-control">
                        <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>><?= htmlspecialchars(__('descending'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>><?= htmlspecialchars(__('ascending'), ENT_QUOTES, 'UTF-8') ?></option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('apply'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>

            <!-- Kartu Ringkasan Statistik Laporan -->
            <div class="stats-grid-4">
                <div class="stat-box-white">
                    <p style="margin:0; color:#81776D; font-size:13px;"><?= htmlspecialchars(__('total_products'), ENT_QUOTES, 'UTF-8') ?></p>
                    <h2 style="margin:10px 0 0;"><?= number_format($total_products) ?></h2>
                </div>
                <div class="stat-box-white">
                    <p style="margin:0; color:#81776D; font-size:13px;"><?= htmlspecialchars(__('total_stock'), ENT_QUOTES, 'UTF-8') ?></p>
                    <h2 style="margin:10px 0 0;"><?= number_format($total_stock) ?></h2>
                </div>
                <div class="stat-box-white">
                    <p style="margin:0; color:#81776D; font-size:13px;"><?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?></p>
                    <h2 style="margin:10px 0 0;"><?= number_format($total_stock_in) ?></h2>
                </div>
                <div class="stat-box-white">
                    <p style="margin:0; color:#81776D; font-size:13px;"><?= htmlspecialchars(__('stock_out'), ENT_QUOTES, 'UTF-8') ?></p>
                    <h2 style="margin:10px 0 0;"><?= number_format($total_stock_out) ?></h2>
                </div>
                <div class="stat-box-white">
                    <p style="margin:0; color:#81776D; font-size:13px;"><?= htmlspecialchars(__('total_capital'), ENT_QUOTES, 'UTF-8') ?></p>
                    <h2 style="margin:10px 0 0; color:#76553D;"><?= htmlspecialchars(formatMoneyPHP($total_modal_value, $business['currency']), ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
                <div class="stat-box-white span-2">
                    <p style="margin:0; color:#81776D; font-size:13px;"><?= htmlspecialchars(__('profit'), ENT_QUOTES, 'UTF-8') ?></p>
                    <h2 style="margin:10px 0 0;"><?= htmlspecialchars(formatMoneyPHP($total_profit, $business['currency']), ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
            </div>

            <!-- Tabel Laporan Produk & Tombol Ekspor -->
            <div class="table-wrapper">
                <div class="table-top-bar">
                    <span><?= htmlspecialchars(__('product_report'), ENT_QUOTES, 'UTF-8') ?></span>

                    <div style="display:flex; gap:10px;">
                        <button type="button" onclick="exportTableToCSV('laporan_stok.csv')" class="btn btn-primary btn-sm">
                            <i data-lucide="download" style="width:16px; height:16px;"></i>
                            <?= htmlspecialchars(__('export_csv'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <button type="button" onclick="printPDF('export-pdf.php?business_id=<?= (int) $business_id ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>')" class="btn btn-primary btn-sm">
                            <i data-lucide="file-text" style="width:16px; height:16px;"></i>
                            <?= htmlspecialchars(__('export_pdf'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </div>

                <div class="table-scroll">
                    <table id="reportTable" class="data-table">
                        <thead>
                            <tr>
                                <?php foreach (['product_name', 'stock_in', 'stock_out', 'profit', 'current_stock'] as $key): ?>
                                    <th><?= htmlspecialchars(__($key), ENT_QUOTES, 'UTF-8') ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($reports) === 0): ?>
                                <tr>
                                    <td colspan="5" class="empty-cell"><?= htmlspecialchars(__('no_data'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = mysqli_fetch_assoc($reports)): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?= htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((int) $row['stock_in']) ?></td>
                                        <td><?= number_format((int) $row['stock_out']) ?></td>
                                        <td><?= htmlspecialchars(formatMoneyPHP($row['profit'], $business['currency']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((int) $row['current_stock']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<iframe id="pdfFrame" style="display:none;"></iframe>

<script>
window.REPORT_CONFIG = {
    hasData: <?= $total_products > 0 ? 'true' : 'false' ?>,
    noDataMsg: <?= json_encode(__('no_data_to_export')) ?>,
    csvSuccessMsg: <?= json_encode(__('csv_export_success')) ?>,
    csvFailedMsg: <?= json_encode(__('csv_export_failed')) ?>,
    pdfSuccessMsg: <?= json_encode(__('pdf_export_success')) ?>,
    pdfFailedMsg: <?= json_encode(__('pdf_export_failed')) ?>
};
</script>
</body>
</html>
