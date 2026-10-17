<?php
// Cek sesi autentikasi pengguna
session_start();
if (!isset($_SESSION['user_id'])) {
    exit("Akses ditolak.");
}

// Memuat file konfigurasi database, bahasa, dan formatter mata uang
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/currency.php';

// Validasi ID bisnis aktif dari parameter URL
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);
if (!$business_id) {
    exit("Bisnis tidak valid.");
}

// Menyiapkan query untuk mengambil data nama bisnis dan mata uang aktif
$stmt_b = mysqli_prepare($conn, "SELECT business_name, currency FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_b, "ii", $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt_b);
$business = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_b));
if (!$business) {
    exit("Bisnis tidak ditemukan.");
}

// Filter tanggal laporan (default ke tanggal awal bulan ini dan hari ini)
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

// Menyiapkan query SQL utama untuk mengambil akumulasi transaksi produk
$sql = "
SELECT p.product_name, p.current_stock,
    COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_in' THEN it.quantity ELSE 0 END), 0) AS stock_in,
    COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_out' THEN it.quantity ELSE 0 END), 0) AS stock_out,
    COALESCE(SUM(CASE WHEN it.transaction_type = 'stock_out' THEN (it.selling_price - it.purchase_price) * it.quantity ELSE 0 END), 0) AS profit
FROM products p
LEFT JOIN inventory_transactions it ON p.id = it.product_id AND DATE(it.transaction_date) BETWEEN ? AND ?
WHERE p.business_id = ?
GROUP BY p.id ORDER BY p.product_name ASC";

// Eksekusi prepared statement query laporan produk
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssi", $date_from, $date_to, $business_id);
mysqli_stmt_execute($stmt);
$reports = mysqli_stmt_get_result($stmt);

// Inisialisasi variabel penampung statistik ringkasan laporan
$total_products  = 0;
$total_stock     = 0;
$total_stock_in  = 0;
$total_stock_out = 0;
$total_profit    = 0;

// Looping akumulasi untuk menghitung total statistik keseluruhan produk
while ($row = mysqli_fetch_assoc($reports)) {
    $total_products++;
    $total_stock     += (int) $row['current_stock'];
    $total_stock_in  += (int) $row['stock_in'];
    $total_stock_out += (int) $row['stock_out'];
    $total_profit    += (float) $row['profit'];
}

// Kembalikan penunjuk baca ke baris pertama agar tabel di bawah tetap bisa di-looping dari awal
mysqli_data_seek($reports, 0);

// Menyiapkan query untuk menghitung total harga modal bisnis
$stmt_m = mysqli_prepare($conn, "SELECT COALESCE(SUM(current_stock * purchase_price), 0) AS total_modal FROM products WHERE business_id = ?");
mysqli_stmt_bind_param($stmt_m, "i", $business_id);
mysqli_stmt_execute($stmt_m);

// Ambil nilai total modal barang (set ke 0 jika bernilai null)
$total_modal_value = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_m))['total_modal'] ?? 0);
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Stok_<?= htmlspecialchars($business['business_name']) ?></title>
    <style>
        /* CSS khusus format tampilan dokumen cetak / PDF */
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

    <!-- Tombol pemicu cetak manual untuk cadangan jika tidak berjalan otomatis -->
    <div class="no-print" style="margin-bottom:15px; text-align:right;">
        <button onclick="window.print()" style="padding:8px 16px; background:#76553D; color:white; border:0; border-radius:6px; cursor:pointer;">
            Cetak / Simpan PDF
        </button>
    </div>

    <!-- Judul dan header dokumen cetak -->
    <div class="header">
        <h2><?= htmlspecialchars($business['business_name']) ?></h2>
        <p style="margin:5px 0 0;">Laporan Performa Stok & Inventaris</p>
    </div>

    <!-- Informasi periode filter laporan -->
    <div class="info">
        <span><strong>Periode:</strong> <?= htmlspecialchars($date_from) ?> s/d <?= htmlspecialchars($date_to) ?></span>
    </div>

    <!-- Tabel ringkasan statistik (Summary Cards) pada cetakan PDF -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
        <tr>
            <!-- Card Total Produk -->
            <td style="padding:10px; border:1px solid #ccc; background:#f9f9f9;">
                <small>Total Produk</small><br>
                <strong><?= number_format($total_products) ?></strong>
            </td>

            <!-- Card Total Stok -->
            <td style="padding:10px; border:1px solid #ccc; background:#f9f9f9;">
                <small>Total Stok</small><br>
                <strong><?= number_format($total_stock) ?></strong>
            </td>

            <!-- Card Total Stok Masuk -->
            <td style="padding:10px; border:1px solid #ccc; background:#f9f9f9;">
                <small>Stok Masuk</small><br>
                <strong><?= number_format($total_stock_in) ?></strong>
            </td>

            <!-- Card Total Stok Keluar -->
            <td style="padding:10px; border:1px solid #ccc; background:#f9f9f9;">
                <small>Stok Keluar</small><br>
                <strong><?= number_format($total_stock_out) ?></strong>
            </td>
        </tr>
        <tr>
            <!-- Card Total Harga Modal -->
            <td colspan="2" style="padding:10px; border:1px solid #ccc; background:#f9f9f9;">
                <small>Total Harga Modal</small><br>
                <strong><?= formatMoneyPHP($total_modal_value, $business['currency']) ?></strong>
            </td>

            <!-- Card Total Keuntungan -->
            <td colspan="2" style="padding:10px; border:1px solid #ccc; background:#f9f9f9;">
                <small>Keuntungan</small><br>
                <strong><?= formatMoneyPHP($total_profit, $business['currency']) ?></strong>
            </td>
        </tr>
    </table>

    <!-- Tabel Rincian Data Laporan Produk -->
    <table>
        <thead>
            <tr>
                <th>Nama Produk</th>
                <th>Stok Masuk</th>
                <th>Stok Keluar</th>
                <th>Keuntungan</th>
                <th>Stok Saat Ini</th>
            </tr>
        </thead>
        <tbody>
            <!-- Cek jika tidak ada data laporan -->
            <?php if (mysqli_num_rows($reports) === 0): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">Tidak ada data laporan.</td>
                </tr>
            <?php else: ?>
                <!-- Looping menampilkan baris data produk -->
                <?php while ($row = mysqli_fetch_assoc($reports)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= number_format((int)$row['stock_in']) ?></td>
                        <td><?= number_format((int)$row['stock_out']) ?></td>
                        <td><?= htmlspecialchars(formatMoneyPHP($row['profit'], $business['currency'])) ?></td>
                        <td><?= number_format((int)$row['current_stock']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>