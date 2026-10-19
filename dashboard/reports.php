<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Pengaturan bahasa
require_once __DIR__ . '/../includes/language.php';

// Formatter mata uang
require_once __DIR__ . '/../includes/currency.php';

// Filter tanggal laporan
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

// Validasi format tanggal awal, set default ke hari pertama bulan ini jika tidak valid
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = date('Y-m-01');
}

// Validasi format tanggal akhir, set default ke tanggal hari ini jika tidak valid
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = date('Y-m-d');
}

// Daftar kolom laporan yang diizinkan untuk pengurutan data
$allowedSort = [
    'product_name',
    'stock_in',
    'stock_out',
    'profit',
    'current_stock'
];

// Ambil parameter opsi urutan dari URL, atur default ke 'profit' jika kosong
$sort = $_GET['sort'] ?? 'profit';

// Validasi kolom pengurutan agar hanya menggunakan kolom yang diizinkan
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'profit';
}

// Ambil arah pengurutan (ASC/DESC)
$order = strtoupper($_GET['order'] ?? 'DESC');

// Validasi arah pengurutan agar hanya bernilai ASC atau DESC 
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

// Menyiapkan query SQL utama untuk akumulasi laporan barang dan keuntungan
$sql = "
SELECT
    p.id,
    p.product_name,
    p.current_stock,

    COALESCE(SUM(
        CASE
            WHEN it.transaction_type = 'stock_in'
            THEN it.quantity
            ELSE 0
        END
    ), 0) AS stock_in,

    COALESCE(SUM(
        CASE
            WHEN it.transaction_type = 'stock_out'
            THEN it.quantity
            ELSE 0
        END
    ), 0) AS stock_out,

    COALESCE(SUM(
        CASE
            WHEN it.transaction_type = 'stock_out'
            THEN (it.selling_price - it.purchase_price) * it.quantity
            ELSE 0
        END
    ), 0) AS profit

FROM products p

LEFT JOIN inventory_transactions it
ON p.id = it.product_id
AND DATE(it.transaction_date) BETWEEN ?
AND ?

WHERE p.business_id = ?

GROUP BY p.id

ORDER BY $sort $order
";

// Menyiapkan prepared statement untuk query utama laporan
$stmt = mysqli_prepare($conn, $sql);

// Mengikat parameter filter tanggal awal, tanggal akhir, dan ID bisnis ke query
mysqli_stmt_bind_param(
    $stmt,
    "ssi",
    $date_from,
    $date_to,
    $business_id
);

// Mengeksekusi statement query dan mengambil hasil data laporan
mysqli_stmt_execute($stmt);
$reports = mysqli_stmt_get_result($stmt);

// Summary laporan
$total_products = 0;
$total_stock = 0;
$total_stock_in = 0;
$total_stock_out = 0;
$total_profit = 0;

// Ambil ulang data laporan untuk summary
mysqli_data_seek($reports, 0);

// Looping akumulasi untuk menghitung total statistik keseluruhan produk
while ($row = mysqli_fetch_assoc($reports)) {
    $total_products++;
    $total_stock += (int) $row['current_stock'];
    $total_stock_in += (int) $row['stock_in'];
    $total_stock_out += (int) $row['stock_out'];
    $total_profit += (float) $row['profit'];
}

// Buat menghitung Total Harga Modal
$stmt_modal = mysqli_prepare(
    $conn,
    "SELECT COALESCE(SUM(current_stock * purchase_price), 0) AS total_modal 
    FROM products 
    WHERE business_id = ?"
);

// Mengikat parameter ID bisnis, eksekusi query, dan ambil total aset modal
mysqli_stmt_bind_param($stmt_modal, "i", $business_id);
mysqli_stmt_execute($stmt_modal);
$res_modal = mysqli_stmt_get_result($stmt_modal);
$row_modal = mysqli_fetch_assoc($res_modal);

// Ambil nilai total modal barang (isi angka 0 jika belum ada data modal)
$total_modal_value = (float)($row_modal['total_modal'] ?? 0);

