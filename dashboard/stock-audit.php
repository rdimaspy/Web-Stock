<?php
// Validasi sesi, akses bisnis aktif, bahasa, & mata uang
require_once __DIR__ . '/../includes/auth_business.php';

$products = getBusinessProducts($conn, $business_id);
$categories = getBusinessCategories($conn, $business_id);

$currentPage = 'stock-audit.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('stock_audit_title'), ENT_QUOTES, 'UTF-8') ?> - StockIn</title>
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
                <h1 class="page-title"><?= htmlspecialchars(__('stock_audit_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="page-desc"><?= htmlspecialchars(__('stock_audit_description'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="card-form">
                <div class="card-form-header">
                    <div class="card-form-icon">
                        <i data-lucide="clipboard-check" style="width:28px;"></i>
                    </div>
                    <div>
                        <h2 style="margin:0; font-size:22px;"><?= htmlspecialchars(__('stock_audit_form_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <p style="margin:5px 0 0; color:#81776D;"><?= htmlspecialchars(__('stock_audit_form_description'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

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
                    <label class="form-label-lg"><?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select id="product_id" class="form-control">
                        <option value=""><?= htmlspecialchars(__('select_product'), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php while ($p = mysqli_fetch_assoc($products)): ?>
                            <option value="<?= (int) $p['id'] ?>"
                                    data-stock="<?= (int) $p['current_stock'] ?>"
                                    data-category="<?= $p['category_id'] ?? 'UNCATEGORIZED' ?>">
                                <?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <div style="margin-top:12px; color:#81776D; font-size:14px;">
                        <?= htmlspecialchars(__('system_stock'), ENT_QUOTES, 'UTF-8') ?>:
                        <strong id="system_stock" style="color:#49382D;">-</strong>
                    </div>
                </div>

                <!-- Stok Fisik & Selisih -->
                <div class="form-group-lg">
                    <label class="form-label-lg"><?= htmlspecialchars(__('physical_stock'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="number" id="physical_stock" min="0" class="form-control"
                        placeholder="<?= htmlspecialchars(__('enter_physical_stock'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group-lg">
                    <label class="form-label-lg"><?= htmlspecialchars(__('stock_difference'), ENT_QUOTES, 'UTF-8') ?></label>
                    <div id="difference" class="form-control" style="background:#FAF7F2; display:flex; align-items:center;">-</div>
                </div>

                <!-- Catatan -->
                <div class="form-group-lg">
                    <label class="form-label-lg">
                        <?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?>
                        <span class="label-optional">(<?= htmlspecialchars(__('optional'), ENT_QUOTES, 'UTF-8') ?>)</span>
                    </label>
                    <input type="text" id="notes" name="notes" maxlength="40" class="form-control"
                        placeholder="<?= htmlspecialchars(__('notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div style="padding-top:10px; display:flex; justify-content:flex-end;">
                    <button type="button" id="saveAuditButton" onclick="saveAudit()" class="btn btn-primary">
                        <i data-lucide="save" style="width:18px; height:18px;"></i>
                        <?= htmlspecialchars(__('save_audit'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
window.STOCK_CONFIG = {
    businessId: <?= (int) $business_id ?>,
    selectProductFirst: <?= json_encode(__('select_product_first')) ?>,
    enterValidPhysical: <?= json_encode(__('enter_valid_physical')) ?>,
    systemError: <?= json_encode(__('system_error')) ?>
};
</script>
</body>
</html>
