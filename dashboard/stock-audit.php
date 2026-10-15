<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/currency.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

// Ambil business aktif dari URL
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);

if (!$business_id || $business_id < 1) {
    header("Location: ../businesses.php");
    exit;
}

// Pastikan bisnis milik user yang login
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

// Ambil produk untuk audit stok
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, product_name, current_stock
    FROM products
    WHERE business_id = ?
    ORDER BY product_name ASC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $business_id
);

mysqli_stmt_execute($stmt);

$products = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars(__('stock_audit_title'), ENT_QUOTES, 'UTF-8') ?>
    </title>
</head>

<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'stock-audit.php';?>

<div style="display:flex; min-height:100vh;">

    <!-- Sidebar -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- Konten utama -->
    <main style="margin-left:230px; flex:1; min-width:0;">

        <!-- Header -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- Halaman stock auditt -->
        <section style="padding:32px;">
            <div style="margin-bottom:25px;">

                <h1 style="margin:0; font-size:29px;">
                    <?= htmlspecialchars(__('stock_audit_title'), ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <p style="color:#81776D; margin-top:10px;">
                    <?= htmlspecialchars(__('stock_audit_description'), ENT_QUOTES, 'UTF-8') ?>
                </p>

            </div>

            <!-- Form Audit Stok -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:28px; max-width:900px;">
                <!-- Header form -->
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:25px;">
                    <div style="width:55px; height:55px; border-radius:12px; background:#F5EFE8; display:flex; align-items:center; justify-content:center; color:#76553D;">
                        <i data-lucide="clipboard-check" style="width:28px;"></i>
                    </div>

                    <div>
                        <h2 style="margin:0; font-size:22px;">
                            <?= htmlspecialchars(__('stock_audit_form_title'), ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <p style="margin:5px 0 0; color:#81776D;">
                            <?= htmlspecialchars(__('stock_audit_form_description'), ENT_QUOTES, 'UTF-8') ?>
                        </p>

                    </div>

                </div>

                <!-- Pilih produk -->
                <label style="display:block; font-weight:bold; margin-bottom:8px;">
                    <?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?>
                </label>

                <select id="product_id" onchange="updateAuditStock()" style="width:100%; height:54px; padding:0 16px; border:1px solid #E9E1D7; border-radius:12px; font-size:16px; background:white;">
                    <option value="">
                        <?= htmlspecialchars(__('select_product'), ENT_QUOTES, 'UTF-8') ?>
                    </option>

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
                    style="width:100%; height:54px; padding:0 16px; box-sizing:border-box; border:1px solid #E9E1D7; border-radius:12px; font-size:16px;">

                <!-- Selisih -->
                <div style="margin-top:20px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">
                        <?= htmlspecialchars(__('stock_difference'), ENT_QUOTES, 'UTF-8') ?>
                    </label>

                    <div id="difference"
                        style="height:54px; background:#FAF7F2; border:1px solid #E9E1D7; border-radius:12px; display:flex; align-items:center; padding:0 16px;">
                        -
                    </div>

                </div>

                <!-- Catatan -->
                <label style="display:block; font-weight:bold; margin-top:20px; margin-bottom:8px;">
                    <?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?>
                </label>

                <textarea id="notes" rows="4" placeholder="<?= htmlspecialchars(__('optional_notes'), ENT_QUOTES, 'UTF-8') ?>"
                    style="width:100%; padding:14px 16px; box-sizing:border-box; border:1px solid #E9E1D7; border-radius:12px; font-size:16px; resize:vertical;">
                </textarea>

                <!-- Tombol save -->
                <div style="padding-top:20px; border-top:1px solid #E9E1D7; display:flex; justify-content:flex-end;">
                    <button type="button" onclick="saveAudit()" style="display:flex; align-items:center; gap:8px; padding:12px 19px; border:0; border-radius:9px; background:#76553D; color:white; font-weight:600; cursor:pointer;">
                        <i data-lucide="save" style="width:18px;height:18px;"></i>
                        <?= htmlspecialchars(__('save_audit'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>

            </div>

        </section>

    </main>

</div>

<script src="https://unpkg.com/lucide@latest"></script>

<script>
lucide.createIcons();
</script>

<script>
// Menampilkan stok sistem saat produk dipilih
function updateAuditStock() {
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    const stock = option.dataset.stock || '-';

    document.getElementById('system_stock').textContent = stock;

    calculateDifference();
}

// Menghitung selisih stok fisik dan stok sistem
function calculateDifference() {
    const systemStock = parseInt(
        document.getElementById('system_stock').textContent
    );

    const physicalStock = parseInt(
        document.getElementById('physical_stock').value
    );

    const result = document.getElementById('difference');

    if (isNaN(systemStock) || isNaN(physicalStock)) {
        result.textContent = '-';
        return;
    }

    const difference = physicalStock - systemStock;

    result.textContent = difference > 0 
        ? '+' + difference 
        : difference;
}

// Simpan audit stok ke API
function saveAudit() {
    const productId = document.getElementById('product_id').value;
    const physicalStock = document.getElementById('physical_stock').value;
    const notes = document.getElementById('notes').value;

    // Validasi produk
    if (!productId) {
        alert('Pilih produk terlebih dahulu.');
        return;
    }

    // Validasi stok fisik
    if (physicalStock === '' || physicalStock < 0) {
        alert('Masukkan stok fisik yang valid.');
        return;
    }

    const formData = new FormData();

    formData.append(
        'business_id',
        <?= (int)$business_id ?>
    );

    formData.append(
        'product_id',
        productId
    );

    formData.append(
        'physical_stock',
        physicalStock
    );

    formData.append(
        'notes',
        notes
    );

    fetch('../api/create-stock-audit.php', {
        method: 'POST',
        body: formData
    })

    .then(response => response.json())

    .then(result => {
        if (result.success) {
            alert(result.message);

            // Refresh agar stok terbaru terlihat
            window.location.reload();

        } else {
            alert(result.message);
        }
    })

    .catch(error => {
        alert('Terjadi kesalahan sistem.');
        console.error(error);
    });
}

</script>

</body>

</html>