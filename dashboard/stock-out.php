<?php
session_start();

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/currency.php';

// Ambil business aktif dari URL
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);

if (!$business_id || $business_id < 1) {
    header("Location: ../businesses.php");
    exit;
}

// Pastikan bisnis memang milik user yang login
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, business_name, currency
    FROM businesses
    WHERE id = ? AND user_id = ?
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

// Ambil produk aktif untuk pilihan Stock Out
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, product_name, current_stock, selling_price
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
        <?= htmlspecialchars(__('stock_out_title'), ENT_QUOTES, 'UTF-8') ?> - Stock
    </title>
</head>

<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'stock-out.php';?>

<!-- Layout utama -->
<div style="display:flex; min-height:100vh;">

    <!-- Sidebar -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- Konten utama -->
    <main style="margin-left:230px; flex:1; min-width:0;">

        <!-- Header -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- HALAMAN STOCK OUT -->
        <section style="padding:32px;">
            <div style="margin-bottom:25px;">
                <h1 style="margin:0; font-size:29px;">
                    <?= htmlspecialchars(__('stock_out_title'), ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <p style="color:#81776D; margin-top:10px;">
                    <?= htmlspecialchars(__('stock_out_description'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- Form Stock Out -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:28px; max-width:900px;">

                <!-- Header form -->
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:25px;">
                    <div style="width:55px; height:55px; border-radius:12px; background:#F5EFE8; display:flex; align-items:center; justify-content:center; color:#76553D;">
                        <i data-lucide="package-minus" style="width:28px;"></i>
                    </div>

                    <div>
                        <h2 style="margin:0; font-size:22px;">
                            <?= htmlspecialchars(__('record_stock_out'), ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <p style="margin:5px 0 0; color:#81776D;">
                            <?= htmlspecialchars(__('record_stock_out_description'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>

                <!-- Pilih produk -->
                <label style="display:block; font-weight:bold; margin-bottom:8px;">
                    <?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?>
                </label>

                <select id="product_id" style="width:100%; padding:14px; border:1px solid #E9E1D7; border-radius:10px; font-size:15px;">
                    <option value="">
                        <?= htmlspecialchars(__('select_product'), ENT_QUOTES, 'UTF-8') ?>
                    </option>

                    <?php while ($product = mysqli_fetch_assoc($products)): ?>
                        <option 
                            value="<?= (int)$product['id'] ?>"
                            data-stock="<?= (int)$product['current_stock'] ?>"
                            data-price="<?= (float)$product['selling_price'] ?>">

                            <?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>
                            — <?= $lang === 'en' ? 'Current stock:' : 'Stok saat ini:' ?>
                            <?= number_format((int)$product['current_stock']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Informasi stok -->
                <div style="margin-top:10px; color:#81776D; font-size:14px;">
                    <?= htmlspecialchars(__('available_stock'), ENT_QUOTES, 'UTF-8') ?>:
                    <strong id="availableStock">
                        -
                    </strong>
                </div>

                <div style="margin-top:8px; color:#81776D; font-size:14px;">
                    <?= htmlspecialchars(__('selling_price'), ENT_QUOTES, 'UTF-8') ?>:
                    <strong id="sellingPrice">
                        -
                    </strong>
                </div>

                <!-- Jumlah keluar -->
                <div style="margin-top:25px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">
                        <?= htmlspecialchars(__('quantity_out'), ENT_QUOTES, 'UTF-8') ?>
                    </label>

                    <input type="number" id="quantity" name="quantity" min="1" placeholder="<?= htmlspecialchars(__('quantity_out_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                        style="width:100%; padding:14px; border:1px solid #E9E1D7; border-radius:10px; font-size:15px;">
                </div>

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

                <!-- Tombol -->
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



<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>

<script>
/*Stock Out Calculation*/

const productSelect = document.getElementById('product_id');
const availableStock = document.getElementById('availableStock');
const sellingPrice = document.getElementById('sellingPrice');
const quantityInput = document.getElementById('quantity');
const totalAmount = document.getElementById('totalAmount');

let currentPrice = 0;

// Update informasi saat produk dipilih
productSelect.addEventListener('change', function () {

    const selectedOption = this.options[this.selectedIndex];

    if (!selectedOption.value) {
        availableStock.textContent = '-';
        sellingPrice.textContent = '-';
        totalAmount.value = '-';
        currentPrice = 0;
        return;
    }

    const stock = parseInt(selectedOption.dataset.stock);
    const price = parseFloat(selectedOption.dataset.price);

    currentPrice = price;

    // Menampilkan stok tersedia
    availableStock.textContent = stock.toLocaleString('id-ID');

    // Menampilkan harga jual
    sellingPrice.textContent = formatCurrency(price);

    calculateTotal();
});

// Menghitung total ketika jumlah berubah
quantityInput.addEventListener('input', function () {
    calculateTotal();
});

// Rumus total = jumlah keluar × harga jual
function calculateTotal() {
    const quantity = parseInt(quantityInput.value) || 0;
    const total = quantity * currentPrice;

    totalAmount.value = total > 0
        ? formatCurrency(total)
        : '-';

}

// Format mata uang
function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);

}

// Saat jumlah berubah
quantityInput.addEventListener('input', function () {
    calculateTotal();
});

// Menghitung total penjualan
function calculateTotal() {
    const quantity = parseInt(quantityInput.value) || 0;
    const total = quantity * currentPrice;

    if (total > 0) {
        totalAmount.value = formatCurrency(total);
    } else {
        totalAmount.value = '-';
    }
}

// Format Rupiah
function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
}

/* Menyimpan transaksi Stock Out*/

const saveButton = document.getElementById('saveStockOut');

saveButton.addEventListener('click', function () {
    const productId = productSelect.value;
    const quantity = quantityInput.value;
    const notes = document.getElementById('notes').value;

    // Validasi sederhana sebelum kirim
    if (!productId) {
        alert('Pilih produk terlebih dahulu.');
        return;
    }

    if (!quantity || quantity < 1) {
        alert('Masukkan jumlah barang keluar.');
        return;
    }

    // Cek stok sebelum mengirim transaksi
    const stockAvailable = parseInt(availableStock.textContent.replace(/\./g, ''));
    if (quantity > stockAvailable) {
        alert(
            'Stok tidak mencukupi. Stok tersedia: ' 
            + stockAvailable
        );
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
        'quantity',
        quantity
    );

    formData.append(
        'notes',
        notes
    );

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