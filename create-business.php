<?php

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Business | Stock</title>
</head>

<body style="margin:0; background:#FAF7F2; font-family:Arial, Helvetica, sans-serif; color:#302921;">

    <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:30px;">

        <div style="width:100%; max-width:480px; background:#FFFFFF; padding:35px; border-radius:18px; border:1px solid #E9E1D7; box-shadow:0 15px 40px rgba(92,69,48,0.08);">

            <!-- HEADER -->
            <div style="text-align:center; margin-bottom:30px;">
                <h2 data-id="Buat Bisnis" data-en="Create Business" style="margin:0; font-size:26px;">Buat Bisnis</h2>
                <p data-id="Tambahkan bisnis untuk mulai mengelola stok." data-en="Add a business to start managing stock." style="color:#81776D; font-size:14px; margin-top:10px;">Tambahkan bisnis untuk mulai mengelola stok.</p>
            </div>

            <form action="api/create-business.php" method="POST" enctype="multipart/form-data">

                <!-- PHOTO -->
                <label data-id="Foto Toko (Opsional)" data-en="Store Photo (Optional)" style="display:block; font-size:13px; margin-bottom:10px;">
                    Foto Toko (Opsional)
                </label>
                <label for="business-photo" id="upload-box" style="width:72px; height:72px; background:#F3E8DC; border-radius:18px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#76553D; overflow:hidden; position:relative; margin-bottom:18px;">
                    <i data-lucide="image-plus" id="upload-icon" style="width:28px; height:28px;"></i>
                    <input id="business-photo" type="file" name="photo" accept="image/*" style="display:none;">
                </label>

                <!-- BUSINESS NAME -->
                <label data-id="Nama Toko" data-en="Store Name" style="display:block; font-size:13px; margin-bottom:7px;">Nama Toko</label>
                <input type="text" name="business_name" data-id-placeholder="Masukkan nama toko" data-en-placeholder="Enter store name" placeholder="Masukkan nama toko" style="width:100%; padding:12px; border:1px solid #E9E1D7; border-radius:9px; margin-bottom:18px; box-sizing:border-box;">

                <!-- CURRENCY -->
                <label data-id="Mata Uang" data-en="Currency" style="display:block; font-size:13px; margin-bottom:7px;">Mata Uang</label>
                <select name="currency" style="width:100%; padding:12px; border:1px solid #E9E1D7; border-radius:9px; margin-bottom:18px; background:white;">
                    <option value="IDR">IDR - Indonesian Rupiah</option>
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="JPY">JPY - Japanese Yen</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="CNY">CNY - Chinese Yuan</option>
                </select>

                <!-- NOTES -->
                <label data-id="Catatan (Opsional)" data-en="Notes (Optional)" style="display:block; font-size:13px; margin-bottom:7px;">Catatan (Opsional)</label>
                <textarea name="notes" data-id-placeholder="Tambahkan catatan bisnis" data-en-placeholder="Add business notes" placeholder="Tambahkan catatan bisnis" style="width:100%; height:90px; padding:12px; border:1px solid #E9E1D7; border-radius:9px; resize:none; box-sizing:border-box; margin-bottom:25px;"></textarea>

                <button type="submit" data-id="Buat Bisnis" data-en="Create Business" style="width:100%; padding:13px; background:#76553D; color:#FFFFFF; border:none; border-radius:9px; font-size:14px; font-weight:600; cursor:pointer;">Buat Bisnis</button>

            </form>

            <p style="text-align:center; margin-top:22px; font-size:13px;">
                <a href="businesses.php" data-id="Kembali ke daftar bisnis" data-en="Back to business list" style="color:#76553D; text-decoration:none;">Kembali ke daftar bisnis</a>
            </p>

        </div>

    </div>

    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
    lucide.createIcons();
    
    const input = document.getElementById("business-photo");
    const box = document.getElementById("upload-box");
    const icon = document.getElementById("upload-icon");

    input.addEventListener("change", function(){
        const file = this.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = function(e){
                icon.style.display = "none";
                const oldPreview = box.querySelector("img");
                if(oldPreview){ oldPreview.remove(); }
                const img = document.createElement("img");
                img.src = e.target.result;
                img.style.width = "100%";
                img.style.height = "100%";
                img.style.objectFit = "cover";
                box.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>

</html>