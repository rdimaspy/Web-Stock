<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Pengaturan bahasa
require_once __DIR__ . '/../includes/language.php';

// Formatter mata uang
require_once __DIR__ . '/../includes/currency.php';

// QUERY AMBIL PRODUK
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, product_name, current_stock, selling_price, category_id
    FROM products
    WHERE business_id = ?
    ORDER BY product_name ASC"
);
mysqli_stmt_bind_param($stmt, "i", $business_id);
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);

// QUERY AMBIL KATEGORI 
$stmtCat = mysqli_prepare(
    $conn,
    "SELECT id, category_name 
    FROM categories 
    WHERE business_id = ? 
    ORDER BY category_name ASC"
);
mysqli_stmt_bind_param($stmtCat, "i", $business_id);
mysqli_stmt_execute($stmtCat);
$categories = mysqli_stmt_get_result($stmtCat);

?>



<!DOCTYPE html>
<!-- Set bahasa -->
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('stock_out_title'), ENT_QUOTES, 'UTF-8') ?> - StockIn</title>
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

                <!-- Filter Kategori -->
                <div style="margin-bottom:22px;">
                    <label for="category_filter" style="display:block; font-weight:bold; margin-bottom:8px;">
                        <?= htmlspecialchars(__('filter_category'), ENT_QUOTES, 'UTF-8') ?>
                    </label>
                    <select id="category_filter" 
                            style="width:100%; box-sizing:border-box; padding:13px; border:1px solid #E9E1D7; border-radius:9px; background:white; color:#49382D; font-size:15px;">
                        <option value="ALL"><?= htmlspecialchars(__('all_categories'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="UNCATEGORIZED"><?= htmlspecialchars(__('uncategorized'), ENT_QUOTES, 'UTF-8') ?></option>
                        
                        <!-- Looping opsi kategori dari database -->
                        <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= (int)$category['id'] ?>">
                                <?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Input pilihan produk -->
                <div style="margin-bottom:22px;">
                    <label for="product_id" style="display:block; margin-bottom:8px; font-weight:600;">
                        <?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?>
                    </label>

                    <select id="product_id" name="product_id" required
                            style="width:100%; box-sizing:border-box; padding:13px; border:1px solid #E9E1D7; border-radius:9px; background:white; color:#49382D; font-size: 15px;">

                        <option value="">
                            <?= htmlspecialchars(__('select_product_placeholder'), ENT_QUOTES, 'UTF-8') ?>
                        </option>

                        <!-- Looping produk-->
                        <?php while ($product = mysqli_fetch_assoc($products)): ?>
                            <option value="<?= (int) $product['id'] ?>"
                                    data-stock="<?= (int) $product['current_stock'] ?>"
                                    data-price="<?= (float) $product['selling_price'] ?>"
                                    data-category="<?= $product['category_id'] ?? 'UNCATEGORIZED' ?>">
                                <?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <!-- Teks informasi stok dan harga jual -->
                    <div style="margin-top:8px; font-size:13px; color:#81776D; line-height:1.5;">
                        <div><?= htmlspecialchars(__('available_stock_label'), ENT_QUOTES, 'UTF-8') ?> 
                            <span id="stockDisplay">-</span>
                        </div>
                        <div><?= htmlspecialchars(__('selling_price_label'), ENT_QUOTES, 'UTF-8') ?> 
                            <span id="priceDisplay">-</span>
                        </div>
                    </div>
                </div>

                <!-- Jumlah keluar -->
                <div style="margin-top:25px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">
                        <?= htmlspecialchars(__('quantity_out'), ENT_QUOTES, 'UTF-8') ?>
                    </label>

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

                    <input type="text" id="notes" name="notes" maxlength="40" placeholder="<?= htmlspecialchars(__('notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                        style="width:100%; padding:10px 12px; box-sizing:border-box; border:1px solid #E9E1D7; border-radius:8px; font-size:14px; outline-color:#B99A7D;">
                </div>

                <!-- Tombol Save -->
                <div style="padding-top:20px; display:flex; justify-content:flex-end;">
                    <button type="submit" id="saveStockOut" style="display:flex; align-items:center; gap:8px; padding:12px 19px; border:0; border-radius:9px; background:#76553D; color:white; font-weight:600; cursor:pointer;">
                        <i data-lucide="save" style="width:18px;height:18px;"></i>
                        <?= htmlspecialchars(__('save_stock_out'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>

            </div>

        </section>
    </main>
</div>


<!-- Ikon & UI Helpers -->
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/toast.js" defer></script>

<script>
    lucide.createIcons();
</script>

<script>
// Merender ikon Lucide pada antarmuka halaman
lucide.createIcons();
</script>

<script>
// Mengambil data mata uang bisnis aktif dari PHP
const businessCurrency = <?= json_encode($businessCurrency ?? $business['currency'] ?? 'USD') ?>;

// Inisialisasi Elemen DOM Utama
const productSelect = document.getElementById('product_id');
const stockDisplay = document.getElementById('stockDisplay');
const priceDisplay = document.getElementById('priceDisplay');
const quantityInput = document.getElementById('quantity');
const totalAmount = document.getElementById('totalAmount');
const categoryFilter = document.getElementById('category_filter');

// Menyimpan nilai harga satuan produk aktif (default: 0)
let currentPrice = 0;

// Menyimpan Seluruh Opsi Produk Asli Beserta Datasets ke Array
let allProductOptions = [];
if (productSelect) {
    allProductOptions = Array.from(productSelect.options).map(opt => ({
        value: opt.value,
        text: opt.textContent,
        stock: opt.getAttribute('data-stock') || opt.dataset.stock || 0,
        price: opt.getAttribute('data-price') || opt.dataset.price || 0,
        category: opt.getAttribute('data-category') || opt.dataset.category || 'UNCATEGORIZED'
    }));
}

// Fungsi Format Mata Uang Dinamis Sesuai Jenis Currency Bisnis Aktif
function formatCurrency(value) {
    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: businessCurrency,
            minimumFractionDigits: 0
        }).format(value);
    } catch (e) {
        // Fallback format jika terjadi kendala kustomisasi di browser
        return businessCurrency + ' ' + Number(value).toLocaleString('en-US');
    }
}

// Menghitung Total Transaksi Penjualan Barang Keluar
function calculateTotal() {
    const quantity = parseInt(quantityInput.value) || 0;
    const total = quantity * currentPrice;

    // Tampilkan total berformat mata uang jika jumlah lebih dari 0
    if (total > 0) {
        totalAmount.value = formatCurrency(total);
    } else {
        totalAmount.value = '-';
    }
}

// Fungsi Memperbarui Tampilan Stok & Harga Produk Terpilih
function updateProductDetails() {
    if (!productSelect) return;

    // Ambil opsi produk yang sedang dipilih oleh pengguna
    const selectedOption = productSelect.options[productSelect.selectedIndex];

    // Reset bidang tampilan jika pengguna memilih opsi placeholder/kosong
    if (!productSelect.value || !selectedOption) {
        if (stockDisplay) stockDisplay.textContent = '-';
        if (priceDisplay) priceDisplay.textContent = '-';
        totalAmount.value = '-';
        currentPrice = 0;
        return;
    }

    // Ambil atribut stok dan harga satuan dari dataset opsi produk terpilih
    const stock = parseInt(selectedOption.getAttribute('data-stock') || selectedOption.dataset.stock || 0);
    const price = parseFloat(selectedOption.getAttribute('data-price') || selectedOption.dataset.price || 0);

    // Perbarui harga satuan aktif
    currentPrice = price;

    // Tampilkan stok tersedia dan harga jual berformat mata uang
    if (stockDisplay) stockDisplay.textContent = new Intl.NumberFormat().format(stock);
    if (priceDisplay) priceDisplay.textContent = formatCurrency(price);

    // Hitung ulang akumulasi total transaksi
    calculateTotal();
}

// Menjalankan fungsi updateProductDetails saat pilihan produk diubah manual oleh pengguna
if (productSelect) {
    productSelect.addEventListener('change', updateProductDetails);
}

// Menghitung ulang total transaksi secara langsung saat input jumlah barang keluar diisi
if (quantityInput) {
    quantityInput.addEventListener('input', calculateTotal);
}

// Logika Penyaringan (Filter) Produk Berdasarkan Kategori
if (categoryFilter && productSelect) {
    categoryFilter.addEventListener('change', function () {
        const selectedCategory = this.value;

        // Kosongkan seluruh opsi dropdown produk saat ini
        productSelect.innerHTML = '';

        // Looping dan filter ulang opsi produk berdasarkan kategori terpilih
        allProductOptions.forEach(opt => {
            // Opsi placeholder ("Pilih Produk") selalu dimasukkan kembali
            if (!opt.value) {
                const placeholderOpt = new Option(opt.text, opt.value);
                productSelect.add(placeholderOpt);
                return;
            }

            const productCategory = String(opt.category);
            let isMatch = false;

            // Memeriksa kesesuaian kategori yang dipilih
            if (selectedCategory === 'ALL') {
                isMatch = true;
            } else if (selectedCategory === 'UNCATEGORIZED' && (!productCategory || productCategory === 'null' || productCategory === 'UNCATEGORIZED')) {
                isMatch = true;
            } else if (productCategory === selectedCategory) {
                isMatch = true;
            }

            // Jika cocok, buat elemen option baru dan pasang kembali atribut stok, harga, dan kategori
            if (isMatch) {
                const newOpt = new Option(opt.text, opt.value);
                newOpt.setAttribute('data-stock', opt.stock);
                newOpt.setAttribute('data-price', opt.price);
                newOpt.setAttribute('data-category', opt.category);
                productSelect.add(newOpt);
            }
        });

        // Reset detail stok, harga, dan total setelah penyaringan kategori
        updateProductDetails();
    });
}

// Menangani Simpan Transaksi Stok Keluar Secara Asinkron 
const saveButton = document.getElementById('saveStockOut');

if (saveButton) {
    saveButton.addEventListener('click', async function () {
        const productId = productSelect.value;
        const quantity = quantityInput.value;
        const notes = document.getElementById('notes').value;

        // Validasi pemilihan produk
        if (!productId) {
            showToast(<?= json_encode(__('select_product_first')) ?>, 'error');
            return;
        }

        // Validasi input jumlah barang keluar
        if (!quantity || quantity < 1) {
            showToast(<?= json_encode(__('enter_quantity_out')) ?>, 'error');
            return;
        }

        // Validasi kecukupan stok barang yang tersedia
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const stockAvailable = parseInt(selectedOption.getAttribute('data-stock') || selectedOption.dataset.stock || 0);

        if (parseInt(quantity) > stockAvailable) {
            showToast(<?= json_encode(__('insufficient_stock')) ?> + ' ' + stockAvailable, 'error');
            return;
        }

        // Menyusun payload FormData untuk dikirimkan ke endpoint API
        const formData = new FormData();
        formData.append('business_id', <?= (int)$business_id ?>);
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        formData.append('notes', notes);

        try {
            // Mengirim data transaksi ke API pengurang stok keluar
            const response = await fetch('../api/create-stock-out.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            // Menampilkan notifikasi umpan balik via Toast
            if (result.success) {
                showToast(result.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error(error);
            showToast(<?= json_encode(__('system_error')) ?>, 'error');
        }
    });
}

</script>

</body>
</html>