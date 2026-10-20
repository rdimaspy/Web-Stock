<?php
// Memulai sesi dan menentukan bahasa yang sedang aktif
session_start();
require_once __DIR__ . '/includes/language.php';
?>


<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | StockIn</title>

    <!-- Library ikon Lucide & Notifikasi UI Kustom -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/toast.js" defer></script>

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
                <!-- Logo StockIn -->
                <div style="display:inline-flex; align-items:center; font-family:Arial, sans-serif; font-weight:800; font-size:24px; letter-spacing:-0.5px;">
                <!-- Teks "Stock" -->
                    <span style="color:#49382D;">Stock</span>

                    <!-- Container Berlapis / Stacked Box -->
                    <div style="position:relative; display:inline-block; margin-left:6px;">
                        <!-- Lapisan Bayangan Belakang (Dus Bawah) -->
                        <span style="position:absolute; top:-3px; right:-3px; width:100%; height:100%; background:#B99A7D; border-radius:6px; z-index:1;"></span>
                        
                        <!-- Kotak Utas "In" (Dus Atas) -->
                        <span style="position:relative; z-index:2; display:block; background:#76553D; color:white; padding:3px 8px; border-radius:6px; font-size:20px; line-height:1.2;">
                            In
                        </span>
                    </div>
                </div>

                <p style="margin-top: 10px; color: #81776D; font-size: 14px; line-height: 1.5;">
                    <?= htmlspecialchars(__('register_subtitle'), ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>

            <!-- Form pengiriman data pendaftaran akun -->
            <form id="registerForm">
                <!-- Bidang Isian Nama Lengkap -->
                <div style="margin-bottom: 18px;">
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:7px; color:#49382D;">
                        <?= htmlspecialchars(__('name_label'), ENT_QUOTES, 'UTF-8'); ?>
                    </label>

                    <input type="text" name="name" class="input-field" 
                        placeholder="<?= htmlspecialchars(__('name_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" 
                        style="width: 100%; padding: 12px 14px; border: 1px solid #E9E1D7; border-radius: 10px; box-sizing: border-box; font-size: 14px; color: #302921;" required>
                </div>

                <!-- Email -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight:600; margin-bottom: 7px; color:#49382D;">
                        <?= htmlspecialchars(__('email_label'), ENT_QUOTES, 'UTF-8'); ?>
                    </label>

                    <input type="email" name="email" class="input-field" 
                        placeholder="<?= htmlspecialchars(__('email_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                        style="width: 100%; padding: 12px 14px; border: 1px solid #E9E1D7; border-radius: 10px; box-sizing: border-box; font-size: 14px; color: #302921;" required>
                </div>

                <!-- Password -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 13px; font-weight:600; margin-bottom: 7px; color:#49382D;">
                        <?= htmlspecialchars(__('password_label'), ENT_QUOTES, 'UTF-8'); ?>
                    </label>

                    <!-- Ketentuan password max 6 cahr -->
                    <input type="password" name="password" class="input-field" minlength="6" maxlength="6" 
                        placeholder="<?= htmlspecialchars(__('create_password_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                        style="width: 100%; padding: 12px 14px; border: 1px solid #E9E1D7; border-radius: 10px; box-sizing: border-box; font-size: 14px; color: #302921;" required>
                </div>

                <!-- Tombol Submit Buat Akun -->
                <button type="submit" style="width: 100%; padding: 13px; background: #76553D; color: #FFFFFF; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                    <?= htmlspecialchars(__('create_account_btn'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </form>

            <!-- Tautan navigasi ke halaman login -->
            <p style="text-align:center; margin-top:28px; color:#81776D; font-size:13px;">
                <span><?= htmlspecialchars(__('already_have_account'), ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="login.php" style="color:#76553D; text-decoration:none; font-weight:600; margin-left:4px;">
                    <?= htmlspecialchars(__('login'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </p>

            <!-- Tombol Kembali -->
            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed #E9E1D7; text-align: center;">
                <a href="index.php" style="display: inline-flex; align-items: center; gap: 6px; color: #81776D; text-decoration: none; font-size: 13px; font-weight: 500;">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
                    <span><?= htmlspecialchars(__('back_to_home'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            </div>
        </div>

    </div>

<!-- Library ikon Lucide & Notifikasi UI Kustom -->
<script src="https://unpkg.com/lucide@latest"></script>
<script src="assets/js/toast.js" defer></script>

<script>
lucide.createIcons();

// Menangani pengiriman form pendaftaran akun secara asinkron via Fetch API
document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
    // Mencegah reload halaman bawaan
    e.preventDefault();

    // Membaca seluruh data input formulir pendaftaran
    const formData = new FormData(this);

    try {
        // Mengirimkan data pendaftaran ke API backend
        const response = await fetch('api/api-register.php', {
            method: 'POST',
            body: formData
        });

        // Menerjemahkan hasil respons ke format JSON
        const result = await response.json();

        // Menampilkan notifikasi toast berdasarkan hasil registrasi
        if (result.status === 'success') {
            showToast(result.message, 'success');

            // Jika sukses membuat akun, alihkan ke halaman login setelah jeda singkat
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1200);
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        // Menangani kegagalan koneksi atau error server
        console.error(err);
        showToast(<?= json_encode(__('connection_error')) ?>, 'error');
    }
});
</script>

</body>
</html>