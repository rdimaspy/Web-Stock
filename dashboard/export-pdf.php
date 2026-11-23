<?php
// Validasi sesi, akses bisnis aktif, bahasa, & mata uang
require_once __DIR__ . '/../includes/auth_business.php';

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

// Query akumulasi transaksi produk untuk laporan PDF
$sql = "SELECT p.product_name, p.current_stock,
            COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_in' THEN it.quantity ELSE 0 END), 0) AS stock_in,
            COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_out' THEN it.quantity ELSE 0 END), 0) AS stock_out,
            COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_out' THEN (it.selling_price - it.purchase_price) * it.quantity ELSE 0 END), 0) AS profit
        FROM products p
        LEFT JOIN inventory_transactions it ON p.id = it.product_id AND DATE(it.transaction_date) BETWEEN ? AND ?
        WHERE p.business_id = ?
        GROUP BY p.id ORDER BY p.product_name ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssi", $date_from, $date_to, $business_id);
mysqli_stmt_execute($stmt);
$reports = mysqli_stmt_get_result($stmt);

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

$stmt_m = mysqli_prepare($conn, "SELECT COALESCE(SUM(current_stock * purchase_price), 0) AS total_modal FROM products WHERE business_id = ?");
mysqli_stmt_bind_param($stmt_m, "i", $business_id);
mysqli_stmt_execute($stmt_m);
$total_modal_value = (float) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_m))['total_modal'] ?? 0);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Stok_<?= htmlspecialchars($business['business_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body class="pdf-print-body">

    <div class="no-print" style="margin-bottom:15px; text-align:right;">
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <?= htmlspecialchars(__('print_save_pdf'), ENT_QUOTES, 'UTF-8') ?>
        </button>
    </div>

    <div class="pdf-header">
        <h2><?= htmlspecialchars($business['business_name'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p style="margin:5px 0 0;"><?= htmlspecialchars(__('stock_report_header'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <div style="margin-bottom:15px;">
        <strong><?= htmlspecialchars(__('period'), ENT_QUOTES, 'UTF-8') ?>:</strong>
        <?= htmlspecialchars($date_from) ?> <?= htmlspecialchars(__('to'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($date_to) ?>
    </div>

    <!-- Ringkasan Statistik Laporan -->
    <table class="pdf-table" style="margin-bottom:20px;">
        <tr>
            <td class="pdf-summary-cell">
                <small><?= htmlspecialchars(__('total_products'), ENT_QUOTES, 'UTF-8') ?></small><br>
                <strong><?= number_format($total_products) ?></strong>
            </td>
            <td class="pdf-summary-cell">
                <small><?= htmlspecialchars(__('total_stock'), ENT_QUOTES, 'UTF-8') ?></small><br>
                <strong><?= number_format($total_stock) ?></strong>
            </td>
            <td class="pdf-summary-cell">
                <small><?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?></small><br>
                <strong><?= number_format($total_stock_in) ?></strong>
            </td>
            <td class="pdf-summary-cell">
                <small><?= htmlspecialchars(__('stock_out'), ENT_QUOTES, 'UTF-8') ?></small><br>
                <strong><?= number_format($total_stock_out) ?></strong>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="pdf-summary-cell">
                <small><?= htmlspecialchars(__('total_capital'), ENT_QUOTES, 'UTF-8') ?></small><br>
                <strong><?= htmlspecialchars(formatMoneyPHP($total_modal_value, $business['currency'])) ?></strong>
            </td>
            <td colspan="2" class="pdf-summary-cell">
                <small><?= htmlspecialchars(__('profit'), ENT_QUOTES, 'UTF-8') ?></small><br>
                <strong><?= htmlspecialchars(formatMoneyPHP($total_profit, $business['currency'])) ?></strong>
            </td>
        </tr>
    </table>

    <!-- Tabel Rincian Produk -->
    <table class="pdf-table">
        <thead>
            <tr>
                <?php foreach (['product_name', 'stock_in', 'stock_out', 'profit', 'current_stock'] as $col): ?>
                    <th><?= htmlspecialchars(__($col), ENT_QUOTES, 'UTF-8') ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($reports) === 0): ?>
                <tr>
                    <td colspan="5" style="text-align:center;"><?= htmlspecialchars(__('no_data'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($reports)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= number_format((int) $row['stock_in']) ?></td>
                        <td><?= number_format((int) $row['stock_out']) ?></td>
                        <td><?= htmlspecialchars(formatMoneyPHP($row['profit'], $business['currency'])) ?></td>
                        <td><?= number_format((int) $row['current_stock']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
