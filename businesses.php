<?php
// Memulai sesi untuk mengakses data pengguna yang login
session_start();

// Menentukan bahasa yang digunakan
$lang = $_SESSION['language'] ?? 'id';
if (!in_array($lang, ['id', 'en'], true)) {
    $lang = 'id';
}

// Memeriksa autentikasi; mengarahkan ke halaman login jika belum terautentikasi
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

// Memuat koneksi database, formatter mata uang, dan translate
include "config/database.php";
require_once __DIR__ . '/includes/currency.php';
require_once __DIR__ . '/includes/language.php';

// Ambil ID pengguna aktif dari sesi
$user_id = (int) $_SESSION['user_id'];

// Ambil daftar bisnis pengguna dari database menggunakan Prepared Statement secara aman
$stmt = mysqli_prepare($conn, "SELECT * FROM businesses WHERE user_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <!-- Pengaturan karakter dan viewport halaman agar responsif di HP -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business | Stock</title>

    <!-- CSS khusus untuk efek & responsif HP -->
    <style>
        /* Efek Hover Card */
        .business-card {
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .business-card:hover {
            transform: translateY(-3px);
            border-color: #A77D5B !important;
            box-shadow: 0 10px 24px rgba(118, 85, 61, 0.12);
            background: #FFFFFF !important;
        }
        .business-card:hover .arrow-icon {
            transform: translateX(4px);
            color: #76553D !important;
        }
        .arrow-icon {
            transition: transform 0.25s ease, color 0.25s ease;
        }
    </style>
</head>

<body style="margin:0; background:#FAF7F2; font-family:Arial, Helvetica, sans-serif; color:#302921;">

    <!-- Container Utama Halaman -->
    <div class="main-container" style="max-width:1100px; margin:auto; padding:30px;">

        <!-- HEADER DAFTAR BISNIS -->
        <div style="display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; margin-bottom:30px;">
            <!-- Sapaan Pengguna -->
            <div>
                <h2 style="margin:0;">
                    <?= htmlspecialchars(__('hello'), ENT_QUOTES, 'UTF-8') ?>
                    <?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <p style="color:#81776D; margin-top:8px; margin-bottom:0;">
                    <?= htmlspecialchars(__('select_business_desc'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- Tombol Aksi Header (Bahasa, Buat Bisnis, Logout) -->
            <div class="header-actions" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                
                <!-- PILIHAN BAHASA -->
                <div style="position:relative; display:inline-block;">
                    <button type="button" onclick="const m=document.getElementById('business-language-menu');m.style.display=m.style.display==='block'?'none':'block';"
                        style="display:flex; align-items:center; gap:8px; padding:8px; border:0; background:transparent; color:#81776D; cursor:pointer;">
                        <i data-lucide="globe" style="width:21px; height:21px;"></i>
                        <i data-lucide="chevron-down" style="width:15px; height:15px;"></i>
                    </button>

                    <!-- Dropdown Menu Bahasa -->
                    <div id="business-language-menu" class="lang-dropdown-menu"
                        style="display:none; position:absolute; top:42px; right:0; width:145px; background:white; border:1px solid #E9E1D7; border-radius:14px; padding:8px; box-shadow:0 5px 18px rgba(73,56,45,0.10); z-index:100;">
                        <form action="set-language.php" method="POST" style="margin:0;">
                            <input type="hidden" name="language" value="id">
                            <input type="hidden" name="redirect" value="businesses.php">

                            <button type="submit" data-language-option="id" style="width:100%; padding:12px; text-align:left; border:0; border-radius:9px; background:<?= $lang === 'id' ? '#F5EFE8' : 'white' ?>; color:#49382D; cursor:pointer;">
                                Indonesia
                            </button>
                        </form>

                        <form action="set-language.php" method="POST" style="margin:0;">
                            <input type="hidden" name="language" value="en">
                            <input type="hidden" name="redirect" value="businesses.php">
                            <button type="submit" data-language-option="en" style="width:100%; padding:12px; text-align:left; border:0; border-radius:9px; background:<?= $lang === 'en' ? '#F5EFE8' : 'white' ?>; color:#49382D; cursor:pointer;">
                                English
                            </button>
                        </form>

                    </div>
                </div>

                <!-- TOMBOL BUAT BISNIS -->
                <a href="create-business.php" class="btn-action"
                style="background:#76553D; color:white; padding:12px 20px; border-radius:10px; text-decoration:none; font-size:14px; font-weight:600; white-space:nowrap; display:inline-flex; align-items:center;">
                    <?= htmlspecialchars(__('create_business_btn'), ENT_QUOTES, 'UTF-8') ?>
                </a>

                <!-- Dropdown Pengaturan Akun & Logout -->
                <div style="position:relative; display:inline-block;">
                    <!-- Tombol ikon gear pemicu dropdown -->
                    <button type="button" onclick="const m=document.getElementById('user-settings-menu');m.style.display=m.style.display==='block'?'none':'block';" style="display:flex; align-items:center; justify-content:center; width:42px; height:42px; border:1px solid #E9E1D7; border-radius:10px; background:white; color:#76553D; cursor:pointer;">
                        <i data-lucide="settings" style="width:20px; height:20px;"></i>
                    </button>

                    <!-- Menu Dropdown Pengaturan Akun -->
                    <div id="user-settings-menu" style="display:none; position:absolute; top:48px; right:0; width:180px; background:white; border:1px solid #E9E1D7; border-radius:12px; padding:6px; box-shadow:0 6px 20px rgba(73,56,45,0.12); z-index:100;">
                        <!-- Pilihan Keluar / Logout -->
                        <a href="logout.php" style="display:flex; align-items:center; gap:8px; padding:10px; border-radius:8px; color:#49382D; text-decoration:none; font-size:13px; font-weight:600;">
                            <i data-lucide="log-out" style="width:16px; height:16px; color:#76553D;"></i>
                            <?= htmlspecialchars(__('logout'), ENT_QUOTES, 'UTF-8') ?>
                        </a>

                        <button type="button" onclick="deleteUserAccount()" style="width:100%; display:flex; align-items:center; gap:8px; padding:10px; border:0; background:#B45F5F; border-radius:8px; color:white; cursor:pointer; font-size:13px; font-weight:600; text-align:left;">
                            <i data-lucide="trash-2" style="width:16px; height:16px; color:white;"></i>
                            <?= htmlspecialchars(__('delete_account'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- DAFTAR KARTU BISNIS -->
        <div class="business-wrapper" style="background:white; border:1px solid #E9E1D7; border-radius:16px; padding:24px;">
            <h3 style="margin:0 0 20px;">
                <?= __('businesses') ?>
            </h3>

            <!-- Grid Kartu Bisnis -->
            <div class="grid-container" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px;">
                <?php if (mysqli_num_rows($query) === 0): ?>
                    <!-- Tampilan Kosong Jika Belum Ada Bisnis -->
                    <div style="grid-column:1 / -1; text-align:center; padding:40px 20px; color:#81776D;">
                        <i data-lucide="store" style="width:40px; height:40px; margin-bottom:12px;"></i>
                        <p style="margin:0; font-size:15px; line-height:1.6;">
                            <?= htmlspecialchars(__('no_businesses'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                <?php else: ?>
                    <!-- Looping Menampilkan Setiap Bisnis Milik Pengguna -->
                    <?php while ($business = mysqli_fetch_assoc($query)): ?>
                    <a href="dashboard/index.php?business_id=<?= (int) $business['id'] ?>" class="business-card"
                    style="display:flex; align-items:center; gap:14px; min-width:0; padding:16px; background:#FAF7F2; border:1.5px solid #E9E1D7; border-radius:14px; text-decoration:none; color:#302921;">

                        <!-- FOTO ATAU INISIAL BISNIS -->
                        <?php if (
                            !empty($business['photo']) &&
                            file_exists(__DIR__ . '/uploads/businesses/' . $business['photo'])
                        ): ?>
                            <!-- Tampilkan foto bisnis jika file ada di folder uploads/businesses/ -->
                            <img src="uploads/businesses/<?= htmlspecialchars($business['photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto bisnis"
                                style="width:56px; height:56px; flex-shrink:0; border-radius:10px; object-fit:cover;">
                        <?php else: ?>
                            <!-- Tampilkan inisial huruf jika tidak ada foto -->
                            <div style="width:56px; height:56px; flex-shrink:0; border-radius:10px; background:#76553D; color:white; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:bold;">
                                <?= htmlspecialchars(strtoupper(substr($business['business_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <!-- INFORMASI DETAIL BISNIS -->
                        <div style="flex:1; min-width:0;">
                            <!-- Nama bisnis -->
                            <div style="font-size:16px; font-weight:700; overflow-wrap:anywhere;">
                                <?= htmlspecialchars($business['business_name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <!-- Mata uang utama bisnis -->
                            <div style="margin-top:5px; color:#81776D; font-size:13px;">
                                <?= htmlspecialchars($business['currency'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <!-- Catatan bisnis singkat maksimal 20 karakter -->
                            <?php if (!empty(trim($business['notes'] ?? ''))): ?>
                                <div
                                    title="<?= htmlspecialchars($business['notes'], ENT_QUOTES, 'UTF-8') ?>"
                                    style="margin-top:6px; color:#81776D; font-size:12px; line-height:1.4; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars(mb_strimwidth(trim($business['notes']), 0, 20), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- IKON PANAH-->
                        <i data-lucide="chevron-right" class="arrow-icon"
                        style="width:18px; height:18px; flex-shrink:0; color:#948578;"></i>
                    </a>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

<!-- Ikon -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();

// Menangani proses penghapusan akun pengguna secara permanen
async function deleteUserAccount() {
    // Tampilkan kotak dialog konfirmasi penghapusan akun
    const confirmDelete = confirm(
        <?= json_encode(__('confirm_delete_account')) ?>
    );

    // Batalkan proses jika pengguna memilih batal
    if (!confirmDelete) return;

    try {
        // Kirim permintaan penghapusan akun ke API backend
        const response = await fetch('api/delete-account.php', {
            method: 'POST'
        });

        // Konversi respon dari server ke format JSON
        const result = await response.json();
        alert(result.message);

        // Jika berhasil, alihkan pengguna ke halaman login
        if (result.status === 'success') {
            window.location.href = 'login.html';
        }
    } catch (err) {
        // Tangani jika terjadi kesalahan koneksi atau server
        console.error(err);
        alert('Terjadi kesalahan saat menghapus akun.');
    }
}
</script>

</body>

</html>