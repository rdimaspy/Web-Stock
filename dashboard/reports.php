<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/currency.php';

$currentPage = 'reports.php';

// Validasi business aktif
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);

if (!$business_id || $business_id < 1) {
    header("Location: ../businesses.php");
    exit;
}

// Pastikan bisnis milik user
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, business_name, currency
    FROM businesses
    WHERE id = ?
    AND user_id = ?
    LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $business_id,
    $_SESSION['user_id']
);

mysqli_stmt_execute($stmt);

$business = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$business) {
    http_response_code(404);
    exit("Bisnis tidak ditemukan atau tidak memiliki akses.");
}

// Filter tanggal laporan
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = date('Y-m-01');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = date('Y-m-d');
}

// Sorting laporan
$allowedSort = [
    'product_name',
    'stock_in',
    'stock_out',
    'profit',
    'current_stock'
];

$sort = $_GET['sort'] ?? 'profit';

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'profit';
}

$order = strtoupper($_GET['order'] ?? 'DESC');

if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

// query utama laporan
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


$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssi",
    $date_from,
    $date_to,
    $business_id
);

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

mysqli_stmt_bind_param($stmt_modal, "i", $business_id);
mysqli_stmt_execute($stmt_modal);
$res_modal = mysqli_stmt_get_result($stmt_modal);
$row_modal = mysqli_fetch_assoc($res_modal);

$total_modal_value = (float)($row_modal['total_modal'] ?? 0);

// Kembalikan pointer agar tabel tetap bisa membaca data
mysqli_data_seek($reports, 0);

?>


<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars(__('reports'), ENT_QUOTES, 'UTF-8') ?> - Stock
    </title>
</head>

<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'reports.php';?>

