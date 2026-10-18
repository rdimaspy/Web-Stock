<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Pengaturan bahasa
require_once __DIR__ . '/../includes/language.php';

// Formatter mata uang
require_once __DIR__ . '/../includes/currency.php';

// Ambil parameter filter rentang tanggal (default ke awal bulan ini hingga hari ini)
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

// Menyiapkan query SQL untuk mengambil 10 produk dengan barang keluar terbanyak
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

// Eksekusi prepared statement query data grafik
$stmt = mysqli_prepare($conn, $sql_chart);
mysqli_stmt_bind_param($stmt, "ssi", $date_from, $date_to, $business_id);
mysqli_stmt_execute($stmt);
$res_chart = mysqli_stmt_get_result($stmt);

// Inisialisasi array penampung data untuk grafik Chart.js
$product_names = [];
$stock_ins     = [];
$stock_outs    = [];
$profits       = [];

// Looping untuk memasukkan hasil query ke masing-masing array data grafik
while ($row = mysqli_fetch_assoc($res_chart)) {
    $product_names[] = $row['product_name'];
    $stock_ins[]     = (int)$row['stock_in'];
    $stock_outs[]    = (int)$row['stock_out'];
    $profits[]       = (float)$row['profit'];
}

?>



<!DOCTYPE html>
<!-- Set bahasa -->
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('analytics_chart'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>

    <!-- Library Chart.js untuk merender grafik secara interaktif -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- HTML2PDF Library untuk cetak grafik Chart.js ke PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        /* Tampilan khusus saat Print / Preview PDF */
        @media print {
            /* Sembunyikan seluruh elemen kecuali #reportContent */
            body * {
                visibility: hidden !important;
            }
            
            #reportContent, #reportContent * {
                visibility: visible !important;
            }

            /* Posisikan #reportContent tepat di bagian paling atas dokumen */
            #reportContent {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Pengaturan kertas cetak A4 Landscape */
            @page {
                size: A4 landscape;
                margin: 12mm;
            }

            /* Tampilkan Kop Laporan saat cetak */
            .print-header {
                display: block !important;
            }

            /* Kunci kontainer grafik agar canvas tidak lepas dari judulnya */
            .chart-container-print {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                border: 1px solid #E9E1D7 !important;
                box-shadow: none !important;
                margin-bottom: 0 !important;
            }

            /* Paksa Grafik 2 pindah ke Halaman 2 secara rapi */
            .print-page-break {
                page-break-before: always !important;
                break-before: page !important;
            }

            /* Pastikan canvas Chart.js terisi penuh dalam kontainernya */
            canvas {
                max-width: 100% !important;
                max-height: 320px !important;
            }
        }
    </style>

</head>

<!-- Styling utama layout dan warna latar halaman -->
<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'reports-analytics.php';?>

