<?php
// Validasi sesi, akses bisnis aktif, bahasa, & mata uang
require_once __DIR__ . '/../includes/auth_business.php';

$search = trim($_GET['search'] ?? '');
$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$sort_code = $_GET['sort_code'] ?? '';

// Ambil daftar kategori bisnis aktif
$categories = getBusinessCategories($conn, $business_id);

// Bangun query pencarian & filter produk secara dinamis
$sql = "SELECT p.*, c.category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id AND c.business_id = p.business_id
        WHERE p.business_id = ?";
$params = [$business_id];
$types = "i";

if ($search !== '') {
    $sql .= " AND (p.product_name LIKE ? OR p.product_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($category_id !== false && $category_id !== null) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$sql .= match ($sort_code) {
    'ASC'   => " ORDER BY p.product_code ASC",
    'DESC'  => " ORDER BY p.product_code DESC",
    default => " ORDER BY p.product_name ASC",
};

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);

$currentPage = 'products.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('products'), ENT_QUOTES, 'UTF-8') ?> - StockIn</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/currency.js?v=2"></script>
    <script src="../assets/js/toast.js?v=2" defer></script>
    <script src="../assets/js/app.js?v=2" defer></script>
</head>
<body>

<div class="dashboard-layout">
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <main class="dashboard-main">
        <?php require __DIR__ . '/components/header.php'; ?>

        <section class="page-section">
            <!-- Header Halaman & Tombol Tambah Produk -->
            <div class="page-header-flex">
                <div>
                    <h1 class="page-title"><?= htmlspecialchars(__('products'), ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="page-desc"><?= htmlspecialchars(__('page_products_description'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <button type="button" onclick="openAddProduct()" class="btn btn-primary">
                    <i data-lucide="plus" style="width:18px; height:18px;"></i>
                    <?= htmlspecialchars(__('add_product'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>

            <!-- Bar Pencarian dan Filter Produk -->
            <form method="GET" class="filter-bar">
                <input type="hidden" name="business_id" value="<?= (int) $business_id ?>">

                <div class="search-box">
                    <i data-lucide="search"></i>
                    <input type="search" name="search" class="form-control"
                        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="<?= htmlspecialchars(__('search_product'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <select name="category_id" class="form-control" style="width:auto; min-width:180px;">
                    <option value=""><?= htmlspecialchars(__('all_categories'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (int) $category_id === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="sort_code" class="form-control" style="width:auto; min-width:150px;">
                    <option value=""><?= htmlspecialchars(__('sort_code'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="ASC" <?= $sort_code === 'ASC' ? 'selected' : '' ?>><?= htmlspecialchars(__('sort_code_asc'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="DESC" <?= $sort_code === 'DESC' ? 'selected' : '' ?>><?= htmlspecialchars(__('sort_code_desc'), ENT_QUOTES, 'UTF-8') ?></option>
                </select>

                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('search'), ENT_QUOTES, 'UTF-8') ?></button>
                <a href="products.php?business_id=<?= (int) $business_id ?>" class="btn btn-danger"><?= htmlspecialchars(__('reset'), ENT_QUOTES, 'UTF-8') ?></a>
            </form>

            <!-- Tabel Daftar Produk -->
            <div class="table-wrapper">
                <div class="table-scroll" style="height:500px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php foreach (['product_name', 'product_code', 'category', 'purchase_price', 'selling_price', 'stock', 'expiration_date', 'notes', 'action'] as $col): ?>
                                    <th><?= htmlspecialchars(__($col), ENT_QUOTES, 'UTF-8') ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($products) === 0): ?>
                                <tr>
                                    <td colspan="9" class="empty-cell"><?= htmlspecialchars(__('no_products'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php while ($product = mysqli_fetch_assoc($products)):
                                    $photoFile = __DIR__ . '/../uploads/products/' . $product['id'] . '/' . ($product['photo'] ?? '');
                                    $hasPhoto = !empty($product['photo']) && file_exists($photoFile);
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:12px;">
                                                <?php if ($hasPhoto): ?>
                                                    <img src="../uploads/products/<?= (int) $product['id'] ?>/<?= htmlspecialchars($product['photo'], ENT_QUOTES, 'UTF-8') ?>" class="product-thumb-md" alt="Foto">
                                                <?php else: ?>
                                                    <div class="product-placeholder-md"><i data-lucide="image" style="width:22px;"></i></div>
                                                <?php endif; ?>
                                                <span class="cell-wrap" style="font-weight:600; min-width:140px; max-width:220px;">
                                                    <?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="cell-wrap" style="font-size:13px; font-weight:600; max-width:100px;">
                                            <?= htmlspecialchars($product['product_code'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td class="cell-wrap" style="color:#81776D; font-size:13px; max-width:120px;">
                                            <?= htmlspecialchars($product['category_name'] ?? __('uncategorized'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars(formatMoneyPHP($product['purchase_price'], $business['currency']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(formatMoneyPHP($product['selling_price'], $business['currency']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((int) $product['current_stock']) ?></td>
                                        <td>
                                            <?php if ($product['expiration_date']):
                                                $daysLeft = (int) (new DateTime())->diff(new DateTime($product['expiration_date']))->format('%r%a');
                                                $formattedDate = htmlspecialchars(date('d/m/Y', strtotime($product['expiration_date'])));
                                                if ($daysLeft < 0): ?>
                                                    <span class="badge badge-danger"><?= $formattedDate ?> (Expired)</span>
                                                <?php elseif ($daysLeft <= 30): ?>
                                                    <span class="badge badge-warning"><?= $formattedDate ?></span>
                                                <?php else: ?>
                                                    <span style="color:#81776D; font-size:13px;"><?= $formattedDate ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color:#81776D;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cell-wrap" style="color:#81776D; font-size:13px; max-width:180px;">
                                            <?= !empty(trim($product['notes'] ?? '')) ? htmlspecialchars($product['notes'], ENT_QUOTES, 'UTF-8') : '<span style="color:#B8B0A6;">—</span>' ?>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:8px;">
                                                <button type="button" onclick="openEditProduct(<?= (int) $product['id'] ?>)" class="btn-icon edit" title="<?= htmlspecialchars(__('edit'), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i data-lucide="pencil" style="width:16px; height:16px;"></i>
                                                </button>
                                                <button type="button" onclick="deleteProduct(<?= (int) $product['id'] ?>)" class="btn-icon delete" title="<?= htmlspecialchars(__('delete'), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<?php require __DIR__ . '/components/modal-product.php'; ?>

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
