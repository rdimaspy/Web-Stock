<?php
// Memulai sesi login dan menentukan bahasa aktif
session_start();
$lang = $_SESSION['language'] ?? 'id';

// Memuat kamus bahasa berdasarkan variabel sesi
$langFile = __DIR__ . "/lang/{$lang}.php";
$trans = file_exists($langFile) ? require $langFile : require __DIR__ . "/lang/id.php";
?>



<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang); ?>">
<head>
    <!-- Pengaturan karakter dan viewport halaman agar responsif di HP -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Deskripsi meta untuk SEO -->
    <meta name="description" content="Stock membantu pemilik bisnis mengelola produk, persediaan, penjualan, dan laporan stok.">

    <title>Stock | Stock Management</title>
    <!-- Library ikon Lucide -->
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="assets/js/app.js" defer></script>
</head>

<!-- Body Utama Halaman -->
<body>
    <!-- Navigasi (Navbar) -->
    <header style="width: 100%; background: #FFFFFF; border-bottom: 2px solid #E9E1D7;">
        <div class="navbar-container" style="max-width: 1160px; height: 64px; margin: 0 auto; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">

            <!-- Identitas dan logo aplikasi -->
            <a href="index.php"
            style="display: flex; align-items: center; gap: 9px; color: #302921; text-decoration: none; font-size: 22px; font-weight: 700;">

                <span style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; background: #76553D; color: #FFFFFF; border-radius: 10px; font-size: 17px;">
                    S
                </span>

                <span>
                    Stock<span style="color:#A77D5B;">.</span>
                </span>
            </a>

            <!-- Menu navigasi kanan -->
            <div style="display: flex; align-items: center; gap: 16px;">

                <!-- Form switcher bahasa -->
                <div style="position: relative;">
                    <button id="languageButton" style="display:flex; align-items:center; gap:5px; border:none; background:none; cursor:pointer; color:#81776D; padding:4px;">
                        <i data-lucide="globe" style="width:16px; height:16px;"></i>
                        <i data-lucide="chevron-down" style="width:13px; height:13px;"></i>
                    </button>

                    <!-- Form submit untuk ganti bahasa via set-language.php -->
                    <div id="languageMenu" style="display:none; position:absolute; right:0; top:32px; width:140px; padding:8px; background:#FFFFFF; border:1px solid #E9E1D7; border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.08); z-index: 100;">
                        <form action="set-language.php" method="POST" style="margin: 0; padding: 0;">
                            <input type="hidden" name="redirect" value="index.php">
                            
                            <button type="submit" name="language" value="id" style="width:100%; padding:8px; border:none; background:<?= $lang === 'id' ? '#F5EEE5' : 'none'; ?>; border-radius:7px; text-align:left; cursor:pointer;">
                                Indonesia
                            </button>

                            <button type="submit" name="language" value="en" style="width:100%; padding:8px; border:none; background:<?= $lang === 'en' ? '#F5EEE5' : 'none'; ?>; border-radius:7px; text-align:left; cursor:pointer;">
                                English
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tombol navigasi masuk -->
                <a href="login.php" style="color:#302921; text-decoration:none; font-size:13px; font-weight:600;">
                    <?= htmlspecialchars($trans['login'] ?? 'Masuk'); ?>
                </a>

                <!-- Tombol registrasi akun baru -->
                <a href="register.php" style="padding:8px 15px; background:#76553D; color:#FFFFFF; border-radius:8px; text-decoration: none; font-size: 13px;">
                    <?= htmlspecialchars($trans['signup'] ?? 'Daftar'); ?>
                </a>
            </div>

        </div>
    </header>

    <!-- Bagian utama Hero Section -->
    <section id="hero" class="hero-section" style="padding: 75px 24px; background: radial-gradient(ellipse at 85% 15%, #F5EBDD 0%, #FFFFFF 55%);">
        <div class="hero-container" style="max-width: 1160px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 45px;">

            <!-- Teks ajakan bertindak (Call to Action) -->
            <div style="flex: 1 1 300px;">

                <h1 style="margin: 0 0 22px; color: #302921; font-size: clamp(32px, 5vw, 58px); line-height: 1.15; letter-spacing: -1.5px;">
                    <span><?= htmlspecialchars($trans['hero_title_1'] ?? 'Stok teratur.'); ?></span>
                    <br>
                    <span style="color: #A77D5B;"><?= htmlspecialchars($trans['hero_title_2'] ?? 'Bisnis lebih terarah.'); ?></span>
                </h1>

                <p style="max-width: 455px; color: #81776D; font-size: 15px; line-height: 1.8;">
                    <?= htmlspecialchars($trans['hero_desc'] ?? 'Kelola produk, pantau pergerakan stok, dan pahami perkembangan bisnismu dalam satu tempat.'); ?>
                </p>

                <div style="margin-top: 28px;">
                    <a href="register.html"
                    style="display: inline-block; padding: 13px 24px; background: #76553D; color: #FFFFFF; border-radius: 9px; text-decoration: none; font-size: 14px; font-weight: 600;">
                        <span><?= htmlspecialchars($trans['create_account'] ?? 'Buat akun'); ?></span> →
                    </a>
                </div>

            </div>

            <!-- Pratinjau visual kartu dashboard -->
            <div style="flex: 1 1 300px; max-width: 475px; width: 100%; margin: 0 auto;">
                <div style="padding: 20px; background: #FFFFFF; border: 1px solid #E9E1D7; border-radius: 18px; box-shadow: 0 20px 50px rgba(92, 69, 48, 0.12);">

                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding-bottom: 16px; border-bottom: 1px solid #E9E1D7;">
                        <div>
                            <h2 style="color: #302921; font-size: 15px; margin: 0;">
                                <?= htmlspecialchars($trans['business_overview'] ?? 'Ringkasan bisnis'); ?>
                            </h2>
                            <p style="margin: 4px 0 0; color: #81776D; font-size: 11px;">
                                <?= htmlspecialchars($trans['dashboard_preview'] ?? 'Contoh tampilan dashboard'); ?>
                            </p>
                        </div>

                        <span style="padding: 6px 10px; background: #FAF7F2; border-radius: 7px; color: #76553D; font-size: 11px;">
                            <?= htmlspecialchars($trans['today'] ?? 'Hari ini'); ?>
                        </span>
                    </div>

                    <!-- Grid ringkasan statistik dummy -->
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; padding: 16px 0;">

                        <div style="padding: 12px; border: 1px solid #E9E1D7; border-radius: 11px;">
                            <p style="color: #81776D; font-size: 11px; margin: 0;"><?= htmlspecialchars($trans['total_products'] ?? 'Total Produk'); ?></p>
                            <h3 style="margin: 6px 0 0; color: #302921; font-size: 22px;">128</h3>
                            <p style="color: #6F8966; font-size: 10px; margin: 4px 0 0;"><?= htmlspecialchars($trans['registered_products'] ?? 'Produk terdaftar'); ?></p>
                        </div>

                        <div style="padding: 12px; border: 1px solid #E9E1D7; border-radius: 11px;">
                            <p style="color: #81776D; font-size: 11px; margin: 0;"><?= htmlspecialchars($trans['stock_in'] ?? 'Stok Masuk'); ?></p>
                            <h3 style="margin: 6px 0 0; color: #302921; font-size: 22px;">+24</h3>
                            <p style="color: #6F8966; font-size: 10px; margin: 4px 0 0;"><?= htmlspecialchars($trans['units_received'] ?? 'Unit masuk'); ?></p>
                        </div>

                        <div style="padding: 12px; border: 1px solid #E9E1D7; border-radius: 11px;">
                            <p style="color: #81776D; font-size: 11px; margin: 0;"><?= htmlspecialchars($trans['stock_out'] ?? 'Stok Keluar'); ?></p>
                            <h3 style="margin: 6px 0 0; color: #302921; font-size: 22px;">-12</h3>
                            <p style="color: #6F8966; font-size: 10px; margin: 4px 0 0;"><?= htmlspecialchars($trans['units_sold'] ?? 'Unit terjual'); ?></p>
                        </div>

                        <div style="padding: 12px; border: 1px solid #E9E1D7; border-radius: 11px;">
                            <p style="color: #81776D; font-size: 11px; margin: 0;"><?= htmlspecialchars($trans['revenue'] ?? 'Omset'); ?></p>
                            <h3 style="margin: 6px 0 0; color: #302921; font-size: 20px;">Rp 2,4 jt</h3>
                            <p style="color: #6F8966; font-size: 10px; margin: 4px 0 0;"><?= htmlspecialchars($trans['todays_sales'] ?? 'Penjualan hari ini'); ?></p>
                        </div>

                    </div>

                    <!-- Daftar sampel produk pada pratinjau -->
                    <h3 style="margin: 10px 0 8px; color: #302921; font-size: 13px;">
                        <?= htmlspecialchars($trans['sample_products'] ?? 'Contoh produk'); ?>
                    </h3>

                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; border-top: 1px solid #F1EBE4;">
                        <div style="width: 36px; height: 36px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #F3E8DC; color: #76553D; border-radius: 9px; font-weight: 700;">
                            K
                        </div>

                        <div style="flex: 1;">
                            <p style="color: #302921; font-size: 12px; font-weight: 600; margin: 0;">Kemeja Basic</p>
                            <p style="color: #81776D; font-size: 10px; margin: 2px 0 0;"><?= htmlspecialchars($trans['clothing'] ?? 'Pakaian'); ?></p>
                        </div>

                        <strong style="color: #302921; font-size: 12px;">42 unit</strong>
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; border-top: 1px solid #F1EBE4;">
                        <div style="width: 36px; height: 36px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #E8EDE3; color: #657B5F; border-radius: 9px; font-weight: 700;">
                            T
                        </div>

                        <div style="flex: 1;">
                            <p style="color: #302921; font-size: 12px; font-weight: 600; margin: 0;">Tote Bag Canvas</p>
                            <p style="color: #81776D; font-size: 10px; margin: 2px 0 0;"><?= htmlspecialchars($trans['accessories'] ?? 'Aksesori'); ?></p>
                        </div>

                        <strong style="color: #302921; font-size: 12px;">28 unit</strong>
                    </div>

                </div>
            </div>

        </div>
    </section>

    <!-- Bagian keunggulan dan fitur utama aplikasi -->
    <section id="features" style="padding: 60px 24px; background: #FAF7F2;">

        <div style="max-width: 1160px; margin: 0 auto;">

            <!-- Header judul bagian fitur -->
            <div style="max-width: 600px; margin: 0 auto 38px; text-align: center;">

                <p style="color: #A77D5B; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin: 0;">
                    <?= htmlspecialchars($trans['features_tag'] ?? 'FITUR STOCK'); ?>
                </p>

                <h2 style="margin-top: 10px; color: #302921; font-size: 28px; line-height: 1.3;">
                    <?= htmlspecialchars($trans['features_title'] ?? 'Kelola stok tanpa ribet'); ?>
                </h2>

                <p style="margin-top: 10px; color: #81776D; font-size: 14px; line-height: 1.7;">
                    <?= htmlspecialchars($trans['features_desc'] ?? 'Semua yang kamu butuhkan untuk mengelola persediaan dan memantau bisnis dalam satu tempat.'); ?>
                </p>

            </div>

            <!-- Grid daftar kartu fitur unggulan -->
            <div class="features-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px;">
                
                <!-- Kartu Fitur 1: Manajemen Produk -->
                <div class="feature-card" style="padding: 22px; background: #FFFFFF; border: 1px solid #E9E1D7; border-radius: 14px;">                       
                    <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #F3E8DC; color: #76553D; border-radius: 11px;">
                        <i data-lucide="package" style="width: 23px; height: 23px;"></i>
                    </div>

                    <h3 style="margin-top: 16px; color: #302921; font-size: 16px;">
                        <?= htmlspecialchars($trans['feat_product_mgmt'] ?? 'Manajemen Produk'); ?>
                    </h3>

                    <p style="margin-top: 8px; color: #81776D; font-size: 13px; line-height: 1.7;">
                        <?= htmlspecialchars($trans['feat_product_desc'] ?? 'Kelola nama produk, kategori, harga, dan stok awal dengan mudah.'); ?>
                    </p>
                </div>

                <!-- Kartu Fitur 2: Pergerakan Stok -->
                <div class="feature-card" style="padding: 22px; background: #FFFFFF; border: 1px solid #E9E1D7; border-radius: 14px;">
                    <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #F3E8DC; color: #76553D; border-radius: 11px;">
                        <i data-lucide="arrow-down-up" style="width: 23px; height: 23px;"></i>
                    </div>

                    <h3 style="margin-top: 16px; color: #302921; font-size: 16px;">
                        <?= htmlspecialchars($trans['feat_stock_move'] ?? 'Pergerakan Stok'); ?>
                    </h3>

                    <p style="margin-top: 8px; color: #81776D; font-size: 13px; line-height: 1.7;">
                        <?= htmlspecialchars($trans['feat_stock_desc'] ?? 'Catat stok masuk, barang terjual, dan penyesuaian stok secara terpisah.'); ?>
                    </p>
                </div>

                <!-- Kartu Fitur 3: Riwayat Aktivitas -->
                <div class="feature-card" style="padding: 22px; background: #FFFFFF; border: 1px solid #E9E1D7; border-radius: 14px;">        
                    <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #F3E8DC; color: #76553D; border-radius: 11px;">
                        <i data-lucide="history" style="width: 23px; height: 23px;"></i>
                    </div>

                    <h3 style="margin-top: 16px; color: #302921; font-size: 16px;">
                        <?= htmlspecialchars($trans['feat_history'] ?? 'Riwayat Aktivitas'); ?>
                    </h3>

                    <p style="margin-top: 8px; color: #81776D; font-size: 13px; line-height: 1.7;">
                        <?= htmlspecialchars($trans['feat_history_desc'] ?? 'Periksa riwayat stok awal, barang masuk, penjualan, dan audit stok.'); ?>
                    </p>
                </div>

                <!-- Kartu Fitur 4: Statistik Bisnis -->
                <div class="feature-card" style="padding: 22px; background: #FFFFFF; border: 1px solid #E9E1D7; border-radius: 14px;">
                    <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #F3E8DC; color: #76553D; border-radius: 11px;">
                        <i data-lucide="chart-no-axes-combined" style="width: 23px; height: 23px;"></i>
                    </div>

                    <h3 style="margin-top: 16px; color: #302921; font-size: 16px;">
                        <?= htmlspecialchars($trans['feat_stats'] ?? 'Statistik Bisnis'); ?>
                    </h3>

                    <p style="margin-top: 8px; color: #81776D; font-size: 13px; line-height: 1.7;">
                        <?= htmlspecialchars($trans['feat_stats_desc'] ?? 'Pantau jumlah stok masuk, stok keluar, omset, dan keuntungan.'); ?>
                    </p>
                </div>

                <!-- Kartu Fitur 5: Laporan dan PDF/CSV -->
                <div class="feature-card" style="padding: 22px; background: #FFFFFF; border: 1px solid #E9E1D7; border-radius: 14px;">
                    <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #F3E8DC; color: #76553D; border-radius: 11px;">
                        <i data-lucide="file-spreadsheet" style="width: 23px; height: 23px;"></i>
                    </div>

                    <h3 style="margin-top: 16px; color: #302921; font-size: 16px;">
                        <?= htmlspecialchars($trans['feat_reports'] ?? 'Laporan & Analitik'); ?>
                    </h3>

                    <p style="margin-top: 8px; color: #81776D; font-size: 13px; line-height: 1.7;">
                        <?= htmlspecialchars($trans['feat_reports_desc'] ?? 'Pantau perkembangan persediaan, penjualan, dan keuntungan melalui laporan yang terstruktur.'); ?>
                    </p>
                </div>

                <!-- Kartu Fitur 6: Multi-Toko -->
                <div class="feature-card" style="padding: 22px; background: #FFFFFF; border: 1px solid #E9E1D7; border-radius: 14px;">
                    <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #F3E8DC; color: #76553D; border-radius: 11px;">
                        <i data-lucide="store" style="width: 23px; height: 23px;"></i>
                    </div>

                    <h3 style="margin-top: 16px; color: #302921; font-size: 16px;">
                        <?= htmlspecialchars($trans['feat_multistore'] ?? 'Manajemen Multi-Toko'); ?>
                    </h3>

                    <p style="margin-top: 8px; color: #81776D; font-size: 13px; line-height: 1.7;">
                        <?= htmlspecialchars($trans['feat_multistore_desc'] ?? 'Kelola setiap toko dengan persediaan dan laporan yang terorganisasi dalam satu akun.'); ?>
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- Bagian Footer (Hak Cipta & Informasi) -->
    <footer style="background: #FFFFFF; border-top: 1px solid #E9E1D7; padding: 24px 16px;">
        <div class="footer-container" style="max-width: 1160px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">

            <!-- Logo brand pada footer -->
            <a href="index.php"
            style="color: #302921; text-decoration: none; font-size: 22px; font-weight: 750;">
                Stock<span style="color: #A77D5B;">.</span>
            </a>

            <!-- Informasi hak cipta -->
            <p style="color: #A0988E; font-size: 11px; margin: 0;">
                © 2026 Stock. All rights reserved.
            </p>

        </div>
    </footer>

</body>
</html>