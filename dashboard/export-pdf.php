<?php
// Cek sesi autentikasi user
session_start();
if (!isset($_SESSION['user_id'])) {
    exit("Akses ditolak.");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/currency.php';

// Validasi business_id aktif
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);
if (!$business_id) {
    exit("Bisnis tidak valid.");
}

// Ambil data detail bisnis
$stmt_b = mysqli_prepare($conn, "SELECT business_name, currency FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_b, "ii", $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt_b);
$business = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_b));
if (!$business) {
    exit("Bisnis tidak ditemukan.");
}

// Filter tanggal laporan
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

// Query data laporan produk
$sql = "
SELECT p.product_name, p.current_stock,
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

// Query total modal
$stmt_m = mysqli_prepare($conn, "SELECT COALESCE(SUM(current_stock * purchase_price), 0) AS total_modal FROM products WHERE business_id = ?");
mysqli_stmt_bind_param($stmt_m, "i", $business_id);
mysqli_stmt_execute($stmt_m);
$total_modal = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_m))['total_modal'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Stok_<?= htmlspecialchars($business['business_name']) ?></title>
    <style>
        /* CSS khusus cetak/PDF */
        body { font-family: Arial, sans-serif; color: #333; margin: 20px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #76553D; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #76553D; }
        .info { margin-bottom: 15px; display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #F5EFE8; color: #76553D; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <!-- Tombol Cetak Manual jika tidak otomatis -->
    <div class="no-print" style="margin-bottom:15px; text-align:right;">
        <button onclick="window.print()" style="padding:8px 16px; background:#76553D; color:white; border:0; border-radius:6px; cursor:pointer;">
            Cetak / Simpan PDF
        </button>
    </div>

    <!-- Header Dokumen Cetak -->
    <div class="header">
        <h2><?= htmlspecialchars($business['business_name']) ?></h2>
        <p style="margin:5px 0 0;">Laporan Performa Stok & Inventaris</p>
    </div>

    <div class="info">
        <span><strong>Periode:</strong> <?= htmlspecialchars($date_from) ?> s/d <?= htmlspecialchars($date_to) ?></span>
        <span><strong>Total Nilai Modal:</strong> <?= htmlspecialchars(formatMoneyPHP($total_modal, $business['currency'])) ?></span>
    </div>

    <!-- Tabel Data Cetak -->
    <table>
        <thead>
            <tr>
                <th>Nama Produk</th>
                <th>Stok Masuk</th>
                <th>Stok Keluar</th>
                <th>Keuntungan</th>
                <th>Sisa Stok</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($reports)): ?>
            <tr>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= number_format((int)$row['stock_in']) ?></td>
                <td><?= number_format((int)$row['stock_out']) ?></td>
                <td><?= htmlspecialchars(formatMoneyPHP($row['profit'], $business['currency'])) ?></td>
                <td><?= number_format((int)$row['current_stock']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>