<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Pengaturan bahasa
require_once __DIR__ . '/../includes/language.php';

// Ambil data profil pengguna aktif
$stmtUser = mysqli_prepare($conn, "SELECT name, email FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmtUser, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmtUser);
$userProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUser));
?>



<!DOCTYPE html>
<!-- Set bahasa -->
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('settings_title'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>
</head>

<!-- Styling utama layout dan warna latar halaman -->
<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'settings.php'; ?>

<!-- Layout utama halaman pengaturan-->
<div style="display:flex; min-height:100vh;">

    <!-- SIDEBAR -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- KONTEN UTAMA -->
    <main style="margin-left:230px; flex:1; min-width:0;">
        
        <!-- HEADER -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- HALAMAN SETTING -->
        <section style="padding:32px; max-width:650px;">
            <div style="margin-bottom:20px;">
                <!-- Judul halaman -->
                <h1 style="margin:0; font-size:26px;"><?= $lang === 'en' ? 'Settings' : 'Pengaturan' ?></h1>
                <!-- Deskripsi -->
                <p style="color:#81776D; margin-top:6px; font-size:14px;">
                    <?= htmlspecialchars(__('settings_description'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- Form Pengaturan -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:24px;">
                <form id="settingsForm" enctype="multipart/form-data">
                    <!-- ID bisnis tersembunyi sebagai parameter acuan update data -->
                    <input type="hidden" name="business_id" value="<?= (int)$business_id ?>">

                    <!-- Input Foto Toko / Bisnis -->
                    <div style="margin-bottom:20px;">
                        <label style="display:block; margin-bottom:8px; font-weight:600; font-size:14px;">
                            <?= htmlspecialchars(__('business_logo_photo'), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                        
                        <div style="display:flex; align-items:center; gap:16px;">
                            <!-- Area Preview -->
                            <label for="businessPhoto" style="width:80px; height:80px; border-radius:12px; background:#F5EFE8; border:1px solid #E9E1D7; display:flex; align-items:center; justify-content:center; cursor:pointer; overflow:hidden; flex-shrink:0;">
                                <img id="businessPreview" src="" style="width:100%; height:100%; object-fit:cover; display:none;">
                                <i id="businessUploadIcon" data-lucide="camera" style="width:28px; height:28px; color:#76553D;"></i>
                            </label>

                            <!-- Tombol dan Keterangan -->
                            <div>
                                <input type="file" id="businessPhoto" name="photo" accept="image/*" style="display:none;">
                                
                                <label for="businessPhoto" style="display:inline-block; padding:8px 14px; background:#F5EFE8; color:#76553D; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">
                                    <?= htmlspecialchars(__('change_photo'), ENT_QUOTES, 'UTF-8') ?>
                                </label>
                                
                                <p style="margin:6px 0 0; color:#81776D; font-size:12px;">
                                    <?= htmlspecialchars(__('photo_hint'), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Input Nama Bisnis -->
                    <div style="margin-bottom:16px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size:14px;"><?= htmlspecialchars(__('business_name_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="business_name" value="<?= htmlspecialchars($business['business_name'], ENT_QUOTES, 'UTF-8') ?>" required style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                    </div>

                    <!-- Input Catatan Bisnis (Notes) -->
                    <div style="margin-bottom:16px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size:14px;">
                            <?= htmlspecialchars(__('business_notes_label'), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                        <input type="text" name="notes" maxlength="20" 
                            value="<?= htmlspecialchars($business['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                            placeholder="<?= htmlspecialchars(__('business_notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>" 
                            style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                    </div>

                    <hr style="border:0; border-top:1px dashed #E9E1D7; margin:20px 0;">

                    <!-- Input Nama Pengguna -->
                    <div style="margin-bottom:16px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size:14px;"><?= htmlspecialchars(__('account_name_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($userProfile['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                    </div>

                    <!-- Email Pengguna (Hanya baca / disabled) -->
                    <div style="margin-bottom:20px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size:14px;"><?= htmlspecialchars(__('email_address_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="email" value="<?= htmlspecialchars($userProfile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #E9E1D7; border-radius:8px; background:#F5EFE8; color:#81776D;">
                    </div>

                    <!-- Tombol Simpan Perubahan Pengaturan -->
                    <button type="submit" style="padding:11px 22px; background:#76553D; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
                        <?= htmlspecialchars(__('save_changes'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            </div>

            <!-- Bagian Zona Berbahaya / Hapus Toko -->
            <hr style="border:0; border-top:1px dashed #E9E1D7; margin:24px 0;">
            <div>
                <!-- Judul -->
                <h3 style="margin:0 0 6px; font-size:15px; color:#B45F5F;">
                    <?= htmlspecialchars(__('danger_zone'), ENT_QUOTES, 'UTF-8') ?>
                </h3>

                <!-- Deskripsi -->
                <p style="margin:0 0 14px; font-size:13px; color:#81776D;">
                    <?= htmlspecialchars(__('delete_business_desc'), ENT_QUOTES, 'UTF-8') ?>
                </p>

                <!-- Tombol hapus toko -->
                <button type="button" id="btnDeleteBusiness" style="padding:10px 18px; background:#FBECEC; color:#B45F5F; border:1px solid #F3C6C6; border-radius:8px; font-weight:600; cursor:pointer; font-size:13px;">
                    <?= htmlspecialchars(__('delete_business_btn'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </section>
    </main>
</div>

<!-- Ikon -->
<script src="https://unpkg.com/lucide@latest"></script>

<script>
lucide.createIcons();

// Preview foto baru, jika file dipilih
document.getElementById('businessPhoto').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('businessPreview');
            const icon = document.getElementById('businessUploadIcon');
            
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (icon) icon.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

// Menangani pengiriman form pengaturan secara asinkron via Fetch API
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Ambil ID bisnis dari form data
    const businessId = formData.get('business_id');

    try {
        // Kirim data pembaruan pengaturan ke backend API
        const response = await fetch('../api/update-settings.php?business_id=' + businessId, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        // Memunculkan pesan respons dan memuat ulang halaman jika pembaruan sukses
        alert(result.message);
        if (result.status === 'success') {
            location.reload();
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan koneksi atau respon server tidak valid.');
    }
});


// Menangani proses penghapusan toko saat tombol hapus toko diklik
document.getElementById('btnDeleteBusiness').addEventListener('click', async function() {
    // Tampilkan dialog konfirmasi penghapusan toko
    const confirmDelete = confirm(<?= json_encode(__('confirm_delete_business')) ?>);

    // Batalkan proses jika pengguna memilih batal
    if (!confirmDelete) return;

    // Siapkan parameter ID bisnis yang akan dihapus
    const formData = new FormData();
    formData.append('business_id', '<?= (int)$business_id ?>');

    try {
        // Kirim permintaan penghapusan toko ke API
        const response = await fetch('../api/delete-business.php', {
            method: 'POST',
            body: formData
        });

        const textText = await response.text();
        
        // Parsing hasil respon JSON dari server
        let result;
        try {
            result = JSON.parse(textText);
        } catch (e) {
            console.error('Server mengembalikan HTML alih-alih JSON:', textText);
            alert('Terjadi kesalahan server. Cek konsol browser untuk melihat detail error.');
            return;
        }

        alert(result.message);

        // Jika penghapusan berhasil, alihkan pengguna kembali ke halaman daftar bisnis
        if (result.status === 'success') {
            window.location.href = '../businesses.php';
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan koneksi saat menghapus toko.');
    }
});
</script>

</body>
</html>