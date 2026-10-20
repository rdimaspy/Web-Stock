<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Muat pengelola bahasa & mata uang
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/currency.php';

// Pencarian produk
$search = trim($_GET['search'] ?? '');
$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);

// Ambil kategori milik bisnis aktif
$stmt = mysqli_prepare($conn, "SELECT id, category_name FROM categories WHERE business_id = ? ORDER BY category_name ASC");
mysqli_stmt_bind_param($stmt, "i", $business_id);
mysqli_stmt_execute($stmt);
$categories = mysqli_stmt_get_result($stmt);

// Ambil produk beserta nama kategorinya
$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id AND c.business_id = p.business_id WHERE p.business_id = ?";
$params = [$business_id];
$types = "i";

// Filter pencarian berdasarkan nama produk atau kode produk
if ($search !== '') {
    $sql .= " AND (p.product_name LIKE ? OR p.product_code LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
    $types .= "ss";
}

// Filter berdasarkan kategori produk jika kategori dipilih
if ($category_id !== false && $category_id !== null) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

// Ambil pilihan urutan kode produk dari URL (ASC atau DESC)
$sort_code = $_GET['sort_code'] ?? '';

// Tentukan aturan pengurutan SQL berdasarkan pilihan pengguna
if ($sort_code === 'ASC') {
    $sql .= " ORDER BY p.product_code ASC";
} elseif ($sort_code === 'DESC') {
    $sql .= " ORDER BY p.product_code DESC";
} else {
    // Default pengurutan alfabetis berdasarkan nama produk (A-Z)
    $sql .= " ORDER BY p.product_name ASC";
}

// Prepare statement query dinamis, bind parameter yang sesuai, eksekusi query, dan ambil hasil data produk
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);
?>



