<?php
/*Sidebar utama yg dipakai semua halaman dashboard.*/
?>

<!-- SIDEBAR NAVIGASI -->
<aside style="position:fixed; top:0; left:0; bottom:0; width:230px; overflow-y:auto; box-sizing:border-box; background:white; border-right:1px solid #E9E1D7; padding:25px 18px; z-index:200;">
    <h2 style="margin:0 0 35px; color:#76553D; font-size:27px;">
        Stock
        <span style="color:#B99A7D;">.</span>
    </h2>

    <p style="font-size:11px; color:#A3978A; font-weight:bold; letter-spacing:1px;">
        <?= htmlspecialchars(__('menu_main'), ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php

    // Daftar menu dashboard
    $menus = [
        ['index.php', 'layout-dashboard', 'dashboard'],
        ['products.php', 'package', 'products'],
        ['stock-in.php', 'package-plus', 'stock_in'],
        ['stock-out.php', 'package-minus', 'stock_out'],
        ['stock-audit.php', 'clipboard-check', 'stock_audit'],
        ['history.php', 'history', 'history'],
        ['reports.php', 'chart-no-axes-combined', 'reports'],
        ['settings.php', 'settings', 'settings']
    ];

    foreach ($menus as [$page, $icon, $key]):
        // Menentukan menu aktif berdasarkan halaman saat ini
        $active = $currentPage === $page;
    ?>

        <a href="<?= $page ?>?business_id=<?= (int)$business_id ?>"
        style="display:flex; align-items:center; gap:12px; padding:12px; margin-bottom:7px; border-radius:9px; background:<?= $active ? '#76553D' : 'white' ?>; color:<?= $active ? 'white' : '#6F6257' ?>; text-decoration:none;">

            <i data-lucide="<?= $icon ?>" style="width:19px;height:19px;"></i>
            <span>
                <?= htmlspecialchars(__($key), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </a>
    <?php endforeach; ?>

    <!-- Kembali ke daftar bisnis -->
    <div style="margin-top:35px; padding-top:18px; border-top:1px solid #E9E1D7;">
        <a href="../businesses.php" style=" display:flex; align-items:center; gap:12px; padding:12px; color:#76553D; text-decoration:none;">
            <i data-lucide="arrow-left" style="width:19px;height:19px;"></i>
            <?= htmlspecialchars(__('businesses'), ENT_QUOTES, 'UTF-8') ?>
        </a>

    </div>

</aside>