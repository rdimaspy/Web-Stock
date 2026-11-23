<?php
// Daftar item menu navigasi utama dashboard
$menus = [
    ['index.php', 'layout-dashboard', 'dashboard'],
    ['products.php', 'package', 'products'],
    ['stock-in.php', 'package-plus', 'stock_in'],
    ['stock-out.php', 'package-minus', 'stock_out'],
    ['stock-audit.php', 'clipboard-check', 'stock_audit'],
    ['history.php', 'history', 'history'],
];
$isReportActive = in_array($currentPage, ['reports.php', 'reports-analytics.php'], true);
?>
<!-- Komponen Sidebar Navigasi Utama -->
<aside class="sidebar">
    <div style="margin-bottom:20px;">
        <?php renderLogo(); ?>
    </div>

    <p class="sidebar-label"><?= htmlspecialchars(__('menu_main'), ENT_QUOTES, 'UTF-8') ?></p>

    <?php foreach ($menus as [$page, $icon, $key]): ?>
        <a href="<?= $page ?>?business_id=<?= (int) $business_id ?>" class="sidebar-link <?= $currentPage === $page ? 'active' : '' ?>">
            <i data-lucide="<?= $icon ?>" style="width:19px; height:19px;"></i>
            <span><?= htmlspecialchars(__($key), ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    <?php endforeach; ?>

    <!-- Menu Laporan dengan Submenu Dropdown -->
    <div style="margin-bottom:7px;">
        <a href="#" onclick="toggleSubmenu(event, 'reportsSubmenu')" class="sidebar-link <?= $isReportActive ? 'active' : '' ?>" style="margin-bottom:0;">
            <div style="display:flex; align-items:center; gap:12px; flex:1;">
                <i data-lucide="chart-no-axes-combined" style="width:19px; height:19px;"></i>
                <span><?= htmlspecialchars(__('reports'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <i data-lucide="chevron-down" style="width:16px; height:16px;"></i>
        </a>

        <div id="reportsSubmenu" class="sidebar-submenu" style="display:<?= $isReportActive ? 'block' : 'none' ?>;">
            <a href="reports.php?business_id=<?= (int) $business_id ?>" class="sidebar-sublink <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                <i data-lucide="file-text" style="width:16px; height:16px;"></i>
                <span><?= htmlspecialchars(__('summary_report'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <a href="reports-analytics.php?business_id=<?= (int) $business_id ?>" class="sidebar-sublink <?= $currentPage === 'reports-analytics.php' ? 'active' : '' ?>">
                <i data-lucide="chart-column" style="width:16px; height:16px;"></i>
                <span><?= htmlspecialchars(__('analytics_chart'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        </div>
    </div>

    <!-- Menu Pengaturan -->
    <a href="settings.php?business_id=<?= (int) $business_id ?>" class="sidebar-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
        <i data-lucide="settings" style="width:19px; height:19px;"></i>
        <span><?= htmlspecialchars(__('settings'), ENT_QUOTES, 'UTF-8') ?></span>
    </a>

    <!-- Kembali ke Daftar Bisnis -->
    <div class="sidebar-footer">
        <a href="../businesses.php" style="display:flex; align-items:center; gap:12px; padding:12px; color:#76553D;">
            <i data-lucide="arrow-left" style="width:19px; height:19px;"></i>
            <?= htmlspecialchars(__('businesses'), ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>
</aside>
