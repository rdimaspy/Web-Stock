<!-- Header navigasi utama bisnis aktif -->
<header style="position:sticky; top:0; z-index:100; min-height:75px; background:white; border-bottom:1px solid #E9E1D7; display:flex; align-items:center; justify-content:space-between; gap:20px; padding:14px 32px;">

    <!-- Informasi Nama Bisnis Aktif -->
    <div>
        <!-- Label status bisnis aktif berdasarkan bahasa -->
        <span style="font-size:13px; color:#948578;">
            <?= htmlspecialchars(__('active_business'), ENT_QUOTES, 'UTF-8') ?>
        </span>

        <!-- Nama bisnis yang sedang dibuka pengguna -->
        <h3 style="margin:5px 0 0; font-size:17px;">
            <?= htmlspecialchars($business['business_name'], ENT_QUOTES, 'UTF-8') ?>
        </h3>
    </div>

    <!-- Area Aksi Header (Opsi Bahasa & Informasi Mata Uang) -->
    <div style="display:flex; align-items:center; gap:20px;">
        
        <!-- Dropdown Pilihan Bahasa -->
        <div style="position:relative; display:inline-block; margin-right:20px;">

            <!-- Tombol dropdown bahasa -->
            <button type="button" onclick="toggleLanguageMenu()" style="display:flex; align-items:center; gap:8px; border:none; background:white; padding:8px; border-radius:8px; cursor:pointer; color:#49382D;">
                <i data-lucide="globe" style="width:20px; height:20px; color:#81776D;"></i>

                <!-- Bahasa aktif yang sedang digunakan (ID / EN) -->
                <span style="font-size:14px;">
                    <?= strtoupper($lang) ?>
                </span>

                <i data-lucide="chevron-down" style="width:16px; height:16px; color:#81776D;"></i>
            </button>

            <!-- Container menu pilihan bahasa -->
            <div id="language-menu"
                style="display:none; position:absolute; top:42px; right:0; width:145px; background:white; border:1px solid #E9E1D7; border-radius:10px; padding:6px; box-shadow:0 5px 18px rgba(73,56,45,0.10); z-index:100;">

                <!-- Opsi bahasa yang tersedia (Indonesia dan English) -->
                <?php foreach (['id' => 'Indonesia', 'en' => 'English'] as $code => $label): ?>

                    <form action="../set-language.php" method="POST" style="margin:0;">
                        <!-- Parameter kode bahasa yang dipilih -->
                        <input type="hidden" name="language" value="<?= $code ?>">

                        <!-- Menyimpan lokasi URL halaman saat ini agar pengguna kembali ke halaman yang sama setelah ganti bahasa -->
                        <input type="hidden" name="redirect" value="<?= $_SERVER['PHP_SELF'] ?>?business_id=<?= (int)$business_id ?>">
                        
                        <!-- Tombol submit opsi bahasa -->
                        <button type="submit"
                            style="width:100%; padding:10px; text-align:left; border:0; border-radius:6px; background:<?= $lang === $code ? '#F5EFE8' : 'white' ?>; color:#49382D; cursor:pointer;">
                            <?= $label ?>
                        </button>

                    </form>

                <?php endforeach; ?>

            </div>

        </div>

        <!-- Informasi Mata Uang Utama Bisnis -->
        <div style="text-align:right;">
            <!-- Label mata uang berdasarkan bahasa -->
            <span style="font-size:13px; color:#81776D;">
                <?= htmlspecialchars(__('currency'), ENT_QUOTES, 'UTF-8') ?>
            </span>

            <!-- Simbol/Kode mata uang bisnis aktif  -->
            <p style="margin:5px 0 0; font-weight:bold;">
                <?= htmlspecialchars($business['currency'], ENT_QUOTES, 'UTF-8') ?>
            </p>

        </div>
        
    </div>

</header>

<script>
// Fungsi JavaScript untuk menampilkan atau menyembunyikan menu dropdown bahasa
function toggleLanguageMenu() {
    const menu = document.getElementById('language-menu');

    menu.style.display = menu.style.display === 'block'
        ? 'none'
        : 'block';
}
</script>