<div style="display:flex; min-height:100vh;">

    <!-- Sidebar -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- KONTEN UTAMA -->
    <main style="margin-left:230px; flex:1; min-width:0;">

        <!-- HEADER -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- AREA DASHBOARD -->
        <section style="padding:32px;">

            <!-- Judul halaman -->
            <div style="margin-bottom:25px;">
                <h1 style="margin:0; font-size:29px;">
                    <?= htmlspecialchars(__('reports'), ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <p style="color:#81776D; margin-top:10px; line-height:1.6;">
                    <?= htmlspecialchars(__('reports_description'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- Filter laporan -->
            <div style="background:white;border:1px solid #E9E1D7; border-radius:12px; padding:22px; margin-bottom:25px;">
                <form method="GET">
                    <input type="hidden" name="business_id" value="<?= (int)$business_id ?>">
                    <div style="display:flex; gap:15px; align-items:end; flex-wrap:wrap;">

                        <div>
                            <label style="display:block; margin-bottom:7px; font-weight:600;">
                                <?= htmlspecialchars(__('date_from'), ENT_QUOTES, 'UTF-8') ?>
                            </label>

                            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                        </div>

                        <div>
                            <label style="display:block; margin-bottom:7px; font-weight:600;">
                                <?= htmlspecialchars(__('date_to'), ENT_QUOTES, 'UTF-8') ?>
                            </label>

                            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                        </div>

                        <div>
                            <label style="display:block; margin-bottom:7px; font-weight:600;">
                                <?= htmlspecialchars(__('sort_by'), ENT_QUOTES, 'UTF-8') ?>
                            </label>

                            <select name="sort"
                                style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">

                                <option value="profit" <?= $sort === 'profit' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('profit'), ENT_QUOTES, 'UTF-8') ?>
                                </option>

                                <option value="stock_in" <?= $sort === 'stock_in' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?>
                                </option>

                                <option value="stock_out" <?= $sort === 'stock_out' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('stock_out'), ENT_QUOTES, 'UTF-8') ?>
                                </option>

                                <option value="current_stock" <?= $sort === 'current_stock' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('current_stock'), ENT_QUOTES, 'UTF-8') ?>
                                </option>

                                <option value="product_name" <?= $sort === 'product_name' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            </select>
                        </div>


                        <div>
                            <label style="display:block; margin-bottom:7px; font-weight:600;">
                                <?= htmlspecialchars(__('order'), ENT_QUOTES, 'UTF-8') ?>
                            </label>

                            <select name="order"
                                style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">

                                <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>
                                    Descending
                                </option>

                                <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>
                                    Ascending
                                </option>
                            </select>
                        </div>

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
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('total_products'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <h2 style="margin:10px 0 0;">
                        <?= number_format($total_products) ?>
                    </h2>
                </div>

                <!-- Total Stok -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('total_stock'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <h2 style="margin:10px 0 0;">
                        <?= number_format($total_stock) ?>
                    </h2>
                </div>

                <!-- Stock In -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <h2 style="margin:10px 0 0;">
                        <?= number_format($total_stock_in) ?>
                    </h2>
                </div>

                <!-- Stock Out -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('stock_out'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <h2 style="margin:10px 0 0;">
                        <?= number_format($total_stock_out) ?>
                    </h2>
                </div>

                <!-- Total Harga Modal -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px;">
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('total_capital') ?? 'Total Harga Modal', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <h2 style="margin:10px 0 0; color:#76553D;">
                        <?= htmlspecialchars(formatMoneyPHP($total_modal_value, $business['currency']), ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                </div>

                <!-- Profit -->
                <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:20px; grid-column:span 2;">
                    <p style="margin:0; color:#81776D; font-size:13px;">
                        <?= htmlspecialchars(__('profit'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <h2 style="margin:10px 0 0;">
                        <?= htmlspecialchars(formatMoneyPHP($total_profit, $business['currency']), ENT_QUOTES, 'UTF-8'
                        ) ?>
                    </h2>
                </div>

            </div>

            <!-- Tabel laporan produk -->
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
                <div style="max-height: 500px; overflow-y: auto; overflow-x: auto; border-top: 1px solid #E9E1D7;">
                    <table id="reportTable" style="width:100%; border-collapse:collapse; text-align:left; white-space:nowrap;">

                        <thead style="background:#F5EFE8; position: sticky; top: 0; z-index: 1;">
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

                                <th style=" padding:14px 16px; font-size:13px; color:#76553D; border-bottom:1px solid #E9E1D7;">
                                    <?= htmlspecialchars(__($key), ENT_QUOTES, 'UTF-8') ?>
                                </th>

                                <?php endforeach; ?>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (mysqli_num_rows($reports) === 0): ?>
                            <tr>
                                <td colspan="5" style="padding:35px; text-align:center; color:#81776D;">
                                    <?= htmlspecialchars(__('no_data'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php while ($row = mysqli_fetch_assoc($reports)): ?>
                                <tr>
                                    <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5; font-weight:600;">
                                        <?= htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                        <?= number_format((int)$row['stock_in']) ?>
                                    </td>

                                    <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                        <?= number_format((int)$row['stock_out']) ?>
                                    </td>

                                    <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                        <?= htmlspecialchars(formatMoneyPHP($row['profit'], $business['currency']), ENT_QUOTES, 'UTF-8') ?>
                                    </td>

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

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>


    <script>
        // mengonversi tabel laporan ke bentuk file CSV
        async function exportTableToCSV(defaultFilename) {
            const table = document.getElementById('reportTable');
            let csv = [];
            
            // Ambil data dari baris tabel
            for (let row of table.rows) {
                let cols = Array.from(row.cells).map(cell => `"${cell.innerText.replace(/"/g, '""')}"`);
                csv.push(cols.join(','));
            }

            const csvData = '\uFEFF' + csv.join('\n');

            // Cek apakah browser mendukung fitur pemilih lokasi file (Save File Picker)
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
                } catch (err) {
                    // Abaikan jika pengguna membatalkan dialog Save As
                }
            } else {
                // Fallback otomatis untuk browser lama yang tidak mendukung
                const csvFile = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
                const downloadLink = document.createElement('a');
                downloadLink.download = defaultFilename;
                downloadLink.href = window.URL.createObjectURL(csvFile);
                downloadLink.style.display = 'none';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
        }

        // Fungsi JS untuk mengisi src iframe tanpa reload/pindah page
        function printPDF(url) {
            const iframe = document.getElementById('pdfFrame');
            iframe.src = url;
            iframe.onload = function() {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            };
        }

    </script>

</body>
</html>