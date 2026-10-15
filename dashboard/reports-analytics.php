<?php
// Validasi sesi user dan koneksi database
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/currency.php';

$currentPage = 'reports-analytics.php';

// Validasi ID bisnis aktif
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);
if (!$business_id || $business_id < 1) {
    header("Location: ../businesses.php");
    exit;
}

// Ambil data bisnis milik user
$stmt = mysqli_prepare($conn, "SELECT id, business_name, currency FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$business = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$business) {
    http_response_code(404);
    exit("Bisnis tidak ditemukan.");
}

// Filter rentang tanggal
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

// Query data pergerakan stok per produk untuk grafik
$sql_chart = "
SELECT p.product_name,
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

// Array data untuk dikirim ke Chart.js
$product_names = [];
$stock_ins     = [];
$stock_outs    = [];
$profits       = [];

while ($row = mysqli_fetch_assoc($res_chart)) {
    $product_names[] = $row['product_name'];
    $stock_ins[]     = (int)$row['stock_in'];
    $stock_outs[]    = (int)$row['stock_out'];
    $profits[]       = (float)$row['profit'];
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('analytics_chart'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>
    <!-- Library Chart.js untuk merender grafik secara interaktif -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<div style="display:flex; min-height:100vh;">
    <!-- Sidebar Navigasi -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <main style="margin-left:230px; flex:1; min-width:0;">
        <!-- Header Utama -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <section style="padding:32px;">
            <div style="margin-bottom:25px;">
                <h1 style="margin:0; font-size:29px;"><?= htmlspecialchars(__('analytics_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p style="color:#81776D; margin-top:10px; line-height:1.6;"><?= htmlspecialchars(__('analytics_desc'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <!-- Form Filter Tanggal -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:22px; margin-bottom:25px;">
                <form method="GET" style="display:flex; gap:15px; align-items:end; flex-wrap:wrap;">
                    <input type="hidden" name="business_id" value="<?= (int)$business_id ?>">
                    <div>
                        <label style="display:block; margin-bottom:7px; font-weight:600;"><?= htmlspecialchars(__('date_from'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:7px; font-weight:600;"><?= htmlspecialchars(__('date_to'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                    </div>
                    <button type="submit" style="height:40px; padding:0 20px; border:0; border-radius:9px; background:#76553D; color:white; font-weight:600; cursor:pointer;">
                        <?= htmlspecialchars(__('apply'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            </div>

            <!-- Container Grid Grafik -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <!-- Grafik 1: Pergerakan Stok Masuk vs Keluar -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <h3 style="margin-top:0; color:#76553D; font-size:16px; margin-bottom:15px;"><?= htmlspecialchars(__('stock_movement_chart'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <canvas id="stockChart"></canvas>
                </div>

                <!-- Grafik 2: Keuntungan per Produk -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <h3 style="margin-top:0; color:#2E7D32; font-size:16px; margin-bottom:15px;"><?= htmlspecialchars(__('profit_trend_chart'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <canvas id="profitChart"></canvas>
                </div>
            </div>
        </section>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    // Data PHP di-parse ke variabel JavaScript
    const productLabels = <?= json_encode($product_names) ?>;
    const stockInValues  = <?= json_encode($stock_ins) ?>;
    const stockOutValues = <?= json_encode($stock_outs) ?>;
    const profitValues   = <?= json_encode($profits) ?>;

    // Render Bar Chart untuk Pergerakan Stok Masuk vs Keluar
    new Chart(document.getElementById('stockChart'), {
        type: 'bar',
        data: {
            labels: productLabels,
            datasets: [
                { label: 'Stok Masuk', data: stockInValues, backgroundColor: '#76553D' },
                { label: 'Stok Keluar', data: stockOutValues, backgroundColor: '#B99A7D' }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    // Render Bar Chart untuk Profit
    new Chart(document.getElementById('profitChart'), {
        type: 'bar',
        data: {
            labels: productLabels,
            datasets: [
                { label: 'Profit', data: profitValues, backgroundColor: '#2E7D32' }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
</script>
</body>
</html>