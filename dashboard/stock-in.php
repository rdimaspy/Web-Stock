<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/currency.php';

// Validasi bisnis aktif
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);

if (!$business_id || $business_id < 1) {
    header("Location: ../businesses.php");
    exit;
}

// Pastikan bisnis milik pengguna yang sedang login
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, business_name, currency
    FROM businesses
    WHERE id = ? AND user_id = ?
    LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "ii", $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

$business = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$business) {
    http_response_code(404);
    exit("Bisnis tidak ditemukan atau kamu tidak memiliki akses.");
}

// Ambil produk dari bisnis aktif
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, product_name, product_code, current_stock
    FROM products
    WHERE business_id = ?
    ORDER BY product_name ASC"
);

mysqli_stmt_bind_param($stmt, "i", $business_id);
mysqli_stmt_execute($stmt);

$products = mysqli_stmt_get_result($stmt);
$productCount = mysqli_num_rows($products);
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>
</head>

<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'stock-in.php';?>

<div style="display:flex; min-height:100vh;">

    <!-- SIDEBAR -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- KONTEN UTAMA -->
    <main style="margin-left:230px; flex:1; min-width:0;">

        <!-- HEADER -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- HALAMAN STOCK IN -->
        <section style="padding:32px;">

            <div style="margin-bottom:25px;">
                <h1 style="margin:0; font-size:29px;">
                    <?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <p style="color:#81776D; margin-top:10px; line-height:1.6;">
                    <?= $lang === 'en'
                        ? 'Record products received into your business inventory.'
                        : 'Catat barang masuk untuk menambah persediaan bisnismu.' ?>
                </p>
            </div>

            <!-- Form Stok Maasuk -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:28px; max-width:900px;">

                <div style="display:flex; align-items:center; gap:12px; margin-bottom:25px;">
                    <div style="width:55px; height:55px; border-radius:12px; background:#F5EFE8; display:flex; align-items:center; justify-content:center; color:#76553D;">
                        <i data-lucide="package-plus" style="width:28px;"></i>
                    </div>

                    <div>
                        <h2 style="font-size:22px; margin:0;">
                            <?= $lang === 'en' ? 'New Stock In' : 'Catat Stok Masuk' ?>
                        </h2>

                        <p style="font-size:13px; color:#81776D; margin:5px 0 0;">
                            <?= $lang === 'en'
                                ? 'Select a product and enter the quantity received.'
                                : 'Pilih produk dan masukkan jumlah barang yang diterima.' ?>
                        </p>
                    </div>
                </div>

                <?php if ($productCount === 0): ?>

                    <div style="padding:22px; text-align:center; background:#FAF7F2; border-radius:10px; color:#81776D; line-height:1.7;">
                        <?= $lang === 'en'
                            ? 'There are no products yet. Add a product first before recording stock in.'
                            : 'Belum ada produk. Tambahkan produk terlebih dahulu sebelum mencatat stok masuk.' ?>

                        <div style="margin-top:15px;">
                            <a href="products.php?business_id=<?= (int) $business_id ?>" style="color:#76553D; font-weight:600; text-decoration:none;">
                                <?= $lang === 'en' ? 'Go to Products' : 'Buka Produk' ?>
                            </a>
                        </div>
                    </div>

                <?php else: ?>

                    <form id="stockInForm" method="POST">

                        <input type="hidden" name="business_id" value="<?= (int) $business_id ?>">

                        <div style="margin-bottom:22px;">
                            <label for="product_id" style="display:block; margin-bottom:8px; font-weight:600;">
                                <?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?>
                            </label>

                            <select id="product_id" name="product_id" required
                                    style="width:100%; box-sizing:border-box; padding:13px; border:1px solid #E9E1D7; border-radius:9px; background:white; color:#49382D;">

                                <option value="">
                                    <?= $lang === 'en' ? 'Select a product' : 'Pilih produk' ?>
                                </option>

                                <?php while ($product = mysqli_fetch_assoc($products)): ?>
                                    <option value="<?= (int) $product['id'] ?>"
                                            data-stock="<?= (int) $product['current_stock'] ?>">
                                        <?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                        — <?= $lang === 'en' ? 'Current stock:' : 'Stok saat ini:' ?>
                                        <?= number_format((int) $product['current_stock']) ?>
                                    </option>
                                <?php endwhile; ?>

                            </select>

                            <p id="currentStockInfo" style="font-size:13px; color:#81776D; margin:8px 0 0;">
                                <?= $lang === 'en'
                                    ? 'Current stock will appear after you select a product.'
                                    : 'Stok saat ini akan ditampilkan setelah kamu memilih produk.' ?>
                            </p>
                        </div>

                        <div style="margin-bottom:22px;">
                            <label for="quantity" style="display:block; margin-bottom:8px; font-weight:600;">
                                <?= $lang === 'en' ? 'Quantity Received' : 'Jumlah Barang Masuk' ?>
                            </label>

                            <input type="number" id="quantity" name="quantity"
                                min="1" step="1" required
                                placeholder="<?= $lang === 'en' ? 'Enter quantity' : 'Masukkan jumlah barang' ?>"
                                style="width:100%; box-sizing:border-box; padding:13px; border:1px solid #E9E1D7; border-radius:9px; outline-color:#B99A7D;">
                        </div>

                        <div style="margin-bottom:25px;">
                            <label for="notes" style="display:block; margin-bottom:8px; font-weight:600;">
                                <?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?>
                                <span style="font-size:12px; color:#948578; font-weight:normal;">
                                    (<?= htmlspecialchars(__('optional'), ENT_QUOTES, 'UTF-8') ?>)
                                </span>
                            </label>

                            <textarea id="notes" name="notes" rows="4"
                                    placeholder="<?= $lang === 'en'
                                        ? 'For example: supplier delivery or restock notes'
                                        : 'Contoh: pengiriman pemasok atau keterangan pengisian stok' ?>"
                                    style="width:100%; box-sizing:border-box; padding:13px; border:1px solid #E9E1D7; border-radius:9px; resize:vertical; font-family:inherit;"></textarea>
                        </div>
                        
                        <!-- Tombol Save -->
                        <div style="padding-top:20px; border-top:1px solid #E9E1D7; display:flex; justify-content:flex-end;">
                            <button type="submit" id="stockInSubmitButton" style="display:flex; align-items:center; gap:8px; padding:12px 19px; border:0; border-radius:9px; background:#76553D; color:white; font-weight:600; cursor:pointer;">
                                <i data-lucide="save" style="width:18px;height:18px;"></i>
                                <?= $lang === 'en' ? 'Save Stock In' : 'Simpan Stok Masuk' ?>
                            </button>
                        </div>

                    </form>

                <?php endif; ?>

            </div>
        </section>
    </main>
