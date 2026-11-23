<?php
// Memulai sesi dan memeriksa autentikasi pengguna
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/includes/language.php';

$currencies = [
    'IDR' => 'IDR - Indonesian Rupiah',
    'USD' => 'USD - US Dollar',
    'EUR' => 'EUR - Euro',
    'JPY' => 'JPY - Japanese Yen',
    'GBP' => 'GBP - British Pound',
    'CNY' => 'CNY - Chinese Yuan',
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Business | StockIn</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body style="color:#302921;">

    <div class="center-page-wrapper">
        <div class="auth-card wide">
            <div class="auth-header">
                <h2 style="margin:0; font-size:26px;"><?= htmlspecialchars(__('create_business_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="auth-subtitle"><?= htmlspecialchars(__('create_business_desc'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <form id="createBusinessForm" enctype="multipart/form-data">
                <!-- Foto Toko (Opsional) -->
                <label class="form-label"><?= htmlspecialchars(__('store_photo_optional'), ENT_QUOTES, 'UTF-8') ?></label>
                <label for="business-photo" id="upload-box" class="upload-box-sm">
                    <img id="businessPhotoPreview" src="" class="img-cover" style="display:none;" alt="Preview">
                    <i data-lucide="image-plus" id="upload-icon" style="width:28px; height:28px;"></i>
                    <input id="business-photo" type="file" name="photo" accept="image/*" style="display:none;">
                </label>

                <!-- Nama Toko -->
                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars(__('store_name'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="business_name" maxlength="40" class="form-control" required
                        placeholder="<?= htmlspecialchars(__('store_name_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Mata Uang -->
                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars(__('currency'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="currency" class="form-control">
                        <?php foreach ($currencies as $code => $label): ?>
                            <option value="<?= $code ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Catatan -->
                <div class="form-group" style="margin-bottom:25px;">
                    <label class="form-label"><?= htmlspecialchars(__('notes_optional'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="notes" maxlength="30" class="form-control"
                        placeholder="<?= htmlspecialchars(__('notes_business_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <?= htmlspecialchars(__('create_business_title'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>

            <p style="text-align:center; margin-top:22px; font-size:13px;">
                <a href="businesses.php" style="color:#76553D;"><?= htmlspecialchars(__('back_to_business_list'), ENT_QUOTES, 'UTF-8') ?></a>
            </p>
        </div>
    </div>

    <script>
        window.APP_CONFIG = { connectionError: <?= json_encode(__('connection_error')) ?> };
    </script>
    <script src="assets/js/toast.js?v=3"></script>
    <script src="assets/js/app.js?v=3"></script>
</body>
</html>
