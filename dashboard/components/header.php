<!-- Header Navigasi Utama Bisnis Aktif -->
<header class="top-header">
    <div>
        <span style="font-size:13px; color:#948578;"><?= htmlspecialchars(__('active_business'), ENT_QUOTES, 'UTF-8') ?></span>
        <h3 style="margin:5px 0 0; font-size:17px;"><?= htmlspecialchars($business['business_name'], ENT_QUOTES, 'UTF-8') ?></h3>
    </div>

    <div style="display:flex; align-items:center; gap:20px;">
        <!-- Dropdown Pilihan Bahasa -->
        <div class="dropdown-wrap" style="margin-right:20px;">
            <button type="button" onclick="toggleLanguageMenu()" class="lang-trigger" style="color:#49382D;">
                <i data-lucide="globe" style="width:20px; height:20px; color:#81776D;"></i>
                <span style="font-size:14px;"><?= strtoupper($lang) ?></span>
                <i data-lucide="chevron-down" style="width:16px; height:16px; color:#81776D;"></i>
            </button>

            <div id="language-menu" class="dropdown-menu">
                <?php foreach (['id' => 'Indonesia', 'en' => 'English'] as $code => $label): ?>
                    <form action="../set-language.php" method="POST" style="margin:0;">
                        <input type="hidden" name="language" value="<?= $code ?>">
                        <input type="hidden" name="redirect" value="<?= $_SERVER['PHP_SELF'] ?>?business_id=<?= (int) $business_id ?>">
                        <button type="submit" class="dropdown-option <?= $lang === $code ? 'active' : '' ?>">
                            <?= $label ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Informasi Mata Uang Bisnis -->
        <div style="text-align:right;">
            <span style="font-size:13px; color:#81776D;"><?= htmlspecialchars(__('currency'), ENT_QUOTES, 'UTF-8') ?></span>
            <p style="margin:5px 0 0; font-weight:bold;"><?= htmlspecialchars($business['currency'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
</header>