</div>

<script src="https://unpkg.com/lucide@latest">
</script>

<script>
    lucide.createIcons();

    const productSelect = document.getElementById('product_id');
    const stockInfo = document.getElementById('currentStockInfo');

    if (productSelect && stockInfo) {
        productSelect.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];

            if (!this.value) {
                stockInfo.textContent = <?= json_encode(
                    $lang === 'en'
                        ? 'Current stock will appear after you select a product.'
                        : 'Stok saat ini akan ditampilkan setelah kamu memilih produk.'
                ) ?>;
                return;
            }

            const currentStock = Number(option.dataset.stock || 0);

            stockInfo.textContent = <?= json_encode(
                $lang === 'en' ? 'Current stock: ' : 'Stok saat ini: '
            ) ?> + currentStock.toLocaleString();
        });
    }

// Hubungkan formulir ke API
const stockInForm = document.getElementById('stockInForm');
const stockInSubmitButton = document.getElementById('stockInSubmitButton');

if (stockInForm && stockInSubmitButton) {
    stockInForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!stockInForm.reportValidity()) {
            return;
        }

        const originalButtonHTML = stockInSubmitButton.innerHTML;

        stockInSubmitButton.disabled = true;
        stockInSubmitButton.textContent = <?= json_encode(
            $lang === 'en' ? 'Saving...' : 'Menyimpan...'
        ) ?>;
        stockInSubmitButton.style.cursor = 'wait';
        stockInSubmitButton.style.opacity = '0.7';

        try {
            const response = await fetch('../api/create-stock-in.php', {
                method: 'POST',
                body: new FormData(stockInForm)
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                alert(result.message || <?= json_encode(
                    $lang === 'en'
                        ? 'Failed to save stock in.'
                        : 'Gagal menyimpan stok masuk.'
                ) ?>);
                return;
            }

            alert(result.message);

            // Muat ulang agar stok terbaru ditampilkan.
            window.location.reload();

        } catch (error) {
            console.error(error);

            alert(<?= json_encode(
                $lang === 'en'
                    ? 'An error occurred while saving stock in.'
                    : 'Terjadi kesalahan saat menyimpan stok masuk.'
            ) ?>);

        } finally {
            stockInSubmitButton.disabled = false;
            stockInSubmitButton.innerHTML = originalButtonHTML;
            stockInSubmitButton.style.cursor = 'pointer';
            stockInSubmitButton.style.opacity = '1';

            lucide.createIcons();
        }
    });
}

</script>

</body>
</html>