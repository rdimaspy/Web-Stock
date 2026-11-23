<?php
// Memulai sesi dan memuat pengaturan bahasa aktif
session_start();
require_once __DIR__ . '/includes/language.php';

// Daftar statistik dummy pada kartu pratinjau hero
$previewStats = [
    ['total_products', '128', 'registered_products'],
    ['stock_in', '+24', 'units_received'],
    ['stock_out', '-12', 'units_sold'],
    ['revenue', 'Rp 2,4 jt', 'todays_sales'],
];

// Daftar kartu fitur unggulan aplikasi
$features = [
    ['package', 'feat_product_mgmt', 'feat_product_desc'],
    ['arrow-down-up', 'feat_stock_move', 'feat_stock_desc'],
    ['history', 'feat_history', 'feat_history_desc'],
    ['chart-no-axes-combined', 'feat_stats', 'feat_stats_desc'],
    ['file-spreadsheet', 'feat_reports', 'feat_reports_desc'],
    ['store', 'feat_multistore', 'feat_multistore_desc'],
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="StockIn membantu pemilik bisnis mengelola produk, persediaan, penjualan, dan laporan stok.">
    <title>StockIn | Stock Management</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="assets/js/app.js?v=3" defer></script>
</head>
<body class="landing-body">

    <!-- Navigasi Utama (Navbar) -->
    <header class="site-header">
        <div class="navbar-container">
            <?php renderLogo(); ?>

            <div class="nav-actions">
                <!-- Dropdown Pilihan Bahasa -->
                <div class="dropdown-wrap">
                    <button id="languageButton" class="lang-trigger" type="button">
                        <i data-lucide="globe" style="width:16px; height:16px;"></i>
                        <i data-lucide="chevron-down" style="width:13px; height:13px;"></i>
                    </button>

                    <div id="languageMenu" class="dropdown-menu">
                        <form action="set-language.php" method="POST" style="margin:0;">
                            <input type="hidden" name="redirect" value="index.php">
                            <?php foreach (['id' => 'Indonesia', 'en' => 'English'] as $code => $label): ?>
                                <button type="submit" name="language" value="<?= $code ?>" class="dropdown-option <?= $lang === $code ? 'active' : '' ?>">
                                    <?= $label ?>
                                </button>
                            <?php endforeach; ?>
                        </form>
                    </div>
                </div>

                <a href="login.php" class="nav-login-link"><?= htmlspecialchars(__('login'), ENT_QUOTES, 'UTF-8') ?></a>
                <a href="register.php" class="btn btn-primary btn-sm"><?= htmlspecialchars(__('signup'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </div>
    </header>

    <!-- Bagian Hero & Pratinjau Dashboard -->
    <section id="hero" class="hero-section">
        <div class="hero-container">
            <div class="hero-text-col">
                <h1 class="hero-title">
                    <span><?= htmlspecialchars(__('hero_title_1'), ENT_QUOTES, 'UTF-8') ?></span><br>
                    <span class="hero-title-accent"><?= htmlspecialchars(__('hero_title_2'), ENT_QUOTES, 'UTF-8') ?></span>
                </h1>
                <p class="hero-desc"><?= htmlspecialchars(__('hero_desc'), ENT_QUOTES, 'UTF-8') ?></p>
                <div style="margin-top:28px;">
                    <a href="register.php" class="btn btn-primary btn-lg">
                        <span><?= htmlspecialchars(__('create_account'), ENT_QUOTES, 'UTF-8') ?></span> →
                    </a>
                </div>
            </div>

            <!-- Kartu Pratinjau Visual Dashboard -->
            <div class="hero-preview-col">
                <div class="preview-card">
                    <div class="preview-header">
                        <div>
                            <h2 style="color:#302921; font-size:15px; margin:0;"><?= htmlspecialchars(__('business_overview'), ENT_QUOTES, 'UTF-8') ?></h2>
                            <p style="margin:4px 0 0; color:#81776D; font-size:11px;"><?= htmlspecialchars(__('dashboard_preview'), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <span class="badge badge-neutral" style="font-size:11px;"><?= htmlspecialchars(__('today'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="preview-grid">
                        <?php foreach ($previewStats as [$titleKey, $val, $subKey]): ?>
                            <div class="preview-stat-box">
                                <p style="color:#81776D; font-size:11px; margin:0;"><?= htmlspecialchars(__($titleKey), ENT_QUOTES, 'UTF-8') ?></p>
                                <h3 style="margin:6px 0 0; color:#302921; font-size:21px;"><?= $val ?></h3>
                                <p style="color:#6F8966; font-size:10px; margin:4px 0 0;"><?= htmlspecialchars(__($subKey), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h3 style="margin:10px 0 8px; color:#302921; font-size:13px;"><?= htmlspecialchars(__('sample_products'), ENT_QUOTES, 'UTF-8') ?></h3>

                    <div class="preview-sample-row">
                        <div class="preview-sample-icon">K</div>
                        <div style="flex:1;">
                            <p style="color:#302921; font-size:12px; font-weight:600; margin:0;">Kemeja Basic</p>
                            <p style="color:#81776D; font-size:10px; margin:2px 0 0;"><?= htmlspecialchars(__('clothing'), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <strong style="color:#302921; font-size:12px;">42 unit</strong>
                    </div>

                    <div class="preview-sample-row">
                        <div class="preview-sample-icon alt">T</div>
                        <div style="flex:1;">
                            <p style="color:#302921; font-size:12px; font-weight:600; margin:0;">Tote Bag Canvas</p>
                            <p style="color:#81776D; font-size:10px; margin:2px 0 0;"><?= htmlspecialchars(__('accessories'), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <strong style="color:#302921; font-size:12px;">28 unit</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bagian Daftar Fitur Aplikasi -->
    <section id="features" class="features-section">
        <div class="features-container">
            <div class="features-header">
                <p class="features-tag"><?= htmlspecialchars(__('features_tag'), ENT_QUOTES, 'UTF-8') ?></p>
                <h2 style="margin-top:10px; color:#302921; font-size:28px; line-height:1.3;"><?= htmlspecialchars(__('features_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p style="margin-top:10px; color:#81776D; font-size:14px; line-height:1.7;"><?= htmlspecialchars(__('features_desc'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="features-grid">
                <?php foreach ($features as [$icon, $titleKey, $descKey]): ?>
                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <i data-lucide="<?= $icon ?>" style="width:23px; height:23px;"></i>
                        </div>
                        <h3 style="margin-top:16px; color:#302921; font-size:16px;"><?= htmlspecialchars(__($titleKey), ENT_QUOTES, 'UTF-8') ?></h3>
                        <p style="margin-top:8px; color:#81776D; font-size:13px; line-height:1.7;"><?= htmlspecialchars(__($descKey), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <?php renderLogo(); ?>
            <p style="color:#A0988E; font-size:11px; margin:0;">© 2026 StockIn. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
