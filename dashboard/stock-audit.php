<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Pengaturan bahasa
require_once __DIR__ . '/../includes/language.php';

// Formatter mata uang
require_once __DIR__ . '/../includes/currency.php';

// Ambil produk untuk audit stok
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, product_name, current_stock, category_id
    FROM products
    WHERE business_id = ?
    ORDER BY product_name ASC"
);

// Mengikat parameter ID bisnis ke statement query
mysqli_stmt_bind_param($stmt, "i", $business_id);

// Mengeksekusi query yang telah disiapkan dan Mengambil hasil query produk
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);

// Ambil daftar kategori produk untuk filte
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
    <title><?= htmlspecialchars(__('stock_audit_title'), ENT_QUOTES, 'UTF-8') ?> - StockIn</title>
</head>

<!-- Styling utama layout dan warna latar halaman -->
<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'stock-audit.php';?>

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
                    <?= htmlspecialchars(__('stock_audit_title'), ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <!-- Deskripsi -->
                <p style="color:#81776D; margin-top:10px;">
                    <?= htmlspecialchars(__('stock_audit_description'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- FORM STOK AUDIT -->
            <!-- Container form utama pencatatan stok audit -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:28px; max-width:900px;">

                <!-- Header form -->
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:25px;">
                    <div style="width:55px; height:55px; border-radius:12px; background:#F5EFE8; display:flex; align-items:center; justify-content:center; color:#76553D;">
                        <i data-lucide="clipboard-check" style="width:28px;"></i>
                    </div>

                    <div>
                        <!-- Judul form -->
                        <h2 style="margin:0; font-size:22px;">
                            <?= htmlspecialchars(__('stock_audit_form_title'), ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <!-- Deskripsi form -->
                        <p style="margin:5px 0 0; color:#81776D;">
                            <?= htmlspecialchars(__('stock_audit_form_description'), ENT_QUOTES, 'UTF-8') ?>
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

                <!-- Pilih produk -->
                <label style="display:block; font-weight:bold; margin-bottom:8px;">
                    <?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?>
                </label>

                <!-- Elemen dropdown pilihan produk -->
                <select id="product_id" onchange="updateAuditStock()" style="width:100%; box-sizing:border-box; padding: 13px; border:1px solid #E9E1D7; border-radius:9px; font-size:15px; background:white;">
                    <option value="">
                        <?= htmlspecialchars(__('select_product'), ENT_QUOTES, 'UTF-8') ?>
                    </option>

                    <!-- Looping untuk menampilkan daftar opsi produk beserta data atribut stok dan harga -->
                    <?php while ($product = mysqli_fetch_assoc($products)): ?>
                        <option value="<?= (int)$product['id'] ?>" 
                                data-stock="<?= (int)$product['current_stock'] ?>"
                                data-category="<?= $product['category_id'] ?? 'UNCATEGORIZED' ?>">
                            <?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Stok sistem -->
                <div style="margin-top:18px; color:#81776D;">
                    <?= htmlspecialchars(__('system_stock'), ENT_QUOTES, 'UTF-8') ?>:
                    <strong id="system_stock" style="color:#49382D;">
                        -
                    </strong>
                </div>

                <!-- Input stok fisik -->
                <label style="display:block; font-weight:bold; margin-top:25px; margin-bottom:8px;">
                    <?= htmlspecialchars(__('physical_stock'), ENT_QUOTES, 'UTF-8') ?>
                </label>

                <input type="number" id="physical_stock" min="0" oninput="calculateDifference()"
                    placeholder="<?= htmlspecialchars(__('enter_physical_stock'), ENT_QUOTES, 'UTF-8') ?>"
                    style="width:100%; box-sizing:border-box; padding: 13px; border:1px solid #E9E1D7; border-radius:12px; font-size:15px;">

                <!-- Selisih -->
                <div style="margin-top:20px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">
                        <?= htmlspecialchars(__('stock_difference'), ENT_QUOTES, 'UTF-8') ?>
                    </label>

                    <div id="difference"
                        style="height: 40px; background:#FAF7F2; border:1px solid #E9E1D7; border-radius:12px; display:flex; align-items:center; padding:0 16px;">
                        -
                    </div>

                </div>

                <!-- Catatan -->
                <label style="display:block; font-weight:bold; margin-top:20px; margin-bottom:8px;">
                    <?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?>
                    <span style="font-size:12px; color:#948578; font-weight:normal;">
                        (<?= htmlspecialchars(__('optional'), ENT_QUOTES, 'UTF-8') ?>)
                    </span>
                </label>

                <input type="text" id="notes" name="notes" maxlength="40" placeholder="<?= htmlspecialchars(__('notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                    style="width:100%; padding:10px 12px; box-sizing:border-box; border:1px solid #E9E1D7; border-radius:8px; font-size:14px; outline-color:#B99A7D;">

                <!-- Tombol save -->
                <div style="padding-top:20px; display:flex; justify-content:flex-end;">
                    <button type="button" onclick="saveAudit()" style="display:flex; align-items:center; gap:8px; padding:12px 19px; border:0; border-radius:9px; background:#76553D; color:white; font-weight:600; cursor:pointer;">
                        <i data-lucide="save" style="width:18px;height:18px;"></i>
                        <?= htmlspecialchars(__('save_audit'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>

            </div>

        </section>
    </main>
</div>

<!-- Library Ikon & Notifikasi UI Kustom -->
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/toast.js" defer></script>

<script>
// Rendernya ikon Lucide pada antarmuka
lucide.createIcons();

// Inisialisasi Elemen DOM Utama
const productSelect = document.getElementById('product_id');
const categoryFilter = document.getElementById('category_filter');

// Menyimpan Seluruh Opsi Produk Asli Beserta Datasets ke Array (Client-Side Cache)
let allProductOptions = [];
if (productSelect) {
    allProductOptions = Array.from(productSelect.options).map(opt => ({
        value: opt.value,
        text: opt.textContent,
        stock: opt.getAttribute('data-stock') || opt.dataset.stock || 0,
        category: opt.getAttribute('data-category') || opt.dataset.category || 'UNCATEGORIZED'
    }));
}

// Memperbarui Tampilan Stok Sistem Sesuai Produk yang Dipilih
function updateAuditStock() {
    if (!productSelect) return;

    // Ambil elemen opsi yang sedang dipilih dari dropdown produk
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    // Kembalikan ke tampilan default (-) jika tidak ada produk terpilih
    if (!productSelect.value || !selectedOption) {
        document.getElementById('system_stock').textContent = '-';
        calculateDifference();
        return;
    }

    // Ambil nilai stok dari atribut kustom atau dataset opsi produk
    const stock = selectedOption.getAttribute('data-stock') || selectedOption.dataset.stock || '-';
    document.getElementById('system_stock').textContent = stock;

    // Hitung ulang selisih stok setelah stok sistem diperbarui
    calculateDifference();
}

// Menjalankan pembaruan stok sistem saat pilihan produk diubah manual oleh pengguna
if (productSelect) {
    productSelect.addEventListener('change', updateAuditStock);
}

// Menghitung Selisih Antara Stok Fisik dan Stok Sistem
function calculateDifference() {
    // Membaca angka stok sistem dari teks elemen HTML
    const systemStock = parseInt(document.getElementById('system_stock').textContent);
    
    // Membaca angka stok fisik yang diinputkan pengguna
    const physicalStock = parseInt(document.getElementById('physical_stock').value);
    
    // Elemen penampung hasil selisih
    const result = document.getElementById('difference');

    // Kosongkan tampilan selisih jika input angka belum valid
    if (isNaN(systemStock) || isNaN(physicalStock)) {
        result.textContent = '-';
        return;
    }

    // Rumus selisih = stok fisik dikurangi stok sistem
    const difference = physicalStock - systemStock;
    
    // Tampilkan tanda + jika hasil selisih bernilai positif
    result.textContent = difference > 0 ? '+' + difference : difference;
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

            // Kondisi pencocokan kategori
            if (selectedCategory === 'ALL') {
                isMatch = true;
            } else if (selectedCategory === 'UNCATEGORIZED' && (!productCategory || productCategory === 'null' || productCategory === 'UNCATEGORIZED')) {
                isMatch = true;
            } else if (productCategory === selectedCategory) {
                isMatch = true;
            }

            // Buat elemen option baru dan pasang kembali atribut datasetnya jika cocok
            if (isMatch) {
                const newOpt = new Option(opt.text, opt.value);
                newOpt.setAttribute('data-stock', opt.stock);
                newOpt.setAttribute('data-category', opt.category);
                productSelect.add(newOpt);
            }
        });

        // Reset detail stok sistem setelah daftar produk disaring
        updateAuditStock();
    });
}

// Menangani Simpan Data Audit Stok Secara Asinkron (Fetch API)
async function saveAudit() {
    const productId = productSelect.value;
    const physicalStock = document.getElementById('physical_stock').value;
    const notes = document.getElementById('notes').value.trim();
    const saveButton = document.querySelector('button[onclick="saveAudit()"]');

    // Validasi pemilihan produk
    if (!productId) {
        showToast(<?= json_encode(__('select_product_first')) ?>, 'error');
        return;
    }

    // Validasi input angka stok fisik
    if (physicalStock === '' || parseInt(physicalStock) < 0) {
        showToast(<?= json_encode(__('enter_valid_physical')) ?>, 'error');
        return;
    }

    // Ubah status visual tombol saat proses menyimpan berlangsung
    const originalButtonHTML = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.style.opacity = '0.7';

    // Menyusun payload FormData untuk dikirimkan ke server backend
    const formData = new FormData();
    formData.append('business_id', <?= (int)$business_id ?>);
    formData.append('product_id', productId);
    formData.append('physical_stock', physicalStock);
    formData.append('notes', notes);

    try {
        // Mengirimkan data audit ke endpoint API PHP
        const response = await fetch('../api/create-stock-audit.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        // Tampilkan pesan respons server via notifikasi Toast
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast(<?= json_encode(__('system_error')) ?>, 'error');
        console.error(error);
    } finally {
        // Kembalikan kondisi tombol simpan ke keadaan semula
        saveButton.disabled = false;
        saveButton.style.opacity = '1';
        saveButton.innerHTML = originalButtonHTML;
        lucide.createIcons();
    }
}
</script>

</body>
</html>