<?php
// Memulai sesi dan memeriksa autentikasi pengguna
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/currency.php';
require_once __DIR__ . '/includes/language.php';

// Ambil daftar bisnis milik pengguna aktif
$user_id = (int) $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM businesses WHERE user_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business | StockIn</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/toast.js?v=3" defer></script>
    <script src="assets/js/app.js?v=3" defer></script>
</head>
<body style="color:#302921;">

    <div class="main-container">
        <!-- Header Daftar Bisnis -->
        <div class="top-bar-flex">
            <div>
                <h2 style="margin:0;">
                    <?= htmlspecialchars(__('hello'), ENT_QUOTES, 'UTF-8') ?>
                    <?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <p style="color:#81776D; margin:8px 0 0;">
                    <?= htmlspecialchars(__('select_business_desc'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <div class="header-actions">
                <!-- Pilihan Bahasa -->
                <div class="dropdown-wrap">
                    <button type="button" class="lang-trigger" onclick="toggleMenu('business-language-menu')">
                        <i data-lucide="globe" style="width:21px; height:21px;"></i>
                        <i data-lucide="chevron-down" style="width:15px; height:15px;"></i>
                    </button>

                    <div id="business-language-menu" class="dropdown-menu">
                        <?php foreach (['id' => 'Indonesia', 'en' => 'English'] as $code => $label): ?>
                            <form action="set-language.php" method="POST" style="margin:0;">
                                <input type="hidden" name="language" value="<?= $code ?>">
                                <input type="hidden" name="redirect" value="businesses.php">
                                <button type="submit" class="dropdown-option <?= $lang === $code ? 'active' : '' ?>">
                                    <?= $label ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tombol Buat Bisnis -->
                <a href="create-business.php" class="btn btn-primary">
                    <?= htmlspecialchars(__('create_business_btn'), ENT_QUOTES, 'UTF-8') ?>
                </a>

                <!-- Menu Pengaturan Akun & Logout -->
                <div class="dropdown-wrap">
                    <button type="button" class="settings-icon-btn" onclick="toggleMenu('user-settings-menu')">
                        <i data-lucide="settings" style="width:20px; height:20px;"></i>
                    </button>

                    <div id="user-settings-menu" class="dropdown-menu wide">
                        <a href="logout.php" class="dropdown-option" style="display:flex; align-items:center; gap:8px; font-weight:600;">
                            <i data-lucide="log-out" style="width:16px; height:16px; color:#76553D;"></i>
                            <?= htmlspecialchars(__('logout'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <button type="button" onclick="deleteUserAccount()" class="btn btn-danger" style="width:100%; justify-content:flex-start; padding:10px; font-size:13px;">
                            <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                            <?= htmlspecialchars(__('delete_account'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Kartu Bisnis -->
        <div class="business-wrapper">
            <h3 style="margin:0 0 20px;"><?= htmlspecialchars(__('businesses'), ENT_QUOTES, 'UTF-8') ?></h3>

            <div class="business-grid">
                <?php if (mysqli_num_rows($query) === 0): ?>
                    <div style="grid-column:1 / -1; text-align:center; padding:40px 20px; color:#81776D;">
                        <i data-lucide="store" style="width:40px; height:40px; margin-bottom:12px;"></i>
                        <p style="margin:0; font-size:15px; line-height:1.6;">
                            <?= htmlspecialchars(__('no_businesses'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php while ($business = mysqli_fetch_assoc($query)):
                        $photoFile = __DIR__ . '/uploads/businesses/' . $business['id'] . '/' . ($business['photo'] ?? '');
                        $hasPhoto = !empty($business['photo']) && file_exists($photoFile);
                    ?>
                        <a href="dashboard/index.php?business_id=<?= (int) $business['id'] ?>" class="business-card">
                            <?php if ($hasPhoto): ?>
                                <img src="uploads/businesses/<?= (int) $business['id'] ?>/<?= htmlspecialchars($business['photo'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Foto bisnis" class="business-avatar">
                            <?php else: ?>
                                <div class="business-avatar">
                                    <?= htmlspecialchars(strtoupper(substr($business['business_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <div style="flex:1; min-width:0;">
                                <div style="font-size:16px; font-weight:700; overflow-wrap:anywhere;">
                                    <?= htmlspecialchars($business['business_name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div style="margin-top:5px; color:#81776D; font-size:13px;">
                                    <?= htmlspecialchars($business['currency'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <?php if (!empty(trim($business['notes'] ?? ''))): ?>
                                    <div title="<?= htmlspecialchars($business['notes'], ENT_QUOTES, 'UTF-8') ?>"
                                        style="margin-top:6px; color:#81776D; font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= htmlspecialchars(mb_strimwidth(trim($business['notes']), 0, 20), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <i data-lucide="chevron-right" class="arrow-icon"></i>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        window.APP_CONFIG = {
            confirmDeleteAccount: <?= json_encode(__('confirm_delete_account')) ?>,
            deleteAccountBtn: <?= json_encode(__('delete_account')) ?>,
            cancelBtn: <?= json_encode(__('btn_cancel')) ?>,
            connectionError: <?= json_encode(__('connection_error')) ?>
        };
    </script>
</body>
</html>
