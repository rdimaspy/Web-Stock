<?php
// Memulai sesi dan menentukan bahasa yang sedang aktif
session_start();
$lang = $_SESSION['language'] ?? 'id';

// Memuat kamus bahasa berdasarkan file PHP di folder lang/
$langFile = __DIR__ . "/lang/{$lang}.php";
$trans = file_exists($langFile) ? require $langFile : require __DIR__ . "/lang/id.php";
?>


<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Stock</title>

    <!-- Library ikon Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- CSS background register-->
    <style>
        .bg-orb-1 {
            position: absolute;
            top: -10%;
            left: -5%;
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, rgba(235, 218, 199, 0.6) 0%, rgba(250, 247, 242, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        .bg-orb-2 {
            position: absolute;
            bottom: -10%;
            right: -5%;
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, rgba(216, 193, 171, 0.45) 0%, rgba(250, 247, 242, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        .input-field {
            transition: all 0.25s ease;
        }

        .input-field:focus {
            border-color: #A77D5B !important;
            box-shadow: 0 0 0 3px rgba(167, 125, 91, 0.15) !important;
            outline: none;
        }
    </style>
</head>

<!-- Body Utama Halaman -->
<body style="margin: 0; background: #FAF7F2; font-family: Arial, Helvetica, sans-serif; color: #302921; overflow-x: hidden; position: relative;">

    <!-- Desain elemen abstrak (Latar belakang) -->
    <div class="bg-orb-1"></div>
    <div class="bg-orb-2"></div>

    <!-- Container utama form pendaftaran -->
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 30px; position: relative; z-index: 5;">

        <!-- Kartu Form Registrasi -->
        <div style="width: 100%; max-width: 420px; background: #FFFFFF; padding: 40px; border-radius: 20px; border: 1px solid #E9E1D7; box-shadow: 0 20px 50px rgba(92,69,48,0.07);">

            <!-- Identitas Logo dan Judul Registrasi -->
            <div style="text-align: center; margin-bottom: 30px;">
                <a href="index.php" style="text-decoration: none; color: #302921; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">
                    Stock<span style="color: #A77D5B;">.</span>
                </a>

                <p style="margin-top: 10px; color: #81776D; font-size: 14px; line-height: 1.5;">
                    <?= htmlspecialchars($trans['register_subtitle'] ?? 'Buat akun untuk mulai mengelola stok bisnis.'); ?>
                </p>
            </div>

            <!-- Form pengiriman data pendaftaran akun -->
            <form action="api/api-register.php" method="POST">
                <!-- Bidang Isian Nama Lengkap -->
                <div style="margin-bottom: 18px;">
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:7px; color:#49382D;">
                        <?= htmlspecialchars($trans['name_label'] ?? 'Nama'); ?>
                    </label>

                    <input type="text" name="name" class="input-field" 
                        placeholder="<?= htmlspecialchars($trans['name_placeholder'] ?? 'Masukkan nama'); ?>" 
                        style="width: 100%; padding: 12px 14px; border: 1px solid #E9E1D7; border-radius: 10px; box-sizing: border-box; font-size: 14px; color: #302921;" required>
                </div>

                <!-- Email -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight:600; margin-bottom: 7px; color:#49382D;">
                        <?= htmlspecialchars($trans['email_label'] ?? 'Email'); ?>
                    </label>

                    <input type="email" name="email" class="input-field" 
                        placeholder="<?= htmlspecialchars($trans['email_placeholder'] ?? 'Masukkan email'); ?>"
                        style="width: 100%; padding: 12px 14px; border: 1px solid #E9E1D7; border-radius: 10px; box-sizing: border-box; font-size: 14px; color: #302921;" required>
                </div>

                <!-- Password -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 13px; font-weight:600; margin-bottom: 7px; color:#49382D;">
                        <?= htmlspecialchars($trans['password_label'] ?? 'Password'); ?>
                    </label>

                    <!-- Ketentuan password max 6 cahr -->
                    <input type="password" name="password" class="input-field" minlength="6" maxlength="6" 
                        placeholder="<?= htmlspecialchars($trans['create_password_placeholder'] ?? 'Buat password (6 karakter)'); ?>"
                        style="width: 100%; padding: 12px 14px; border: 1px solid #E9E1D7; border-radius: 10px; box-sizing: border-box; font-size: 14px; color: #302921;" required>
                </div>

                <!-- Tombol Submit Buat Akun -->
                <button type="submit" style="width: 100%; padding: 13px; background: #76553D; color: #FFFFFF; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                    <?= htmlspecialchars($trans['create_account_btn'] ?? 'Buat Akun'); ?>
                </button>
            </form>

            <!-- Tautan navigasi ke halaman login -->
            <p style="text-align:center; margin-top:28px; color:#81776D; font-size:13px;">
                <span><?= htmlspecialchars($trans['already_have_account'] ?? 'Sudah punya akun?'); ?></span>
                <a href="login.php" style="color:#76553D; text-decoration:none; font-weight:600; margin-left:4px;">
                    <?= htmlspecialchars($trans['login'] ?? 'Masuk'); ?>
                </a>
            </p>

            <!-- Tombol Kembali -->
            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed #E9E1D7; text-align: center;">
                <a href="index.php" style="display: inline-flex; align-items: center; gap: 6px; color: #81776D; text-decoration: none; font-size: 13px; font-weight: 500;">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
                    <span><?= htmlspecialchars($trans['back_to_home'] ?? 'Kembali ke Beranda'); ?></span>
                </a>
            </div>
        </div>

    </div>

    <!-- Ikon -->
<script src="https://unpkg.com/lucide@latest"></script>

<script> lucide.createIcons(); </script>

</body>
</html>