<!-- Layout utama halaman analisis laporan-->
<div style="display:flex; min-height:100vh;">

    <!-- SIDEBAR -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- KONTEN UTAMA -->
    <main style="margin-left:230px; flex:1; min-width:0;">
        
        <!-- HEADER -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- HALAMAN REPORT-->
        <section style="padding:32px;">
            <div style="margin-bottom:25px;">
                <!-- Judul halaman -->
                <h1 style="margin:0; font-size:29px;"><?= htmlspecialchars(__('analytics_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <!-- Deskripsi -->
                <p style="color:#81776D; margin-top:10px; line-height:1.6;"><?= htmlspecialchars(__('analytics_desc'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <!-- Container form filter tanggal dan tombol ekspor PDF -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:22px; margin-bottom:25px;">
                <form method="GET" style="display:flex; gap:15px; align-items:end; flex-wrap:wrap;">
                    <!-- Parameter ID bisnis tersembunyi agar ID bisnis aktif tidak hilang saat memfilter tanggal -->
                    <input type="hidden" name="business_id" value="<?= (int)$business_id ?>">

                    <!-- Input tanggal awal filter analitik -->
                    <div>
                        <label style="display:block; margin-bottom:7px; font-weight:600;"><?= htmlspecialchars(__('date_from'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                    </div>

                    <!-- Input tanggal akhir filter analitik -->
                    <div>
                        <label style="display:block; margin-bottom:7px; font-weight:600;"><?= htmlspecialchars(__('date_to'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                    </div>

                    <!-- Tombol terapkan filter tanggal -->
                    <button type="submit" style="height:40px; padding:0 20px; border:0; border-radius:9px; background:#76553D; color:white; font-weight:600; cursor:pointer;">
                        <?= htmlspecialchars(__('apply'), ENT_QUOTES, 'UTF-8') ?>
                    </button>

                    <!-- Tombol untuk memicu fungsi cetak/ekspor dokumen PDF -->
                    <button type="button" onclick="exportReportToPDF()" style="display:flex; align-items:center; gap:8px; height:40px; padding:0 16px; background:#76553D; color:white; border:none; border-radius:9px; cursor:pointer; font-weight:600;">
                        <i data-lucide="file-down" style="width:18px; height:18px;"></i>
                        <?= htmlspecialchars(__('export_pdf'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            </div>

            <!-- Area Konten Laporan -->
            <div id="reportContent">

                <!-- KOP LAPORAN -->
                <div class="print-header" style="text-align:center; margin-bottom:15px; display:none;">
                    <!-- Nama Bisnis / Toko -->
                    <h2 style="margin:0; font-size:22px; color:#302921; text-transform:uppercase;">
                        <?= htmlspecialchars($business['business_name'] ?? 'NAMA TOKO', ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                    <!-- Judul Resmi Laporan -->
                    <p style="margin:4px 0 0; font-size:13px; color:#81776D; font-weight:600;">
                        <?= htmlspecialchars(__('analytics_report_title'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <!-- Rentang Periode Tanggal Laporan -->
                    <p style="margin:4px 0 0; font-size:12px; color:#81776D;">
                        <?= htmlspecialchars(__('period'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($date_from) ?> <?= htmlspecialchars(__('to'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($date_to) ?>
                    </p>
                    <!-- Pembaatas -->
                    <hr style="border:0; border-top:2px solid #76553D; margin:12px 0 18px;">
                </div>

                <!-- HALAMAN 1: Grafik Pergerakan Stok -->
                <div class="chart-container-print" style="background: white; border: 1.5px solid #E9E1D7; border-radius: 12px; padding: 20px;">
                    <!-- Judul Grafik Pergerakan Stok -->
                    <h3 style="font-size: 18px; color: #76553D; margin-top: 0; margin-bottom: 15px;">
                        <?= htmlspecialchars(__('stock_movement_chart'), ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <!-- Pembungkus Canvas Grafik Chart.js -->
                    <div style="position: relative; width: 100%; height: 350px;">
                        <canvas id="stockMovementChart"></canvas>
                    </div>
                </div>

                <!-- HALAMAN 2: Grafik Tren Keuntungan -->
                <div class="chart-container-print print-page-break" style="background: white; border: 1.5px solid #E9E1D7; border-radius: 12px; padding: 20px; margin-top: 20px;">
                    <!-- Kop Laporan ulang di halaman 2 agar tetap resmi -->
                    <div class="print-header" style="text-align:center; margin-bottom:15px; display:none;">
                        <!-- Nama Bisnis / Toko -->
                        <h2 style="margin:0; font-size:22px; color:#302921; text-transform:uppercase;">
                            <?= htmlspecialchars($business['business_name'] ?? 'NAMA TOKO', ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <!-- Judul Resmi Laporan -->
                        <p style="margin:4px 0 0; font-size:13px; color:#81776D; font-weight:600;">
                            <?= htmlspecialchars(__('analytics_report_title'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <!-- Rentang Periode Tanggal Laporan -->
                        <p style="margin:4px 0 0; font-size:12px; color:#81776D;">
                            <?= htmlspecialchars(__('period'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($date_from) ?> <?= htmlspecialchars(__('to'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($date_to) ?>
                        </p>
                        <hr style="border:0; border-top:2px solid #76553D; margin:12px 0 18px;">
                    </div>

                    <!-- Judul Grafik Tren Keuntungan -->
                    <h3 style="font-size: 18px; color: #2E5A27; margin-top: 0; margin-bottom: 15px;">
                        <?= htmlspecialchars(__('profit_trend_chart'), ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <!-- Pembungkus Canvas Grafik Chart.js -->
                    <div style="position: relative; width: 100%; height: 350px;">
                        <canvas id="profitTrendChart"></canvas>
                    </div>
                </div>

            </div>

        </section>
    </main>
</div>

<!-- Ikon -->
<script src="https://unpkg.com/lucide@latest"></script>

<script>
lucide.createIcons();

// Konversi array data PHP ke format JSON agar bisa dibaca langsung oleh JavaScript
const productLabels = <?= json_encode($product_names) ?>;
const stockInValues  = <?= json_encode($stock_ins) ?>;
const stockOutValues = <?= json_encode($stock_outs) ?>;
const profitValues   = <?= json_encode($profits) ?>;

// Inisialisasi grafik (Bar Chart) untuk perbandingan stok masuk vs stok keluar
new Chart(document.getElementById('stockMovementChart'), {
    type: 'bar',
    data: {
        labels: productLabels,
        datasets: [
            // Dataset untuk kuantitas barang masuk
            { label: <?= json_encode(__('stock_in')) ?>, data: stockInValues, backgroundColor: '#76553D' },
            
            // Dataset untuk kuantitas barang keluar
            { label: <?= json_encode(__('stock_out')) ?>, data: stockOutValues, backgroundColor: '#B99A7D' }
        ]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } } 
    }
});

// Inisialisasi grafik batang (Bar Chart) untuk nilai estimasi keuntungan per produk
new Chart(document.getElementById('profitTrendChart'), {
    type: 'bar',
    data: {
        labels: productLabels,
        datasets: [
            // Dataset untuk estimasi keuntungan
            { label: <?= json_encode(__('profit')) ?>, data: profitValues, backgroundColor: '#2E7D32' }
        ]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } } 
    }
});

// Fungsi untuk mengonversi tampilan grafik analitik ke dokumen PDF
function exportReportToPDF() {
    // Membuka jendela Print Preview resmi bawaan browser dengan layout terkonsep
    window.print();
}

</script>

</body>
</html>