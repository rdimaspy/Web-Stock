<?php
// Memulai sesi dan menentukan bahasa yang sedang aktif
session_start();
require_once __DIR__ . '/includes/language.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | StockIn</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="auth-body">

    <div class="bg-orb-1"></div>
    <div class="bg-orb-2"></div>

    <!-- Container Form Autentikasi -->
    <div class="center-page-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <?php renderLogo(); ?>
                <p class="auth-subtitle"><?= htmlspecialchars(__('login_subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars(__('email_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="email" name="email" class="input-field"
                        placeholder="<?= htmlspecialchars(__('email_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group" style="margin-bottom:24px;">
                    <label class="form-label"><?= htmlspecialchars(__('password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <div class="password-wrapper">
                        <input type="password" id="loginPassword" name="password" class="input-field" minlength="6" maxlength="6"
                            placeholder="<?= htmlspecialchars(__('password_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="password-toggle"
                            onclick="togglePasswordVisibility('loginPassword', this)"
                            title="<?= htmlspecialchars(__('show_password'), ENT_QUOTES, 'UTF-8') ?>"
                            aria-label="<?= htmlspecialchars(__('show_password'), ENT_QUOTES, 'UTF-8') ?>">
                            <i data-lucide="eye" style="width:18px; height:18px;"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <?= htmlspecialchars(__('login'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>

            <p class="auth-switch-text">
                <span><?= htmlspecialchars(__('no_account'), ENT_QUOTES, 'UTF-8') ?></span>
                <a href="register.php" class="auth-switch-link"><?= htmlspecialchars(__('signup'), ENT_QUOTES, 'UTF-8') ?></a>
            </p>

            <div class="auth-back-box">
                <a href="index.php" class="auth-back-link">
                    <i data-lucide="arrow-left" style="width:14px; height:14px;"></i>
                    <span><?= htmlspecialchars(__('back_to_home'), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>
        </div>
    </div>

    <script>
        window.APP_CONFIG = {
            showPassword: <?= json_encode(__('show_password')) ?>,
            hidePassword: <?= json_encode(__('hide_password')) ?>,
            connectionError: <?= json_encode(__('connection_error')) ?>
        };
    </script>
    <script src="assets/js/toast.js?v=3"></script>
    <script src="assets/js/app.js?v=3"></script>
</body>
</html>