<!DOCTYPE html>
<!-- Set bahasa -->
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('products'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>
</head>

<!-- Styling utama layout dan warna latar halaman -->
<body style="width:100%; margin:0; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'products.php';?>

<!-- Layout utama halaman Produk -->
<div style="display:flex; width:100%; height:100vh;">
    <!-- SIDEBAR -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- KONTEN -->
    <main style="margin-left:230px; flex:1; min-width:0; min-height:100vh; display:flex; flex-direction:column;">

        <!-- HEADER -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- Judul dan filter -->
        <section style="padding:32px; display:flex; flex-direction:column; box-sizing:border-box;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:25px; flex-shrink:0;">
                <div>
                    <h1 style="margin:0; font-size:29px;"><?= htmlspecialchars(__('products'), ENT_QUOTES, 'UTF-8') ?></h1>
                    <p style="color:#81776D; margin:10px 0 0;"><?= htmlspecialchars(__('page_products_description'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <button type="button" onclick="openAddProduct()" style="display:flex; align-items:center; gap:8px; padding:12px 17px; background:#76553D; color:white; border:0; border-radius:9px; cursor:pointer; font-weight:bold;">
                    <i data-lucide="plus" style="width:18px;height:18px;"></i><?= htmlspecialchars(__('add_product'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>

            <!-- PENCARIAN DAN FILTER -->
            <form method="GET" style="display:flex; flex-shrink:0; gap:12px; flex-wrap:wrap; background:white; border:1.5px solid #E9E1D7; border-radius:12px; padding:18px; margin-bottom:20px;">
                <!-- Kirim ID bisnis aktif sebagai parameter tersembunyi -->
                <input type="hidden" name="business_id" value="<?= $business_id ?>">

                <!-- Input pencarian produk berdasarkan nama -->
                <div style="flex:1; min-width:200px; position:relative;">
                    <i data-lucide="search" style="position:absolute; left:12px; top:13px; width:18px; height:18px; color:#948578;"></i>
                    <input type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('search_product'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:12px 12px 12px 39px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                </div>

                <!-- Dropdown filter berdasarkan kategori -->
                <select name="category_id" style="min-width:180px; padding:12px; border:1px solid #E9E1D7; border-radius:8px; background:white; color:#49382D;">
                    <option value=""><?= htmlspecialchars(__('all_categories'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php $stmtModalCategories = mysqli_prepare(
                        $conn,
                        "SELECT id, category_name
                        FROM categories
                        WHERE business_id = ?
                        ORDER BY category_name ASC"
                    );

                    // Parameter ID bisnis ke query kategori, eksekusi, ambil kategori
                    mysqli_stmt_bind_param($stmtModalCategories, "i", $business_id);
                    mysqli_stmt_execute($stmtModalCategories);
                    $modalCategories = mysqli_stmt_get_result($stmtModalCategories);
                    ?>

                    <!-- Mengambil baris nama kategori yang ada di database -->
                    <?php while ($category = mysqli_fetch_assoc($modalCategories)): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $category_id === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Dropdown Filter Urutan Kode Produk -->
                <select name="sort_code" style="min-width:150px; padding:12px; border:1px solid #E9E1D7; border-radius:8px; background:white; color:#49382D;">
                    <option value=""><?= htmlspecialchars(__('sort_code'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="ASC" <?= ($_GET['sort_code'] ?? '') === 'ASC' ? 'selected' : '' ?>><?= htmlspecialchars(__('sort_code_asc'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="DESC" <?= ($_GET['sort_code'] ?? '') === 'DESC' ? 'selected' : '' ?>><?= htmlspecialchars(__('sort_code_desc'), ENT_QUOTES, 'UTF-8') ?></option>
                </select>

                <!-- Tombol submit filter -->
                <button type="submit" style="padding:12px 18px; background:#76553D; color:white; border:0; border-radius:8px; cursor:pointer; font-weight:600;"><?= htmlspecialchars(__('search'), ENT_QUOTES, 'UTF-8') ?></button>

                <!-- Tombol reset -->
                <a href="products.php?business_id=<?= $business_id ?>" style="display:inline-flex; align-items:center; justify-content:center; padding:12px 18px; background:#B45F5F; color:white; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px; box-sizing:border-box;">
                    <?= htmlspecialchars(__('reset'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </form>

            <!-- TABEL PRODUK -->
            <!-- Container tabel -->
            <div style=" height:500px; overflow-y:auto; overflow-x:auto; position:relative; background:white; border:1.5px solid #E9E1D7; border-radius:12px;">
                <table style="width:100%; border-collapse:collapse; text-align:left; white-space:nowrap;">
                    <!-- Header tabel -->
                    <thead>
                        <!-- Kolom nama produk -->
                        <tr style="background:#F5EFE8; text-align:left;">
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:18%; background:#F5EFE8;"><?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:12%; background:#F5EFE8;"><?= htmlspecialchars(__('product_code'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:12%; background:#F5EFE8;"><?= htmlspecialchars(__('category'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:15%; background:#F5EFE8;"><?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:13%; background:#F5EFE8;"><?= htmlspecialchars(__('purchase_price'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:13%; background:#F5EFE8;"><?= htmlspecialchars(__('selling_price'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:7%; background:#F5EFE8;"><?= htmlspecialchars(__('stock'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:12%; background:#F5EFE8;"><?= htmlspecialchars(__('expiration_date'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:10%; background:#F5EFE8;"><?= htmlspecialchars(__('action'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Cek ketersediaan data produk -->
                        <?php if (mysqli_num_rows($products) > 0): ?>
                            <!-- Looping untuk menampilkan baris data setiap produk -->
                            <?php while ($product = mysqli_fetch_assoc($products)): ?>
                                <tr style="border-top:1px solid #F0E8DF;">
                                    <!-- Data pada kolom -->
                                    <td style="padding:10px 12px;">
                                        <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                                            <!-- Tampilkan foto produk jika ada di server -->
                                            <?php if(!empty($product['photo']) && file_exists(__DIR__ . '/../uploads/products/' . $product['id'] . '/' . $product['photo'])): ?>
                                                <img src="../uploads/products/<?= $product['id'] ?>/<?= htmlspecialchars($product['photo'], ENT_QUOTES, 'UTF-8') ?>" style="width:50px; height:50px; min-width:50px; flex-shrink:0; border-radius:10px; object-fit:cover; border:1px solid #E9E1D7;">
                                                <!-- jika produk tidak memiliki foto pakai inisial nama produk -->
                                            <?php else: ?>
                                                <div style="width:50px; height:50px; min-width:50px; flex-shrink:0; border-radius:10px; background:#F5EFE8; display:flex; align-items:center; justify-content:center;"><i data-lucide="image" style="width:22px;color:#76553D;"></i></div>
                                            <?php endif; ?>
                                            <span style="font-weight:600; display:block; max-width:180px; word-break:break-word; overflow-wrap:anywhere;"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <!-- Kode Produk -->
                                    <td style="padding:10px 12px; color:#49382D; font-size:13px; font-weight:600;">
                                        <?= htmlspecialchars($product['product_code'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <!-- Kategori-->
                                    <td style="padding:10px 12px; color:#81776D; font-size:13px"><?= htmlspecialchars($product['category_name'] ?? __('uncategorized'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- Catatam -->
                                    <td title="<?= htmlspecialchars($product['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding:10px 12px; color:#81776D; font-size:13px; overflow-wrap:anywhere;"><?php if (!empty(trim($product['notes'] ?? ''))): ?>
                                        <div style="display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:2; overflow:hidden;"><?= htmlspecialchars($product['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <!-- Harga beli -->
                                    <td style="padding:10px 12px; white-space:nowrap;"><?= htmlspecialchars(formatMoneyPHP($product['purchase_price'], $business['currency']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- Harga Jual -->
                                    <td style="padding:10px 12px; white-space:nowrap;"><?= htmlspecialchars(formatMoneyPHP($product['selling_price'], $business['currency']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- Stok sekarang -->
                                    <td style="padding:10px 12px;"><?= number_format((int) $product['current_stock']) ?></td>
                                    <!-- Expired -->
                                    <td style="padding:10px 12px;">
                                        <?php if ($product['expiration_date']): ?>
                                            <?php 
                                                $today = new DateTime();
                                                $expDate = new DateTime($product['expiration_date']);
                                                $interval = $today->diff($expDate);
                                                $daysLeft = (int)$interval->format('%r%a'); // Menghitung selisih hari (+ / -)
                                            ?>

                                            <?php if ($daysLeft < 0): ?>
                                                <!-- KONDISI 1: SUDAH KEDALUWARSA (Merah) -->
                                                <span style="display:inline-block; padding:4px 8px; background:#FBECEC; color:#B45F5F; border-radius:6px; font-size:12px; font-weight:600;" title="Sudah Kedaluwarsa">
                                                    <?= htmlspecialchars(date('d/m/Y', strtotime($product['expiration_date']))) ?> (Expired)
                                                </span>

                                            <?php elseif ($daysLeft <= 30): ?>
                                                <!-- KONDISI 2: HAMPIR KEDALUWARSA <= 30 HARI (Kuning) -->
                                                <span style="display:inline-block; padding:4px 8px; background:#FFF8E7; color:#B7791F; border-radius:6px; font-size:12px; font-weight:600;" title="Hampir Kedaluwarsa">
                                                    <?= htmlspecialchars(date('d/m/Y', strtotime($product['expiration_date']))) ?>
                                                </span>

                                            <?php else: ?>
                                                <!-- KONDISI 3: MASIH AMAN (Teks Normal) -->
                                                <span style="color:#81776D; font-size:13px;">
                                                    <?= htmlspecialchars(date('d/m/Y', strtotime($product['expiration_date']))) ?>
                                                </span>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <span style="color:#81776D;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Tombol edit & hapus -->
                                    <td style="padding:10px 12px;">
                                        <div style="display:flex; gap:8px; align-items:center;">
                                            <!-- Tombol edit -->
                                            <button type="button" onclick="openEditProduct(<?= (int) $product['id'] ?>)" style="width:32px; height:32px; border:none; background:#F5EFE8; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#76553D;" title="<?= htmlspecialchars(__('edit'), ENT_QUOTES, 'UTF-8') ?>">
                                                <i data-lucide="pencil" style="width:16px;height:16px;"></i>
                                            </button>
                                            <!-- Tombol hapus -->
                                            <button type="button" onclick="deleteProduct(<?= (int) $product['id'] ?>)" style="width:32px; height:32px; border:none; background:#FBECEC; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#B45F5F;" title="<?= htmlspecialchars(__('delete'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__('delete_product'), ENT_QUOTES, 'UTF-8') ?>">
                                                <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <!-- Tampilan baris khusus saat data produk tidak ditemukan, dengan menggabungkan 8 kolom -->
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="padding:35px; text-align:center; color:#81776D;"><?= htmlspecialchars(__('no_products'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>
</div>


<!-- COMPONENT MODAL -->
<?php require __DIR__ . '/components/modal-product.php'; ?>

<!-- ICON, ASSETS JS HELPERS -->
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/currency.js"></script>
<script src="../assets/js/toast.js" defer></script>

<!-- DEKLARASI VARIABEL GLOBAL UNTUK JS EXTERNAL -->
<script>
const CURRENT_BUSINESS_ID = <?= (int) $business_id ?>;
const APP_TRANSLATIONS = {
    add_product_title: <?= json_encode(__('add_product_title')) ?>,
    add_product_description: <?= json_encode(__('add_product_description')) ?>,
    edit_product_title: <?= json_encode(__('edit_product_title')) ?>,
    edit_product_description: <?= json_encode(__('edit_product_description')) ?>,
    save_category: <?= json_encode(__('save_category')) ?>,
    saving: <?= json_encode(__('saving')) ?>,
    category_name_required: <?= json_encode(__('category_name_required')) ?>,
    category_save_failed: <?= json_encode(__('category_save_failed')) ?>,
    category_error: <?= json_encode(__('category_error')) ?>,
    product_save_error: <?= json_encode(__('product_save_error')) ?>,
    delete_product_confirm: <?= json_encode(__('delete_product_confirm')) ?>,
    delete: <?= json_encode(__('delete')) ?>,
    cancel: <?= json_encode(__('cancel')) ?>,
    product_delete_failed: <?= json_encode(__('product_delete_failed')) ?>,
    product_delete_error: <?= json_encode(__('product_delete_error')) ?>,
    connection_error: <?= json_encode(__('connection_error')) ?>
};
</script>
<script src="../assets/js/products.js" defer></script>

</body>
</html>