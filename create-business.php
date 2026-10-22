<?php
// Memulai sesi untuk mengakses data pengguna yang login
session_start();

// Menentukan bahasa yang digunakan
$lang = $_SESSION['language'] ?? 'id';
if (!in_array($lang, ['id', 'en'], true)) {
    $lang = 'id';
}

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// Pengaturan bahasa
require_once __DIR__ . '/includes/language.php';
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Business | StockIn</title>
</head>

<!-- Body Utama Halaman -->
<body style="margin:0; background:#FAF7F2; font-family:Arial, Helvetica, sans-serif; color:#302921;">

    <!-- Layout di Tengah Layar -->
    <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:30px;">

        <!-- Kartu Form Pembuatan Bisnis Baru -->
        <div style="width:100%; max-width:480px; background:#FFFFFF; padding:35px; border-radius:18px; border:1px solid #E9E1D7; box-shadow:0 15px 40px rgba(92,69,48,0.08);">

            <!-- HEADER -->
            <div style="text-align:center; margin-bottom:30px;">
                <h2 style="margin:0; font-size:26px;"><?= htmlspecialchars(__('create_business_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p style="color:#81776D; font-size:14px; margin-top:10px;"><?= htmlspecialchars(__('create_business_desc'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <form id="createBusinessForm" enctype="multipart/form-data">

                <!-- PHOTO -->
                <label style="display:block; font-size:13px; margin-bottom:10px;">
                    <?= htmlspecialchars(__('store_photo_optional'), ENT_QUOTES, 'UTF-8') ?>
                </label>
                <label for="business-photo" id="upload-box" style="width:72px; height:72px; background:#F3E8DC; border-radius:18px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#76553D; overflow:hidden; position:relative; margin-bottom:18px;">
                    <i data-lucide="image-plus" id="upload-icon" style="width:28px; height:28px;"></i>
                    <input id="business-photo" type="file" name="photo" accept="image/*" style="display:none;">
                </label>

                <!-- BUSINESS NAME -->
                <label style="display:block; font-size:13px; margin-bottom:7px;"><?= htmlspecialchars(__('store_name'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" name="business_name" maxlength="40" placeholder="<?= htmlspecialchars(__('store_name_placeholder'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:12px; border:1px solid #E9E1D7; border-radius:9px; margin-bottom:18px; box-sizing:border-box;" required>

                <!-- CURRENCY -->
                <label style="display:block; font-size:13px; margin-bottom:7px;"><?= htmlspecialchars(__('currency'), ENT_QUOTES, 'UTF-8') ?></label>
                <select name="currency" style="width:100%; padding:12px; border:1px solid #E9E1D7; border-radius:9px; margin-bottom:18px; background:white;">
                    <option value="IDR">IDR - Indonesian Rupiah</option>
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="JPY">JPY - Japanese Yen</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="CNY">CNY - Chinese Yuan</option>
                </select>

                <!-- NOTES -->
                <label style="display:block; font-size:13px; margin-bottom:7px;"><?= htmlspecialchars(__('notes_optional'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" name="notes" maxlength="30" placeholder="<?= htmlspecialchars(__('notes_business_placeholder'), ENT_QUOTES, 'UTF-8') ?>" 
                style="width:100%; padding:10px 12px; border:1px solid #E9E1D7; border-radius:9px; box-sizing:border-box; margin-bottom:25px; outline-color:#B99A7D; font-size:14px;">

                <!-- Tombol buat bisnis -->
                <button type="submit" style="width:100%; padding:13px; background:#76553D; color:#FFFFFF; border:none; border-radius:9px; font-size:14px; font-weight:600; cursor:pointer;"><?= htmlspecialchars(__('create_business_title'), ENT_QUOTES, 'UTF-8') ?></button>
                
            </form>

            <!-- Tombol Kembali -->
            <p style="text-align:center; margin-top:22px; font-size:13px;">
                <a href="businesses.php" style="color:#76553D; text-decoration:none;"><?= htmlspecialchars(__('back_to_business_list'), ENT_QUOTES, 'UTF-8') ?></a>
            </p>

        </div>
    </div>

<!-- Library Ikon & Notifikasi UI Kustom -->
<script src="https://unpkg.com/lucide@latest"></script>
<script src="assets/js/toast.js" defer></script>

<script>
lucide.createIcons();

// Ambil elemen input file, kotak pratinjau, dan ikon upload
const input = document.getElementById("business-photo");
const box = document.getElementById("upload-box");
const icon = document.getElementById("upload-icon");

// Menangani event saat pengguna memilih file foto baru
input.addEventListener("change", function(){
    const file = this.files[0];
    if(file){
        // Membaca file gambar yang dipilih menggunakan FileReader API
        const reader = new FileReader();
        reader.onload = function(e){
            // Sembunyikan ikon upload default
            icon.style.display = "none";

            // Hapus pratinjau foto lama jika sebelumnya sudah ada
            const oldPreview = box.querySelector("img");
            if(oldPreview){ oldPreview.remove(); }

            // Buat elemen <img> baru untuk menampilkan pratinjau foto toko
            const img = document.createElement("img");
            img.src = e.target.result;
            img.style.width = "100%";
            img.style.height = "100%";
            img.style.objectFit = "cover";
            box.appendChild(img);
        };
        // Konversi file gambar ke URL data biner
        reader.readAsDataURL(file);
    }
});

// Menangani proses pengiriman formulir pembuatan bisnis
document.getElementById('createBusinessForm')?.addEventListener('submit', async function(e) {
    // Mencegah reload halaman bawaan formulir
    e.preventDefault();

    // Membaca seluruh data input dari form
    const formData = new FormData(this);

    try {
        // Mengirimkan data pembuatan toko ke API backend
        const response = await fetch('api/api-create-business.php', {
            method: 'POST',
            body: formData
        });

        // Menerjemahkan respons server ke format JSON
        const result = await response.json();

        // Menampilkan notifikasi toast sesuai hasil pemrosesan
        if (result.status === 'success') {
            showToast(result.message, 'success');

            // Mengalihkan halaman ke daftar bisnis setelah jeda notifikasi
            setTimeout(() => {
                window.location.href = 'businesses.php';
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