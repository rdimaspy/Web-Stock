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
        /* Mengamankan render laporan agar penuh saat di-export */
        #reportContent {
            max-width: 100% !important;
            width: 100% !important;
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
                        <?= $lang === 'en' ? 'Export PDF' : 'Ekspor PDF' ?>
                    </button>
                </form>
            </div>

            <!-- Area Konten Laporan (Bisa Di-export ke PDF) -->
            <div id="reportContent">
                <!-- Container Utama Grafik (Vertikal: Atas & Bawah) -->
                <div style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 25px;">

                    <!-- Container Grafik 1: Pergerakan Stok Barang -->
                    <div style="background: white; border: 1.5px solid #E9E1D7; border-radius: 12px; padding: 20px;">
                        <!-- Judul grafik pergerakan stok berdasarkan bahasa aktif -->
                        <h3 style="font-size: 18px; color: #76553D; margin-top: 0; margin-bottom: 15px;">
                            <?= $lang === 'en' ? 'Stock Movement Chart' : 'Grafik Pergerakan Stok' ?>
                        </h3>

                        <!-- Pembungkus canvas grafik pergerakan stok -->
                        <div style="position: relative; width: 100%; height: 350px;">
                            <!-- Elemen canvas tempat Chart.js merender grafik barang masuk dan keluar -->
                            <canvas id="stockMovementChart"></canvas>
                        </div>
                    </div>

                    <!-- Container Grafik 2: Tren Keuntungan Produk -->
                    <div style="background: white; border: 1.5px solid #E9E1D7; border-radius: 12px; padding: 20px;">
                        <!-- Judul grafik tren keuntungan berdasarkan bahasa aktif -->
                        <h3 style="font-size: 18px; color: #2E5A27; margin-top: 0; margin-bottom: 15px;">
                            <?= $lang === 'en' ? 'Profit Trend Chart' : 'Grafik Tren Keuntungan' ?>
                        </h3>

                        <!-- Pembungkus canvas grafik tren keuntungan -->
                        <div style="position: relative; width: 100%; height: 350px;">
                            <!-- Elemen canvas tempat Chart.js merender grafik estimasi profit -->
                            <canvas id="profitTrendChart"></canvas>
                        </div>
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
            { label: '<?= $lang === 'en' ? 'Stock In' : 'Stok Masuk' ?>', data: stockInValues, backgroundColor: '#76553D' },
            
            // Dataset untuk kuantitas barang keluar
            { label: '<?= $lang === 'en' ? 'Stock Out' : 'Stok Keluar' ?>', data: stockOutValues, backgroundColor: '#B99A7D' }
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
            { label: '<?= $lang === 'en' ? 'Profit' : 'Keuntungan' ?>', data: profitValues, backgroundColor: '#2E7D32' }
        ]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } } 
    }
});

// Fungsi asinkron untuk mengonversi tampilan grafik analitik ke dokumen PDF
async function exportReportToPDF() {
    // Ambil elemen container utama yang berisi tampilan grafik dan data analitik
    const element = document.getElementById('reportContent');
    const fileName = `Laporan_Analitik_<?= date('Y-m-d') ?>.pdf`;

    // Konfigurasi atribut dan resolusi untuk pembuatan file PDF lewat html2pdf
    const opt = {
        margin:       [10, 10, 10, 10],
        filename:     fileName,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    // Gunakan fitur modern File System Access API jika didukung oleh browser
    if ('showSaveFilePicker' in window) {
        try {
            // Tampilkan dialog pilihan lokasi penyimpanan file PDF kepada pengguna
            const handle = await window.showSaveFilePicker({
                suggestedName: fileName,
                types: [{
                    description: 'PDF Document',
                    accept: { 'application/pdf': ['.pdf'] },
                }],
            });

            // File PDF ke bentuk binary Blob lalu tulis data ke lokasi yang dipilih
            const pdfBlob = await html2pdf().set(opt).from(element).output('blob');
            const writable = await handle.createWritable();
            await writable.write(pdfBlob);
            await writable.close();

            // Tampilkan notifikasi berhasil ekspor PDF
            alert(<?= json_encode($lang === 'en' ? 'PDF exported successfully!' : 'PDF berhasil diekspor!') ?>);
            return;
        } catch (err) {
            // Abaikan penanganan error jika pengguna membatalkan dialog simpan file
            if (err.name === 'AbortError') return;
        }
    }

    // Alternatif unduhan otomatis (fallback) untuk browser yang belum mendukung Save File Picker
    html2pdf().set(opt).from(element).save();
}

</script>

</body>
</html>