// Kembalikan ulsng data agar tabel HTML bisa menampilkan semua data dari awal
mysqli_data_seek($reports, 0);

?>



<!DOCTYPE html>
<!-- Set bahasa -->
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('reports'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>
</head>

<!-- Styling utama layout dan warna latar halaman -->
<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'reports.php';?>

<!-- Layout utama halaman laporan-->
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
                <h1 style="margin:0; font-size:29px;">
                    <?= htmlspecialchars(__('reports'), ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <!-- Deskripsi -->
                <p style="color:#81776D; margin-top:10px; line-height:1.6;">
                    <?= htmlspecialchars(__('reports_description'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- Filter laporan -->
            <div style="background:white;border:1px solid #E9E1D7; border-radius:12px; padding:22px; margin-bottom:25px;">
                <form method="GET">
                    <!-- Parameter ID bisnis tersembunyi agar posisi bisnis aktif tidak berubah saat filter dikirim -->
                    <input type="hidden" name="business_id" value="<?= (int)$business_id ?>">
                    <div style="display:flex; gap:15px; align-items:end; flex-wrap:wrap;">

                        <!-- Input tanggal awal laporan -->
                        <div>
                            <label style="display:block; margin-bottom:7px; font-weight:600;">
                                <?= htmlspecialchars(__('date_from'), ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                        </div>

                        <!-- Input tanggal akhir laporan -->
                        <div>
                            <label style="display:block; margin-bottom:7px; font-weight:600;">
                                <?= htmlspecialchars(__('date_to'), ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                        </div>

                        <!-- Dropdown pilihan kolom untuk pengurutan data laporan -->
                        <div>
                            <label style="display:block; margin-bottom:7px; font-weight:600;">
                                <?= htmlspecialchars(__('sort_by'), ENT_QUOTES, 'UTF-8') ?>
                            </label>

                            <select name="sort"
                                style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">

                                <!-- Opsi urutkan berdasarkan keuntungan -->
                                <option value="profit" <?= $sort === 'profit' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('profit'), ENT_QUOTES, 'UTF-8') ?>
                                </option>

                                <!-- Opsi urutkan berdasarkan jumlah barang masuk -->
                                <option value="stock_in" <?= $sort === 'stock_in' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?>
                                </option>

                                <!-- Opsi urutkan berdasarkan jumlah barang keluar -->
                                <option value="stock_out" <?= $sort === 'stock_out' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('stock_out'), ENT_QUOTES, 'UTF-8') ?>
                                </option>

                                <!-- Opsi urutkan berdasarkan jumlah stok saat ini -->
                                <option value="current_stock" <?= $sort === 'current_stock' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('current_stock'), ENT_QUOTES, 'UTF-8') ?>
                                </option>

                                <!-- Opsi urutkan berdasarkan nama produk -->
                                <option value="product_name" <?= $sort === 'product_name' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            </select>
                        </div>

                        <!-- Dropdown arah pengurutan -->
                        <div>
                            <label style="display:block; margin-bottom:7px; font-weight:600;">
                                <?= htmlspecialchars(__('order'), ENT_QUOTES, 'UTF-8') ?>
                            </label>

                            <select name="order" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">   
                                <!-- Opsi pengurutan data DESC -->
                                <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('descending'), ENT_QUOTES, 'UTF-8') ?>
                                </option>

                                <!-- Opsi pengurutan data ASC -->
                                <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('ascending'), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            </select>
                        </div>

                        <!-- Tombol terapkan -->
                        <button type="submit"
                            style="height:40px; padding:0 20px; border:0; border-radius:9px; background:#76553D; color:white; font-weight:600; cursor:pointer;">
                            <?= htmlspecialchars(__('apply'), ENT_QUOTES, 'UTF-8') ?>
                        </button>

                    </div>
                </form>
            </div>

            <!-- Summary Report -->
            <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; margin-bottom:25px;">

                <!-- Total Produk -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <!-- judul -->
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('total_products'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <h2 style="margin:10px 0 0;">
                        <?= number_format($total_products) ?>
                    </h2>
                </div>

                <!-- Total Stok -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <!-- judul -->
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('total_stock'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <h2 style="margin:10px 0 0;">
                        <?= number_format($total_stock) ?>
                    </h2>
                </div>

                <!-- Stock In -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <!-- judul -->
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <!-- total -->
                    <h2 style="margin:10px 0 0;">
                        <?= number_format($total_stock_in) ?>
                    </h2>
                </div>

                <!-- Stock Out -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <!-- judul -->
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('stock_out'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <!-- total -->
                    <h2 style="margin:10px 0 0;">
                        <?= number_format($total_stock_out) ?>
                    </h2>
                </div>

                <!-- Total Harga Modal -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <!-- judul -->
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('total_capital'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <!-- total -->
                    <h2 style="margin:10px 0 0; color:#76553D;">
                        <?= htmlspecialchars(formatMoneyPHP($total_modal_value, $business['currency']), ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                </div>

                <!-- Profit -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px; grid-column:span 2;">
                    <!-- judul -->
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('profit'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <!-- total -->
                    <h2 style="margin:10px 0 0;">
                        <?= htmlspecialchars(formatMoneyPHP($total_profit, $business['currency']), ENT_QUOTES, 'UTF-8'
                        ) ?>
                    </h2>
                </div>
            </div>

            <!-- TABEL LAPORAN -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; overflow:hidden;">

                <!-- Header Table -->
                <div style="padding:18px 20px; border-bottom:1px solid #E9E1D7; font-weight:bold; display:flex; justify-content:space-between; align-items:center;">
                    <span><?= htmlspecialchars(__('product_report'), ENT_QUOTES, 'UTF-8') ?></span>

                    <div style="display:flex; gap:10px; align-items:center;">
                        <!-- Tombol Ekspor CSV -->
                        <button type="button" onclick="exportTableToCSV('laporan_stok.csv')" style="padding:8px 16px; border:1px solid #E9E1D7; border-radius:8px; background:#76553D; color:white; font-weight:600; cursor:pointer; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                            <i data-lucide="download" style="width:16px; height:16px;"></i>
                            <?= htmlspecialchars(__('export_csv'), ENT_QUOTES, 'UTF-8') ?>
                        </button>

                        <!-- Tombol cetak PDF -->
                        <button type="button" onclick="printPDF('export-pdf.php?business_id=<?= (int)$business_id ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>')" style="padding:8px 16px; border:0; border-radius:8px; background:#76553D; color:white; font-weight:600; cursor:pointer; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                            <i data-lucide="file-text" style="width:16px; height:16px;"></i>
                            <?= htmlspecialchars(__('export_pdf'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <!-- Container tabel laporan -->
                <div style="max-height: 500px; overflow-y: auto; overflow-x: auto; border-top: 1px solid #E9E1D7;">
                    <table id="reportTable" style="width:100%; border-collapse:collapse; text-align:left; white-space:nowrap;">
                        <!-- Header tabel -->
                        <thead style="background:#F5EFE8; position: sticky; top: 0; z-index: 1;">
                            <!-- Looping nama-nama kolom header -->
                            <tr>
                                <?php foreach (
                                    [
                                        'product_name',
                                        'stock_in',
                                        'stock_out',
                                        'profit',
                                        'current_stock'
                                    ] as $key
                                ): ?>

                                <!-- Judul kolom header tabel -->
                                <th style=" padding:14px 16px; font-size:13px; color:#76553D; border-bottom:1px solid #E9E1D7;">
                                    <?= htmlspecialchars(__($key), ENT_QUOTES, 'UTF-8') ?>
                                </th>

                                <?php endforeach; ?>
                            </tr>
                        </thead>

                        <tbody>
                        <!-- Cek apakah data laporan hasil query kosong -->
                        <?php if (mysqli_num_rows($reports) === 0): ?>
                            <tr>
                                <!-- Pesan pemberitahuan jika kosong-->
                                <td colspan="5" style="padding:35px; text-align:center; color:#81776D;">
                                    <?= htmlspecialchars(__('no_data'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- Looping untuk menampilkan baris data laporan per produk -->
                            <?php while ($row = mysqli_fetch_assoc($reports)): ?>
                                <tr>
                                    <!-- Nama produk -->
                                    <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5; font-weight:600;">
                                        <?= htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <!-- Total kuantitas barang masuk -->
                                    <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                        <?= number_format((int)$row['stock_in']) ?>
                                    </td>

                                    <!-- Total kuantitas barang keluar -->
                                    <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                        <?= number_format((int)$row['stock_out']) ?>
                                    </td>

                                    <!-- Total estimasi keuntungan -->
                                    <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                        <?= htmlspecialchars(formatMoneyPHP($row['profit'], $business['currency']), ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <!-- Jumlah sisa stok produk saat ini -->
                                    <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                        <?= number_format((int)$row['current_stock']) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        </tbody>
                    </table>

                </div>

            </div>

<!-- Iframe tersembunyi untuk memuat file export-pdf.php tanpa pindah halaman -->
<iframe id="pdfFrame" style="display:none;"></iframe>

<!-- Library Ikon & Notifikasi UI Kustom -->
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/toast.js" defer></script>

<script>
lucide.createIcons();

// Flag jumlah data laporan dari PHP
const hasReportData = <?= $total_products > 0 ? 'true' : 'false' ?>;

// Fungsi untuk mengonversi data tabel laporan ke file CSV
async function exportTableToCSV(defaultFilename) {
    // Validasi keberadaan data sebelum diekspor
    if (!hasReportData) {
        showToast(<?= json_encode(__('no_data_to_export')) ?>, 'error');
        return;
    }

    const table = document.getElementById('reportTable');
    let csv = [];
    
    // Looping setiap baris tabel untuk mengambil isi sel data
    for (let row of table.rows) {
        let cols = Array.from(row.cells).map(cell => `"${cell.innerText.replace(/"/g, '""')}"`);
        csv.push(cols.join(','));
    }

    // Tambahkan karakter UTF-8 BOM (\uFEFF) agar simbol mata uang terbaca rapi di Excel
    const csvData = '\uFEFF' + csv.join('\n');

    // Gunakan File System Access API jika didukung oleh browser
    if ('showSaveFilePicker' in window) {
        try {
            const handle = await window.showSaveFilePicker({
                suggestedName: defaultFilename,
                types: [{
                    description: 'CSV File',
                    accept: { 'text/csv': ['.csv'] },
                }],
            });
            const writable = await handle.createWritable();
            await writable.write(csvData);
            await writable.close();
            showToast(<?= json_encode(__('csv_export_success')) ?>, 'success');
        } catch (err) {
            // Pengguna membatalkan dialog penyimpanan
        }
    } else {
        // Fallback unduhan otomatis untuk browser biasa
        try {
            const csvFile = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
            const downloadLink = document.createElement('a');
            downloadLink.download = defaultFilename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            showToast(<?= json_encode(__('csv_export_success')) ?>, 'success');
        } catch (e) {
            showToast(<?= json_encode(__('csv_export_failed')) ?>, 'error');
        }
    }
}

// Fungsi untuk memuat dan mencetak file PDF
function printPDF(url) {
    if (!hasReportData) {
        showToast(<?= json_encode(__('no_data_to_export')) ?>, 'error');
        return;
    }

    const iframe = document.getElementById('pdfFrame');
    iframe.src = url;
    
    iframe.onload = function() {
        try {
            iframe.contentWindow.focus();

            // Pasang pendeteksi selesai cetak pada window iframe
            iframe.contentWindow.addEventListener('afterprint', function handleAfterPrint() {
                showToast(<?= json_encode(__('pdf_export_success') ?? 'Dokumen PDF berhasil diproses.') ?>, 'success');
                iframe.contentWindow.removeEventListener('afterprint', handleAfterPrint);
            });

            // Jalankan dialog cetak PDF
            iframe.contentWindow.print();
        } catch (e) {
            console.error(e);
            showToast(<?= json_encode(__('pdf_export_failed') ?? 'Gagal memuat dokumen PDF.') ?>, 'error');
        }
    };
}

</script>

</body>
</html>