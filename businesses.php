<?php

session_start();

$lang = $_SESSION['language'] ?? 'id';
if (!in_array($lang, ['id', 'en'], true)) {
    $lang = 'id';
}

// cek apakah sudah login
if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit;
}

// connect database
include "config/database.php";

// Formatter mata uang
require_once __DIR__ . '/includes/currency.php';

// Sistem bahasa
require_once __DIR__ . '/includes/language.php';

// ambil bisnis milik user
$user_id = $_SESSION['user_id'];

$query = mysqli_query($conn, "SELECT * FROM businesses WHERE user_id='$user_id'");

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business | Stock</title>
</head>

<body style="margin:0; background:#FAF7F2; font-family:Arial, Helvetica, sans-serif; color:#302921;">

    <div style="max-width:1100px; margin:auto; padding:30px;">

        <!-- HEADER -->
        <div style="display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; margin-bottom:30px;">
            <div>
                <h2 style="margin:0;">
                    <?= $lang === 'en' ? 'Hello,' : 'Halo,' ?>
                    <?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <p style="color:#81776D; margin-top:8px; margin-bottom:0;">
                    <?= $lang === 'en'
                        ? 'Select a business to continue'
                        : 'Pilih bisnis untuk melanjutkan' ?>
                </p>
            </div>

            <div style="display:flex; align-items:center; gap:12px;">
                <!-- PILIHAN BAHASA -->
                <div style="position:relative; display:inline-block;">
                    <button
                        type="button"
                        onclick="const m=document.getElementById('business-language-menu');m.style.display=m.style.display==='block'?'none':'block';"
                        style="display:flex; align-items:center; gap:8px; padding:8px; border:0; background:transparent; color:#81776D; cursor:pointer;">
                        <i data-lucide="globe" style="width:21px; height:21px;"></i>
                        <i data-lucide="chevron-down" style="width:15px; height:15px;"></i>
                    </button>

                    <div
                        id="business-language-menu"
                        style="display:none; position:absolute; top:42px; right:0; width:145px; background:white; border:1px solid #E9E1D7; border-radius:14px; padding:8px; box-shadow:0 5px 18px rgba(73,56,45,0.10); z-index:100;">

                        <form action="set-language.php" method="POST" style="margin:0;">
                            <input type="hidden" name="language" value="id">
                            <input type="hidden" name="redirect" value="businesses.php">

                            <button
                                type="submit"
                                data-language-option="id"
                                style="width:100%; padding:12px; text-align:left; border:0; border-radius:9px; background:<?= $lang === 'id' ? '#F5EFE8' : 'white' ?>; color:#49382D; cursor:pointer;">
                                Indonesia
                            </button>
                        </form>

                        <form action="set-language.php" method="POST" style="margin:0;">
                            <input type="hidden" name="language" value="en">
                            <input type="hidden" name="redirect" value="businesses.php">

                            <button
                                type="submit"
                                data-language-option="en"
                                style="width:100%; padding:12px; text-align:left; border:0; border-radius:9px; background:<?= $lang === 'en' ? '#F5EFE8' : 'white' ?>; color:#49382D; cursor:pointer;">
                                English
                            </button>
                        </form>

                    </div>
                </div>

                    <!-- BUAT BISNIS -->
                    <a href="create-business.php"
                    style="background:#76553D; color:white; padding:12px 20px; border-radius:10px; text-decoration:none; font-size:14px; font-weight:600; white-space:nowrap;">
                        <?= $lang === 'en' ? '+ Create Business' : '+ Buat Bisnis' ?>
                    </a>

                    <!-- LOGOUT -->
                    <a href="logout.php"
                    style="display:flex; align-items:center; justify-content:center; gap:8px; padding:12px 14px; border:1px solid #E9E1D7; border-radius:10px; background:white; color:#76553D; text-decoration:none; font-size:14px; font-weight:600; white-space:nowrap;">
                        <i data-lucide="log-out" style="width:17px; height:17px;"></i>
                        <?= $lang === 'en' ? 'Logout' : 'Keluar' ?>
                    </a>

                </div>
            </div>

        <!-- BUSINESS LIST -->
        <div style="background:white; border:1px solid #E9E1D7; border-radius:16px; padding:24px;">
            <h3 style="margin:0 0 20px;">
                <?= __('businesses') ?>
            </h3>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:12px;">
                <?php if (mysqli_num_rows($query) === 0): ?>
                    <div style="grid-column:1 / -1; text-align:center; padding:40px 20px; color:#81776D;">
                        <i data-lucide="store" style="width:40px; height:40px; margin-bottom:12px;"></i>
                        <p style="margin:0; font-size:15px; line-height:1.6;">
                            <?= $lang === 'en'
                                ? "No businesses yet. Let's create your first business!"
                                : 'Belum ada bisnis. Yuk, buat bisnis pertamamu!' ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php while ($business = mysqli_fetch_assoc($query)): ?>
                    <a href="dashboard/index.php?business_id=<?= (int) $business['id'] ?>"
                    style="display:flex; align-items:center; gap:14px; min-width:0; padding:14px; background:#FAF7F2; border:1.5px solid #E9E1D7; border-radius:12px; text-decoration:none; color:#302921; transition:border-color 0.2s;">

                        <!-- FOTO BISNIS -->
                        <?php if (
                            !empty($business['photo']) &&
                            file_exists(__DIR__ . '/uploads/businesses/' . $business['id'] . '/' . $business['photo'])
                        ): ?>
                            <img src="uploads/businesses/<?= (int) $business['id'] ?>/<?= htmlspecialchars($business['photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto bisnis"
                                style="width:56px; height:56px; flex-shrink:0; border-radius:10px; object-fit:cover;">
                        <?php else: ?>
                            <div style="width:56px; height:56px; flex-shrink:0; border-radius:10px; background:#76553D; color:white; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:bold;">
                                <?= htmlspecialchars(strtoupper(substr($business['business_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <!-- INFORMASI BISNIS -->
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:16px; font-weight:700; overflow-wrap:anywhere;">
                                <?= htmlspecialchars($business['business_name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <div style="margin-top:5px; color:#81776D; font-size:13px;">
                                <?= htmlspecialchars($business['currency'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <?php if (!empty(trim($business['notes'] ?? ''))): ?>
                                <div
                                    title="<?= htmlspecialchars($business['notes'], ENT_QUOTES, 'UTF-8') ?>"
                                    style="margin-top:7px; color:#81776D; font-size:12px; line-height:1.5; overflow:hidden; display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:2; overflow-wrap:anywhere;">
                                    <?= htmlspecialchars($business['notes'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <i data-lucide="chevron-right"
                        style="width:18px; height:18px; flex-shrink:0; color:#948578;"></i>
                    </a>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>

</body>

</html>