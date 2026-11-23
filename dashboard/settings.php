<?php
// Validasi sesi, akses bisnis aktif, bahasa, & mata uang
require_once __DIR__ . '/../includes/auth_business.php';

// Ambil data profil pengguna aktif
$stmtUser = mysqli_prepare($conn, "SELECT name, email FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmtUser, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmtUser);
$userProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUser));

// Daftar field input kata sandi untuk efisiensi render
$passwordFields = [
    ['currentPassword', 'current_password', 'current_password_label', 'current_password_place'],
    ['newPassword', 'new_password', 'new_password_label', 'new_password_place'],
    ['confirmPassword', 'confirm_password', 'confirm_password_label', 'confirm_password_place'],
];

$currentPage = 'settings.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('settings_title'), ENT_QUOTES, 'UTF-8') ?> - StockIn</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/toast.js?v=3" defer></script>
    <script src="../assets/js/app.js?v=3" defer></script>
</head>
<body>

<div class="dashboard-layout">
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <main class="dashboard-main">
        <?php require __DIR__ . '/components/header.php'; ?>

        <section class="page-section max-650">
            <div class="page-header" style="margin-bottom:20px;">
                <h1 style="margin:0; font-size:26px;"><?= htmlspecialchars(__('settings_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p style="color:#81776D; margin-top:6px; font-size:14px;">
                    <?= htmlspecialchars(__('settings_description'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <div class="card">
                <form id="settingsForm" enctype="multipart/form-data">
                    <input type="hidden" name="business_id" value="<?= (int) $business_id ?>">

                    <!-- Foto / Logo Toko -->
                    <div class="form-group">
                        <label class="form-label-lg"><?= htmlspecialchars(__('business_logo_photo'), ENT_QUOTES, 'UTF-8') ?></label>
                        <div style="display:flex; align-items:center; gap:16px;">
                            <label for="businessPhoto" class="upload-box-md">
                                <img id="businessPreview" src="" class="img-cover" style="display:none;" alt="Preview">
                                <i id="businessUploadIcon" data-lucide="camera" style="width:28px; height:28px; color:#76553D;"></i>
                            </label>
                            <div>
                                <input type="file" id="businessPhoto" name="photo" accept="image/*" style="display:none;">
                                <label for="businessPhoto" class="btn btn-soft btn-sm">
                                    <?= htmlspecialchars(__('change_photo'), ENT_QUOTES, 'UTF-8') ?>
                                </label>
                                <p style="margin:6px 0 0; color:#81776D; font-size:12px;">
                                    <?= htmlspecialchars(__('photo_hint'), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Bisnis -->
                    <div class="form-group">
                        <label class="form-label-lg"><?= htmlspecialchars(__('business_name_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="business_name" maxlength="40" class="form-control" required
                            value="<?= htmlspecialchars($business['business_name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label-lg"><?= htmlspecialchars(__('business_notes_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="notes" maxlength="30" class="form-control"
                            value="<?= htmlspecialchars($business['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="<?= htmlspecialchars(__('business_notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <hr class="divider-dashed">

                    <!-- Informasi Akun Pengguna -->
                    <div class="form-group">
                        <label class="form-label-lg"><?= htmlspecialchars(__('account_name_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="name" maxlength="40" class="form-control" required
                            value="<?= htmlspecialchars($userProfile['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label-lg"><?= htmlspecialchars(__('email_address_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="email" class="form-control" disabled
                            value="<?= htmlspecialchars($userProfile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <hr class="divider-dashed">

                    <!-- Bagian Ubah Kata Sandi (Opsional) -->
                    <div style="margin-bottom:16px;">
                        <h4 style="margin:0 0 6px; font-size:15px;"><?= htmlspecialchars(__('change_password_section'), ENT_QUOTES, 'UTF-8') ?></h4>
                        <p style="margin:0 0 14px; font-size:12px; color:#81776D;"><?= htmlspecialchars(__('change_password_desc'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <?php foreach ($passwordFields as [$id, $name, $labelKey, $placeKey]): ?>
                        <div class="form-group">
                            <label class="form-label-lg"><?= htmlspecialchars(__($labelKey), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="password-wrapper">
                                <input type="password" id="<?= $id ?>" name="<?= $name ?>" maxlength="6" class="form-control"
                                    placeholder="<?= htmlspecialchars(__($placeKey), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="button" class="password-toggle"
                                    onclick="togglePasswordVisibility('<?= $id ?>', this)"
                                    title="<?= htmlspecialchars(__('show_password'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i data-lucide="eye" style="width:18px; height:18px;"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-primary">
                        <?= htmlspecialchars(__('save_changes'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            </div>

            <!-- Zona Berbahaya / Hapus Toko -->
            <hr class="divider-dashed" style="margin:24px 0;">
            <div>
                <h3 style="margin:0 0 6px; font-size:15px; color:#B45F5F;"><?= htmlspecialchars(__('danger_zone'), ENT_QUOTES, 'UTF-8') ?></h3>
                <p style="margin:0 0 14px; font-size:13px; color:#81776D;"><?= htmlspecialchars(__('delete_business_desc'), ENT_QUOTES, 'UTF-8') ?></p>
                <button type="button" id="btnDeleteBusiness" onclick="deleteActiveBusiness()" class="btn btn-danger-soft btn-sm">
                    <?= htmlspecialchars(__('delete_business_btn'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </section>
    </main>
</div>

<script>
window.APP_CONFIG = {
    businessId: <?= (int) $business_id ?>,
    showPassword: <?= json_encode(__('show_password')) ?>,
    hidePassword: <?= json_encode(__('hide_password')) ?>,
    confirmDeleteBusiness: <?= json_encode(__('confirm_delete_business')) ?>,
    deleteBusinessBtn: <?= json_encode(__('delete_business_btn')) ?>,
    cancelBtn: <?= json_encode(__('btn_cancel')) ?>,
    connectionError: <?= json_encode(__('connection_error')) ?>
};
document.addEventListener('DOMContentLoaded', () => {
    setupImagePreview('businessPhoto', 'businessPreview', 'businessUploadIcon');
    bindAsyncForm('settingsForm', (fd) => '../api/update-settings.php?business_id=' + fd.get('business_id'), { reload: true, delay: 1200 });
});
</script>
</body>
</html>
