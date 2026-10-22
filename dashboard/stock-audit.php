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
    "SELECT id, product_name, current_stock
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
                        <option value="<?= (int)$product['id'] ?>" data-stock="<?= (int)$product['current_stock'] ?>">
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
lucide.createIcons();

// Memperbarui tampilan stok sistem sesuai produk yang dipilih
function updateAuditStock() {
    // Ambil elemen dropdown produk dan opsi yang sedang dipilih
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    
    // Ambil nilai stok dan gunakan karakter '-' jika kosong
    const stock = option.dataset.stock || '-';

    // Tampilkan nilai stok
    document.getElementById('system_stock').textContent = stock;

    // Hitung ulang selisih stok secara otomatis
    calculateDifference();
}

// Menghitung selisih antara stok fisik dan stok sistem
function calculateDifference() {
    // Ambil nilai stok sistem dari teks elemen UI
    const systemStock = parseInt(
        document.getElementById('system_stock').textContent
    );

    // Ambil nilai stok fisik yang diinputkan oleh pengguna
    const physicalStock = parseInt(
        document.getElementById('physical_stock').value
    );

    // Ambil elemen penampung hasil selisih
    const result = document.getElementById('difference');

    // Kembalikan ke tampilan default jika input stok belum berupa angka
    if (isNaN(systemStock) || isNaN(physicalStock)) {
        result.textContent = '-';
        return;
    }

    // Hitung hasil pengurangan stok fisik dengan stok sistem
    const difference = physicalStock - systemStock;

    // Tampilkan angka selisih beserta tanda plus (+) jika bernilai positif
    result.textContent = difference > 0 
        ? '+' + difference 
        : difference;
}

// Menangani proses penyimpanan data audit stok secara asinkron
async function saveAudit() {
    const productId = document.getElementById('product_id').value;
    const physicalStock = document.getElementById('physical_stock').value;
    const notes = document.getElementById('notes').value.trim();
    const saveButton = document.querySelector('button[onclick="saveAudit()"]');

    // Validasi pemilihan produk sebelum dikirim
    if (!productId) {
        showToast(<?= json_encode(__('select_product_first')) ?>, 'error');
        return;
    }

    // Validasi input angka stok fisik
    if (physicalStock === '' || parseInt(physicalStock) < 0) {
        showToast(<?= json_encode(__('enter_valid_physical')) ?>, 'error');
        return;
    }

    // Ubah UI tombol saat proses menyimpan
    const originalButtonHTML = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.style.opacity = '0.7';

    const formData = new FormData();
    formData.append('business_id', <?= (int)$business_id ?>);
    formData.append('product_id', productId);
    formData.append('physical_stock', physicalStock);
    formData.append('notes', notes);

    try {
        // Kirim permintaan pencatatan audit stok via Fetch API
        const response = await fetch('../api/create-stock-audit.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

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
        // Kembalikan tombol ke status aktif semula
        saveButton.disabled = false;
        saveButton.style.opacity = '1';
        saveButton.innerHTML = originalButtonHTML;
    }
}

</script>

</body>
</html>