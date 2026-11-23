<?php
// Validasi sesi, akses bisnis aktif, bahasa, & mata uang
require_once __DIR__ . '/../includes/auth_business.php';

$products = getBusinessProducts($conn, $business_id);
$productCount = mysqli_num_rows($products);
$categories = getBusinessCategories($conn, $business_id);

$currentPage = 'stock-in.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?> - StockIn</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/toast.js?v=2" defer></script>
    <script src="../assets/js/app.js?v=2" defer></script>
    <script src="../assets/js/stock.js?v=2" defer></script>
</head>
<body>

<div class="dashboard-layout">
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <main class="dashboard-main">
        <?php require __DIR__ . '/components/header.php'; ?>

        <section class="page-section">
            <div class="page-header">
                <h1 class="page-title"><?= htmlspecialchars(__('stock_in'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="page-desc"><?= htmlspecialchars(__('stock_in_desc'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="card-form">
                <div class="card-form-header">
                    <div class="card-form-icon">
                        <i data-lucide="package-plus" style="width:28px;"></i>
                    </div>
                    <div>
                        <h2 style="font-size:22px; margin:0;"><?= htmlspecialchars(__('new_stock_in'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <p style="font-size:13px; color:#81776D; margin:5px 0 0;"><?= htmlspecialchars(__('new_stock_in_desc'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <?php if ($productCount === 0): ?>
                    <div style="padding:22px; text-align:center; background:#FAF7F2; border-radius:10px; color:#81776D; line-height:1.7;">
                        <?= htmlspecialchars(__('no_products_stock_in'), ENT_QUOTES, 'UTF-8') ?>
                        <div style="margin-top:15px;">
                            <a href="products.php?business_id=<?= (int) $business_id ?>" style="color:#76553D; font-weight:600;">
                                <?= htmlspecialchars(__('go_to_products'), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <form id="stockInForm" method="POST">
                        <input type="hidden" name="business_id" value="<?= (int) $business_id ?>">

                        <!-- Filter Kategori -->
                        <div class="form-group-lg">
                            <label for="category_filter" class="form-label-lg"><?= htmlspecialchars(__('filter_category'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select id="category_filter" class="form-control">
                                <option value="ALL"><?= htmlspecialchars(__('all_categories'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="UNCATEGORIZED"><?= htmlspecialchars(__('uncategorized'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                    <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Pilih Produk -->
                        <div class="form-group-lg">
                            <label for="product_id" class="form-label-lg"><?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select id="product_id" name="product_id" class="form-control" required>
                                <option value=""><?= htmlspecialchars(__('select_product_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php while ($p = mysqli_fetch_assoc($products)): ?>
                                    <option value="<?= (int) $p['id'] ?>"
                                            data-stock="<?= (int) $p['current_stock'] ?>"
                                            data-category="<?= $p['category_id'] ?? 'UNCATEGORIZED' ?>">
                                        <?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <p id="currentStockInfo" style="font-size:13px; color:#81776D; margin:8px 0 0;">
                                <?= htmlspecialchars(__('current_stock_prompt'), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>

                        <!-- Jumlah Barang Masuk -->
                        <div class="form-group-lg">
                            <label for="quantity" class="form-label-lg"><?= htmlspecialchars(__('quantity_received'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="number" id="quantity" name="quantity" min="1" step="1" required class="form-control"
                                placeholder="<?= htmlspecialchars(__('enter_quantity_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <!-- Catatan -->
                        <div class="form-group-lg">
                            <label for="notes" class="form-label-lg">
                                <?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?>
                                <span class="label-optional">(<?= htmlspecialchars(__('optional'), ENT_QUOTES, 'UTF-8') ?>)</span>
                            </label>
                            <input type="text" id="notes" name="notes" maxlength="40" class="form-control"
                                placeholder="<?= htmlspecialchars(__('notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div style="padding-top:10px; display:flex; justify-content:flex-end;">
                            <button type="submit" id="stockInSubmitButton" class="btn btn-primary">
                                <i data-lucide="save" style="width:18px; height:18px;"></i>
                                <?= htmlspecialchars(__('save_stock_in'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<script>
window.STOCK_CONFIG = {
    businessId: <?= (int) $business_id ?>,
    currentStockPrompt: <?= json_encode(__('current_stock_prompt')) ?>,
    currentStockLabel: <?= json_encode(__('current_stock_label')) ?>,
    savingText: <?= json_encode(__('saving')) ?>,
    failedMsg: <?= json_encode(__('failed_save_stock_in')) ?>,
    errorMsg: <?= json_encode(__('error_saving_stock_in')) ?>
};
</script>
</body>
</html>
