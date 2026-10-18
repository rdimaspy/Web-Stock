<!-- Komponen Sidebar Navigasi Utama -->
<aside style="position:fixed; top:0; left:0; bottom:0; width:230px; overflow-y:auto; box-sizing:border-box; background:white; border-right:1px solid #E9E1D7; padding:25px 18px; z-index:200;">
    
    <!-- Logo dan Judul Aplikasi -->
    <h2 style="margin:0 0 35px; color:#76553D; font-size:27px;">
        Stock
        <span style="color:#B99A7D;">.</span>
    </h2>

    <!-- Label Kategori Menu Utama -->
    <p style="font-size:11px; color:#A3978A; font-weight:bold; letter-spacing:1px;">
        <?= htmlspecialchars(__('menu_main'), ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php
    // Daftar item menu utama dashboard
    $menus = [
        ['index.php', 'layout-dashboard', 'dashboard'],
        ['products.php', 'package', 'products'],
        ['stock-in.php', 'package-plus', 'stock_in'],
        ['stock-out.php', 'package-minus', 'stock_out'],
        ['stock-audit.php', 'clipboard-check', 'stock_audit'],
        ['history.php', 'history', 'history'],
    ];

    // Looping untuk merender daftar menu utama
    foreach ($menus as [$page, $icon, $key]):
        // Penanda status menu aktif berdasarkan halaman yang sedang dibuka
        $active = $currentPage === $page;
    ?>
        <!-- Item Link Navigasi Menu Utama -->
        <a href="<?= $page ?>?business_id=<?= (int)$business_id ?>"
        style="display:flex; align-items:center; gap:12px; padding:12px; margin-bottom:7px; border-radius:9px; background:<?= $active ? '#76553D' : 'white' ?>; color:<?= $active ? 'white' : '#6F6257' ?>; text-decoration:none; font-weight:bold;">
            <i data-lucide="<?= $icon ?>" style="width:19px;height:19px;"></i>
            <span>
                <?= htmlspecialchars(__($key), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </a>
    <?php endforeach; ?>

    <!-- Menu Laporan khusus dengan fitur Dropdown Submenu -->
    <?php 
    // Memeriksa apakah halaman saat ini berada dalam grup laporan
    $isReportActive = in_array($currentPage, ['reports.php', 'reports-analytics.php'], true); 
    ?>
    <div style="margin-bottom:7px;">
        <!-- Tombol pemicu buka/tutup submenu Laporan -->
        <a href="#" onclick="toggleSubmenu(event, 'reportsSubmenu')"
        style="display:flex; align-items:center; justify-space-between; gap:12px; padding:12px; border-radius:9px; background:<?= $isReportActive ? '#76553D' : 'white' ?>; color:<?= $isReportActive ? 'white' : '#6F6257' ?>; text-decoration:none; font-weight:bold;">
            <div style="display:flex; align-items:center; gap:12px; flex:1;">
                <i data-lucide="chart-no-axes-combined" style="width:19px;height:19px;"></i>
                <span><?= htmlspecialchars(__('reports'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <i data-lucide="chevron-down" style="width:16px;height:16px;"></i>
        </a>

        <!-- Container Submenu Laporan -->
        <div id="reportsSubmenu" style="display:<?= $isReportActive ? 'block' : 'none' ?>; padding-left:15px; margin-top:5px;">
            <!-- Submenu 1: Laporan Ringkasan (reports.php) -->
            <a href="reports.php?business_id=<?= (int)$business_id ?>"
            style="display:flex; align-items:center; gap:10px; padding:10px 12px; margin-bottom:4px; border-radius:7px; background:<?= $currentPage === 'reports.php' ? '#F5EFE8' : 'transparent' ?>; color:<?= $currentPage === 'reports.php' ? '#76553D' : '#81776D' ?>; font-weight:<?= $currentPage === 'reports.php' ? 'bold' : 'normal' ?>; text-decoration:none; font-size:13px;">
                <i data-lucide="file-text" style="width:16px;height:16px;"></i>
                <span><?= htmlspecialchars(__('summary_report') ?? 'Laporan Ringkasan', ENT_QUOTES, 'UTF-8') ?></span>
            </a>

            <!-- Submenu 2: Grafik & Analitik (reports-analytics.php) -->
            <a href="reports-analytics.php?business_id=<?= (int)$business_id ?>"
            style="display:flex; align-items:center; gap:10px; padding:10px 12px; margin-bottom:4px; border-radius:7px; background:<?= $currentPage === 'reports-analytics.php' ? '#F5EFE8' : 'transparent' ?>; color:<?= $currentPage === 'reports-analytics.php' ? '#76553D' : '#81776D' ?>; font-weight:<?= $currentPage === 'reports-analytics.php' ? 'bold' : 'normal' ?>; text-decoration:none; font-size:13px;">
                <i data-lucide="chart-column" style="width:16px;height:16px;"></i>
                <span><?= htmlspecialchars(__('analytics_chart') ?? 'Grafik & Analitik', ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        </div>
    </div>

    <!-- Menu Pengaturan Toko / Bisnis -->
    <?php $activeSettings = $currentPage === 'settings.php'; ?>
    <a href="settings.php?business_id=<?= (int)$business_id ?>"
    style="display:flex; align-items:center; gap:12px; padding:12px; margin-bottom:7px; border-radius:9px; background:<?= $activeSettings ? '#76553D' : 'white' ?>; color:<?= $activeSettings ? 'white' : '#6F6257' ?>; text-decoration:none; font-weight:bold;">
        <i data-lucide="settings" style="width:19px;height:19px;"></i>
        <span><?= htmlspecialchars(__('settings'), ENT_QUOTES, 'UTF-8') ?></span>
    </a>

    <!-- Navigasi kembali ke daftar toko/bisnis pengguna -->
    <div style="margin-top:35px; padding-top:18px; border-top:1px solid #E9E1D7;">
        <a href="../businesses.php" style="display:flex; align-items:center; gap:12px; padding:12px; color:#76553D; text-decoration:none;">
            <i data-lucide="arrow-left" style="width:19px;height:19px;"></i>
            <?= htmlspecialchars(__('businesses'), ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>

</aside>

<!-- Mengontrol buka/tutup tampilan submenu dropdown -->
<script>
    function toggleSubmenu(event, id) {
        event.preventDefault();
        const submenu = document.getElementById(id);
        if (submenu) {
            submenu.style.display = (submenu.style.display === 'none' || submenu.style.display === '') ? 'block' : 'none';
        }
    }
</script>