<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Pengaturan bahasa
require_once __DIR__ . '/../includes/language.php';

// Formatter mata uang
require_once __DIR__ . '/../includes/currency.php';

// Ambil produk aktif untuk pilihan Stock Out
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, product_name, current_stock, selling_price
    FROM products
    WHERE business_id = ?
    ORDER BY product_name ASC"
);

// Mengikat parameter ID bisnis ke statement query
mysqli_stmt_bind_param(
    $stmt,
    "i",
    $business_id
);

// Mengeksekusi query yang telah disiapkan dan Mengambil hasil query produk
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);

?>



<!DOCTYPE html>
<!-- Set bahasa -->
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('stock_out_title'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>
</head>

<!-- Styling utama layout dan warna latar halaman -->
<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'stock-out.php';?>

<!-- Layout utama halaman Produk -->
<div style="display:flex; min-height:100vh;">

    <!-- SIDEBAR -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- KONTEN UTAMA -->
    <main style="margin-left:230px; flex:1; min-width:0;">

        <!-- HEADER -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- HALAMAN STOK KELUAR -->
        <section style="padding:32px;">
            <div style="margin-bottom:25px;">
                <!-- Judul -->
                <h1 style="margin:0; font-size:29px;">
                    <?= htmlspecialchars(__('stock_out_title'), ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <!-- Deskripsi -->
                <p style="color:#81776D; margin-top:10px;">
                    <?= htmlspecialchars(__('stock_out_description'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- FORM STOK KELUAR -->
            <!-- Container form utama pencatatan stok keluar -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:28px; max-width:900px;">

                <!-- Header form -->
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:25px;">
                    <div style="width:55px; height:55px; border-radius:12px; background:#F5EFE8; display:flex; align-items:center; justify-content:center; color:#76553D;">
                        <i data-lucide="package-minus" style="width:28px;"></i>
                    </div>

                    <div>
                        <!-- Judul form -->
                        <h2 style="margin:0; font-size:22px;">
                            <?= htmlspecialchars(__('record_stock_out'), ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <!-- Deskripsi form -->
                        <p style="margin:5px 0 0; color:#81776D;">
                            <?= htmlspecialchars(__('record_stock_out_description'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>

                <!-- Pilih produk -->
                <label style="display:block; font-weight:bold; margin-bottom:8px;">
                    <?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?>
                </label>

                <!-- Elemen dropdown pilihan produk -->
                <select id="product_id" name="product_id" required
                        style="width:100%; box-sizing:border-box; padding:13px; border:1px solid #E9E1D7; border-radius:9px; background:white; color:#49382D;">

                    <!-- Opsi default untuk memilih produk -->
                    <option value="">
                        <?= $lang === 'en' ? 'Select a product' : 'Pilih produk' ?>
                    </option>

                    <!-- Looping opsi daftar produk beserta atribut data stok dan harga -->
                    <?php while ($product = mysqli_fetch_assoc($products)): ?>
                        <option value="<?= (int) $product['id'] ?>"
                                data-stock="<?= (int) $product['current_stock'] ?>"
                                data-price="<?= (float) $product['selling_price'] ?>">
                            <?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Teks informasi stok dan harga jual di bawah pilihan produk -->
                <div style="margin-top:8px; font-size:13px; color:#81776D; line-height:1.5;">
                    <div><?= $lang === 'en' ? 'Available stock:' : 'Stok tersedia:' ?> <span id="stockDisplay">-</span></div>
                    <div><?= $lang === 'en' ? 'Selling price:' : 'Harga Jual:' ?> <span id="priceDisplay">-</span></div>
                </div>

                <!-- Jumlah keluar -->
                <div style="margin-top:25px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">
                        <?= htmlspecialchars(__('quantity_out'), ENT_QUOTES, 'UTF-8') ?>
                    </label>

                    <!-- Jumlah barang keluar -->
                    <input type="number" id="quantity" name="quantity" min="1" placeholder="<?= htmlspecialchars(__('quantity_out_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                        style="width:100%; padding:14px; border:1px solid #E9E1D7; border-radius:10px; font-size:15px;">
                </div>

                <!-- Total (harga) barang keluar -->
                <div style="margin-top:22px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">
                        <?= htmlspecialchars(__('total_amount'), ENT_QUOTES, 'UTF-8') ?>
                    </label>

                    <input type="text" id="totalAmount" readonly value="-" style="width:100%; box-sizing:border-box; padding:13px; border:1px solid #E9E1D7; border-radius:9px; background:#FAF7F2; color:#76553D;">
                </div>

                <!-- Catatan -->
                <div style="margin-top:25px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">
                        <?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?>
                        <span style="font-weight:normal; color:#81776D;">
                            (<?= htmlspecialchars(__('optional'), ENT_QUOTES, 'UTF-8') ?>)
                        </span>
                    </label>

                    <textarea id="notes" name="notes" rows="4" placeholder="<?= htmlspecialchars(__('notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                        style="width:100%; padding:14px; border:1px solid #E9E1D7; border-radius:10px; resize:none; font-size:15px;">
                    </textarea>
                </div>

                <!-- Tombol Save-->
                <div style="padding-top:20px; border-top:1px solid #E9E1D7; display:flex; justify-content:flex-end;">
                    <button type="submit" id="saveStockOut" style="display:flex; align-items:center; gap:8px; padding:12px 19px; border:0; border-radius:9px; background:#76553D; color:white; font-weight:600; cursor:pointer;">
                        <i data-lucide="save" style="width:18px;height:18px;"></i>
                        <?= htmlspecialchars(__('save_stock_out'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>

            </div>

        </section>
    </main>
</div>


<!-- Ikon -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>

<script>
// Mengambil data mata uang bisnis aktif dari PHP
const businessCurrency = <?= json_encode($businessCurrency ?? $business['currency'] ?? 'USD') ?>;

/* Stock Out Calculation */
// Mengambil elemen dropdown produk, stok tersedia, harga jual, jumlah barang keluar, dan total harga
const productSelect = document.getElementById('product_id');
const stockDisplay = document.getElementById('stockDisplay');
const priceDisplay = document.getElementById('priceDisplay');
const quantityInput = document.getElementById('quantity');
const totalAmount = document.getElementById('totalAmount');

// Menyimpan nilai harga satuan produk aktif (default: 0)
let currentPrice = 0;

// Format mata uang dinamis sesuai jenis currency bisnis aktif
function formatCurrency(value) {
    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: businessCurrency,
            minimumFractionDigits: 0
        }).format(value);
    } catch (e) {
        // Fallback jika format currency mengalami kendala pada browser
        return businessCurrency + ' ' + Number(value).toLocaleString('en-US');
    }
}

// Rumus menghitung total transaksi
function calculateTotal() {
    const quantity = parseInt(quantityInput.value) || 0;
    const total = quantity * currentPrice;

    // Menampilkan hasil format mata uang jika total lebih dari 0
    if (total > 0) {
        totalAmount.value = formatCurrency(total);
    } else {
        totalAmount.value = '-';
    }
}

// Menyesuaikan informasi harga dan stok saat produk pada dropdown dipilih
productSelect.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];

    // Reset bidang tampilan jika tidak ada produk yang dipilih
    if (!selectedOption.value) {
        if (stockDisplay) stockDisplay.textContent = '-';
        if (priceDisplay) priceDisplay.textContent = '-';
        totalAmount.value = '-';
        currentPrice = 0;
        return;
    }

    // Mengambil nilai stok dan harga dari atribut dataset opsi produk terpilih
    const stock = parseInt(selectedOption.dataset.stock) || 0;
    const price = parseFloat(selectedOption.dataset.price) || 0;

    // Memperbarui nilai harga satuan aktif
    currentPrice = price;

    // Menampilkan stok tersedia dan harga jual berformat mata uang bisnis
    if (stockDisplay) stockDisplay.textContent = new Intl.NumberFormat().format(stock);
    if (priceDisplay) priceDisplay.textContent = formatCurrency(price);

    // Menghitung ulang total harga transaksi
    calculateTotal();
});

// Menghitung ulang total harga secara real-time saat jumlah keluar diubah
quantityInput.addEventListener('input', function () {
    calculateTotal();
});

/* Menyimpan Transaksi Stock Out */
const saveButton = document.getElementById('saveStockOut');

saveButton.addEventListener('click', function () {
    const productId = productSelect.value;
    const quantity = quantityInput.value;
    const notes = document.getElementById('notes').value;

    // Validasi input pemilihan produk
    if (!productId) {
        alert('Pilih produk terlebih dahulu.');
        return;
    }

    // Validasi input jumlah barang keluar
    if (!quantity || quantity < 1) {
        alert('Masukkan jumlah barang keluar.');
        return;
    }

    // Ambil stok tersedia langsung dari dataset produk yang terpilih
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const stockAvailable = parseInt(selectedOption.dataset.stock) || 0;

    // Validasi ketersediaan stok produk
    if (parseInt(quantity) > stockAvailable) {
        alert('Stok tidak mencukupi. Stok tersedia: ' + stockAvailable);
        return;
    }

    // Menyusun payload FormData untuk dikirimkan ke server
    const formData = new FormData();
    formData.append('business_id', <?= (int)$business_id ?>);
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('notes', notes);

    // Mengirimkan data transaksi stok keluar melalui API
    fetch('../api/create-stock-out.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            window.location.reload();
        } else {
            alert(result.message);
        }
    })
    .catch(error => {
        console.error(error);
        alert('Terjadi kesalahan sistem.');
    });
});
</script>

</body>
</html>