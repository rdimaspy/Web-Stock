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
    <title><?= $lang === 'en' ? 'Settings' : 'Pengaturan' ?> - Stock</title>
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
                    <?= $lang === 'en' ? 'Update your business details and profile.' : 'Ubah detail bisnis dan profil akun kamu.' ?>
                </p>
            </div>

            <!-- Form Pengaturan -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; padding:24px;">
                <form id="settingsForm">
                    <!-- ID bisnis tersembunyi sebagai parameter acuan update data -->
                    <input type="hidden" name="business_id" value="<?= (int)$business_id ?>">

                    <!-- Input Nama Bisnis -->
                    <div style="margin-bottom:16px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size:14px;"><?= $lang === 'en' ? 'Business Name' : 'Nama Bisnis' ?></label>
                        <input type="text" name="business_name" value="<?= htmlspecialchars($business['business_name'], ENT_QUOTES, 'UTF-8') ?>" required style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                    </div>

                    <!-- Input Catatan Bisnis (Notes) -->
                    <div style="margin-bottom:16px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size:14px;">
                            <?= $lang === 'en' ? 'Business Notes (Max 20 chars)' : 'Catatan Bisnis (Maks 20 karakter)' ?>
                        </label>
                        <input type="text" name="notes" maxlength="20" 
                            value="<?= htmlspecialchars($business['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                            placeholder="<?= $lang === 'en' ? 'Brief note (max 20 chars)...' : 'Catatan singkat (maks 20 karakter)...' ?>" 
                            style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                    </div>

                    <hr style="border:0; border-top:1px dashed #E9E1D7; margin:20px 0;">

                    <!-- Input Nama Pengguna -->
                    <div style="margin-bottom:16px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size:14px;"><?= $lang === 'en' ? 'Account Name' : 'Nama Akun' ?></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($userProfile['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                    </div>

                    <!-- Email Pengguna (Hanya baca / disabled) -->
                    <div style="margin-bottom:20px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size:14px;"><?= $lang === 'en' ? 'Email Address' : 'Alamat Email' ?></label>
                        <input type="email" value="<?= htmlspecialchars($userProfile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #E9E1D7; border-radius:8px; background:#F5EFE8; color:#81776D;">
                    </div>

                    <!-- Tombol Simpan Perubahan Pengaturan -->
                    <button type="submit" style="padding:11px 22px; background:#76553D; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
                        <?= $lang === 'en' ? 'Save Changes' : 'Simpan Perubahan' ?>
                    </button>
                </form>
            </div>

            <!-- Bagian Zona Berbahaya / Hapus Toko -->
            <hr style="border:0; border-top:1px dashed #E9E1D7; margin:24px 0;">
            <div>
                <!-- Judul -->
                <h3 style="margin:0 0 6px; font-size:15px; color:#B45F5F;">
                    <?= $lang === 'en' ? 'Danger Zone' : 'Zona Berbahaya' ?>
                </h3>

                <!-- Deskripsi -->
                <p style="margin:0 0 14px; font-size:13px; color:#81776D;">
                    <?= $lang === 'en' ? 'Deleting this business will permanently erase all associated products, categories, and transaction history.' : 'Menghapus toko ini akan menghapus seluruh produk, kategori, dan riwayat transaksi di dalamnya secara permanen.' ?>
                </p>

                <!-- Tombol hapus toko -->
                <button type="button" id="btnDeleteBusiness" style="padding:10px 18px; background:#FBECEC; color:#B45F5F; border:1px solid #F3C6C6; border-radius:8px; font-weight:600; cursor:pointer; font-size:13px;">
                    <?= $lang === 'en' ? 'Delete Business' : 'Hapus Toko Ini' ?>
                </button>
            </div>
        </section>
    </main>
</div>

<!-- Ikon -->
<script src="https://unpkg.com/lucide@latest"></script>

<script>
lucide.createIcons();

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
    const confirmDelete = confirm(
        <?= json_encode($lang === 'en' 
            ? 'Are you sure you want to permanently delete this business? All data will be lost!' 
            : 'Apakah kamu yakin ingin menghapus toko ini secara permanen? Seluruh data produk dan transaksi akan hilang!') ?>
    );

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