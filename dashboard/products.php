<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Muat pengelola bahasa
require_once __DIR__ . '/../includes/language.php';

// Muat Mata Uang
require_once __DIR__ . '/../includes/currency.php';

// Pencarian produk
$search = trim($_GET['search'] ?? '');
$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);

// Ambil kategori milik bisnis aktif
$stmt = mysqli_prepare($conn, "SELECT id, category_name FROM categories WHERE business_id = ? ORDER BY category_name ASC");
mysqli_stmt_bind_param($stmt, "i", $business_id);
mysqli_stmt_execute($stmt);
$categories = mysqli_stmt_get_result($stmt);

// Ambil produk beserta nama kategorinya
$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id AND c.business_id = p.business_id WHERE p.business_id = ?";
$params = [$business_id];
$types = "i";

// Filter pencarian nama produk (keyword)
if ($search !== '') {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Filter pencarian dr kategori
if ($category_id !== false && $category_id !== null) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

// Urutkan daftar produk berdasarkan nama secara A-Z
$sql .= " ORDER BY p.product_name ASC";

// Prepare statement query dinamis, menyesuaikan filter, eksekusi dan simpan hasil
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);
?>



<!DOCTYPE html>
<!-- Set bahasa -->
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('products'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>
</head>

<!-- Styling utama layout dan warna latar halaman -->
<body style="width:100%; margin:0; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'products.php';?>

<!-- Layout utama halaman Produk -->
<div style="display:flex; width:100%; height:100vh;">
    <!-- SIDEBAR -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- KONTEN -->
    <main style="margin-left:230px; flex:1; min-width:0; min-height:100vh; display:flex; flex-direction:column;">

        <!-- HEADER -->
        <?php require __DIR__ . '/components/header.php'; ?>

        <!-- Judul dan filter + fitur tabel yang scroll -->
        <section style="padding:32px; display:flex; flex-direction:column; box-sizing:border-box;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:25px; flex-shrink:0;">
                <div>
                    <h1 style="margin:0; font-size:29px;"><?= htmlspecialchars(__('products'), ENT_QUOTES, 'UTF-8') ?></h1>
                    <p style="color:#81776D; margin:10px 0 0;"><?= htmlspecialchars(__('page_products_description'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <button type="button" onclick="openAddProduct()" style="display:flex; align-items:center; gap:8px; padding:12px 17px; background:#76553D; color:white; border:0; border-radius:9px; cursor:pointer;">
                    <i data-lucide="plus" style="width:18px;height:18px;"></i><?= htmlspecialchars(__('add_product'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>

            <!-- PENCARIAN DAN FILTER -->
            <form method="GET" style="display:flex; flex-shrink:0; gap:12px; flex-wrap:wrap; background:white; border:1.5px solid #E9E1D7; border-radius:12px; padding:18px; margin-bottom:20px;">
                <!-- Kirim ID bisnis aktif sebagai parameter tersembunyi -->
                <input type="hidden" name="business_id" value="<?= $business_id ?>">

                <!-- Input pencarian produk berdasarkan nama -->
                <div style="flex:1; min-width:200px; position:relative;">
                    <i data-lucide="search" style="position:absolute; left:12px; top:13px; width:18px; height:18px; color:#948578;"></i>
                    <input type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('search_product'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:12px 12px 12px 39px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                </div>

                <!-- Dropdown filter berdasarkan kategori -->
                <select name="category_id" style="min-width:180px; padding:12px; border:1px solid #E9E1D7; border-radius:8px; background:white; color:#49382D;">
                    <option value=""><?= htmlspecialchars(__('all_categories'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php $stmtModalCategories = mysqli_prepare(
                        $conn,
                        "SELECT id, category_name
                        FROM categories
                        WHERE business_id = ?
                        ORDER BY category_name ASC"
                    );

                    // Parameter ID bisnis ke query kategori, eksekusi, ambil kategori
                    mysqli_stmt_bind_param($stmtModalCategories, "i", $business_id);
                    mysqli_stmt_execute($stmtModalCategories);
                    $modalCategories = mysqli_stmt_get_result($stmtModalCategories);
                    ?>

                    <!-- Mengambil baris nama kategori yang ada di database -->
                    <?php while ($category = mysqli_fetch_assoc($modalCategories)): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $category_id === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Tombol submit filter -->
                <button type="submit" style="padding:12px 18px; background:#76553D; color:white; border:0; border-radius:8px; cursor:pointer;"><?= htmlspecialchars(__('search'), ENT_QUOTES, 'UTF-8') ?></button>
                <!-- Tombol reset -->
                <a href="products.php?business_id=<?= $business_id ?>" style="display:flex; align-items:center; padding:12px; color:#76553D; text-decoration:none;"><?= htmlspecialchars(__('reset'), ENT_QUOTES, 'UTF-8') ?></a>
            </form>

            <!-- TABEL PRODUK -->
            <!-- Container tabel -->
            <div style=" height:500px; overflow-y:auto; overflow-x:auto; position:relative; background:white; border:1.5px solid #E9E1D7; border-radius:12px;">
                <table style="width:100%; border-collapse:collapse; text-align:left; white-space:nowrap;">
                    <!-- Header tabel -->
                    <thead>
                        <!-- Kolom nama produk -->
                        <tr style="background:#F5EFE8; text-align:left;">
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:18%; background:#F5EFE8;"><?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:12%; background:#F5EFE8;"><?= htmlspecialchars(__('category'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:15%; background:#F5EFE8;"><?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:13%; background:#F5EFE8;"><?= htmlspecialchars(__('purchase_price'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:13%; background:#F5EFE8;"><?= htmlspecialchars(__('selling_price'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:7%; background:#F5EFE8;"><?= htmlspecialchars(__('stock'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:12%; background:#F5EFE8;"><?= htmlspecialchars(__('expiration_date'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="position:sticky; top:0; z-index:2; padding:12px; width:10%; background:#F5EFE8;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Cek ketersediaan data produk -->
                        <?php if (mysqli_num_rows($products) > 0): ?>
                            <!-- Looping untuk menampilkan baris data setiap produk -->
                            <?php while ($product = mysqli_fetch_assoc($products)): ?>
                                <tr style="border-top:1px solid #F0E8DF;">
                                    <!-- Data pada kolom -->
                                    <td style="padding:10px 12px;">
                                        <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                                            <!-- Tampilkan foto produk jika ada di server -->
                                            <?php if(!empty($product['photo']) && file_exists(__DIR__ . '/../uploads/products/' . $product['id'] . '/' . $product['photo'])): ?>
                                                <img src="../uploads/products/<?= $product['id'] ?>/<?= htmlspecialchars($product['photo'], ENT_QUOTES, 'UTF-8') ?>" style="width:50px; height:50px; min-width:50px; flex-shrink:0; border-radius:10px; object-fit:cover; border:1px solid #E9E1D7;">
                                                <!-- jika produk tidak memiliki foto pakai inisial nama produk -->
                                            <?php else: ?>
                                                <div style="width:50px; height:50px; min-width:50px; flex-shrink:0; border-radius:10px; background:#F5EFE8; display:flex; align-items:center; justify-content:center;"><i data-lucide="image" style="width:22px;color:#76553D;"></i></div>
                                            <?php endif; ?>
                                            <span style="font-weight:600; display:block; max-width:180px; word-break:break-word; overflow-wrap:anywhere;"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <td style="padding:10px 12px; color:#81776D; font-size:13px"><?= htmlspecialchars($product['category_name'] ?? __('uncategorized'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td title="<?= htmlspecialchars($product['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding:10px 12px; color:#81776D; font-size:13px; overflow-wrap:anywhere;"><?php if (!empty(trim($product['notes'] ?? ''))): ?>
                                        <div style="display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:2; overflow:hidden;"><?= htmlspecialchars($product['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td style="padding:10px 12px; white-space:nowrap;"><?= htmlspecialchars(formatMoneyPHP($product['purchase_price'], $business['currency']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding:10px 12px; white-space:nowrap;"><?= htmlspecialchars(formatMoneyPHP($product['selling_price'], $business['currency']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding:10px 12px;"><?= number_format((int) $product['current_stock']) ?></td>
                                    <td style="padding:10px 12px; color:#81776D;"><?= $product['expiration_date'] ? htmlspecialchars(date('d/m/Y', strtotime($product['expiration_date']))) : '-' ?></td>
                                    <td style="padding:10px 12px;">
                                        <div style="display:flex; gap:8px; align-items:center;">
                                            <!-- Tombol edit & hapus -->
                                            <button type="button" onclick="openEditProduct(<?= $product['id'] ?>)" style="width:32px; height:32px; border:none; background:#F5EFE8; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#76553D;" title="Edit">
                                                <i data-lucide="pencil" style="width:16px;height:16px;"></i>
                                            </button>                                            
                                            <button type="button" onclick="deleteProduct(<?= (int) $product['id'] ?>)" style="width:32px; height:32px; border:none; background:#FBECEC; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#B45F5F;" title="<?= $lang === 'en' ? 'Delete' : 'Hapus' ?>" aria-label="<?= $lang === 'en' ? 'Delete product' : 'Hapus produk' ?>">
                                                <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <!-- Tampilan baris khusus saat data produk tidak ditemukan, dengan menggabungkan 8 kolom -->
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="padding:35px; text-align:center; color:#81776D;"><?= htmlspecialchars(__('no_products'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>
</div>

<!-- PRODUK -->
<!-- Container tambah/edit produk -->
<div id="productModal" style="display:none; position:fixed; inset:0; background:rgba(73,56,45,0.35); z-index:999; align-items:center; justify-content:center; padding:20px;">
    <!-- Kotak dialog -->
    <div style="width:520px; max-height:85vh; background:white; border-radius:16px; padding:25px; box-sizing:border-box; display:flex; flex-direction:column;">
        <!-- Header, Judul (tambah atau edit produk), tombol close -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 id="productModalTitle" style="margin:0;"><?= htmlspecialchars(__('add_product'), ENT_QUOTES, 'UTF-8') ?></h2>
            <button onclick="closeProductModal()" style="border:none; background:none; font-size:22px; cursor:pointer; color:#76553D;">×</button>
        </div>

        <!-- Deskripsi petunjuk pengisian modal -->
        <p id="productModalDescription" style="color:#81776D; margin-top:0;"><?= htmlspecialchars(__('add_product_page_description'), ENT_QUOTES, 'UTF-8') ?></p>

        <!-- Area form -->
        <div style="overflow-y:auto; padding-right:10px; flex:1;">
            <!-- FORM ISI PRODUK -->
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="business_id" value="<?= $business_id ?>">
                <input type="hidden" name="product_id" id="product_id">

                <!-- Foto Produk -->
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:10px; font-weight:600;"><?= htmlspecialchars(__('product_photo'), ENT_QUOTES, 'UTF-8') ?></label>
                    <label for="productPhoto" style="width:100px; height:100px; border-radius:14px; background:#F5EFE8; display:flex; align-items:center; justify-content:center; cursor:pointer; overflow:hidden;"><img id="productPreview" src="" style="width:100%; height:100%; object-fit:cover; display:none;"><i id="uploadIcon" data-lucide="image-plus" style="width:35px; height:35px; color:#76553D;"></i></label>
                    <input type="file" id="productPhoto" name="photo" accept="image/*" style="display:none;">
                </div>

                <!-- Nama Produk -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="product_name" placeholder="<?= htmlspecialchars(__('product_name_placeholder'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                </div>

                <!-- Kode Produk -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('product_code'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="product_code" readonly placeholder="<?= htmlspecialchars(__('product_code_placeholder'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                </div>

                <!-- Kategori -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('category'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="category_id" style="width:100%; padding:12px; border:1px solid #E9E1D7; border-radius:8px; background:white;">
                        <option value=""><?= htmlspecialchars(__('select_category'), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php while($category = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" onclick="toggleCategoryForm()" style="margin-top:10px; border:none; background:none; color:#76553D; cursor:pointer; padding:0; font-weight:600;">+ <?= htmlspecialchars(__('add_new_category'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>

                <!-- Tambah kategori baru -->
                <div id="newCategoryBox" style="display:none; background:#FAF7F2; padding:15px; border-radius:10px; margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('new_category_name'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" id="newCategoryInput" name="new_category" placeholder="<?= htmlspecialchars(__('new_category_name'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px;">
                    <button type="button" id="saveCategoryButton" onclick="saveNewCategory()" style="margin-top:12px; padding:10px 16px; border:0; border-radius:8px; background:#76553D; color:white; font-weight:600; cursor:pointer;">
                        Simpan Kategori
                    </button>
                </div>

                <!-- Harga Beli -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('purchase_price'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="purchase_price" class="currency-input" min="0" placeholder="0" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px;">
                </div>

                <!-- Harga Jual -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('selling_price'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="selling_price" class="currency-input" min="0" placeholder="0" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px;">
                </div>

                <!-- Initial Stock -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('initial_stock'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="number" name="initial_stock" min="0" placeholder="0" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px;">
                </div>

                <!-- Expiration Date -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('expiration_date'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" name="expiration_date" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px;">
                </div>

                <!-- Notes -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?></label>
                    <textarea name="notes" placeholder="<?= htmlspecialchars(__('notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>" rows="4" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px; resize:none; font-family:inherit;"></textarea>
                </div>

                <!-- Toombol Save -->
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px; padding-top:20px; border-top:1px solid #E9E1D7;">
                    <button type="button" onclick="closeProductModal()" style="padding:12px 22px; border-radius:9px; border:1px solid #E9E1D7; background:white; color:#76553D; cursor:pointer; font-weight:600;"><?= htmlspecialchars(__('cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="submit" id="saveProductButton" style="padding:12px 22px; border-radius:9px; border:none; background:#76553D; color:white; cursor:pointer; font-weight:600;"><?= htmlspecialchars(__('save_product'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </form>
        </div>

    </div>
</div>

<!-- ICON -->
<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/currency.js"></script>

<script>
lucide.createIcons();

// Translate bahasa
const translations = {
    add_product_title:"<?= __('add_product_title') ?>",
    add_product_description:"<?= __('add_product_description') ?>",
    edit_product_title:"<?= __('edit_product_title') ?>",
    edit_product_description:"<?= __('edit_product_description') ?>"
}

// Flag penanda status (false = Tambah, true = Edit)
let editMode = false;

// Buka tampilan overlay tambah produk
function openProductModal(){
    document.getElementById('productModal').style.display='flex';
}

// Tutup tambah produk (close) dan reset isi form
function closeProductModal(){
    document.getElementById('productModal').style.display='none';
    editMode = false;
    resetProductForm();
}

// Cek form isi produk
document.getElementById('productForm').addEventListener('submit', function(e){
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);

    // Kirim request ke endpoint API sesuai mode form
    fetch( 
        editMode 
        ? '../api/update-product.php'
        : '../api/create-product.php', 
    {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Tampilkan respons server dan reload jika sukses
        if(data.status === 'success'){
            alert(data.message);
            closeProductModal();
            form.reset();
            location.reload();
        }else{
            alert(data.message || 'Gagal menyimpan produk');
        }
    })
    .catch(error => {
        console.error(error);
        alert('Terjadi kesalahan');
    });
});

// Menambahkan event listener saat berkas foto produk dipilih oleh pengguna
document.getElementById('productPhoto').addEventListener('change', function(){
    // Mengambil berkas pertama yang diunggah dari input file
    const file = this.files[0];

    // Ambil file foto yang dipilih
    if(file){
        // Buat objek pembaca file lokal
        const reader = new FileReader();

        reader.onload = function(e){
            // Ambil elemen foto preview
            const preview = document.getElementById('productPreview');
            const icon = document.getElementById('uploadIcon');

            // Set sumber gambar dari hasil bacaan file
            preview.src = e.target.result;
            // Tampilkan foto preview dan sembunyikan ikon upload
            preview.style.display = 'block';
            icon.style.display = 'none';
        }
        // Baca file gambar
        reader.readAsDataURL(file);
    }
});

// Toggle tampilkan/sembunyikan box input kategori baru
function toggleCategoryForm(){
    const box = document.getElementById('newCategoryBox');
    // Tampilkan kotak jika sedang tersembunyi, atau sembunyikan jika sedang tampil
    if(box.style.display === 'none'){
        box.style.display = 'block';
    }else{
        box.style.display = 'none';
    }

}

// Simpan kategori baru ke server
async function saveNewCategory() {
    // Ambil elemen input, tombol simpan, dan nilai nama kategori
    const input = document.getElementById('newCategoryInput');
    const button = document.getElementById('saveCategoryButton');
    const categoryName = input.value.trim();

    // Validasi input nama kategori tidak boleh kosong
    if (!categoryName) {
        alert('Nama kategori wajib diisi.');
        input.focus();
        return;
    }

    // Siapkan data formulir untuk dikirim ke API
    const formData = new FormData();
    formData.append('business_id', '<?= (int) $business_id ?>');
    formData.append('category_name', categoryName);

    // Nonaktifkan tombol dan ubah teks saat proses menyimpan
    button.disabled = true;
    button.textContent = 'Menyimpan...';

    try {
        // Kirim data kategori baru
        const response = await fetch('../api/create-category.php', {
            method: 'POST',
            body: formData
        });

        // Konversi respon server ke format JSON
        const result = await response.json();

        // Cek jika HTTP status gagal atau status respon bukan success
        if (!response.ok || result.status !== 'success') {
            alert(result.message || 'Kategori gagal disimpan.');
            return;
        }

        // Ambil data kategori baru dan cari elemen dropdown kategori pada form produk
        const category = result.category;
        const categorySelect = document.querySelector(
            '#productForm select[name="category_id"]'
        );

        // Buat dan tambahkan opsi kategori baru ke dalam dropdown
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.category_name;

        categorySelect.appendChild(option);
        categorySelect.value = category.id;

        // Kosongkan input dan sembunyikan kotak input kategori
        input.value = '';
        document.getElementById('newCategoryBox').style.display = 'none';

        // Tampilkan pesan sukses dari server
        alert(result.message);
    } catch (error) {
        // Jika terjadi error koneksi atau sistem
        console.error(error);
        alert('Terjadi kesalahan saat menyimpan kategori.');
    } finally {
        // Kembalikan tombol ke status aktif setelah proses selesai
        button.disabled = false;
        button.textContent = 'Simpan Kategori';
    }
}

// Bersihkan dan kembalikan semua isi form produk ke kondisi awal
function resetProductForm(){
    // Ambil elemen form utama produk
    const form = document.getElementById('productForm');

    // Kosongkan seluruh input bawaan di dalam form
    form.reset();

    // Kosongkan ID produk untuk memastikan form kembali ke mode Tambah
    document.getElementById('product_id').value = "";

    // Ambil elemen priview dan ikon upload
    const preview = document.getElementById('productPreview');
    const icon = document.getElementById('uploadIcon');

    // Sembunyikan dan kosongkan preview jika elemennya ada
    if(preview){
        preview.src = "";
        preview.style.display = "none";
    }

    // Tampilkan kembali ikon upload foto
    if(icon){
        icon.style.display = "block";
    }

    // Sembunyikan kotak input kategori baru jika sedang terbuka
    const categoryBox = document.getElementById('newCategoryBox');
    if(categoryBox){
        categoryBox.style.display = "none";
    }
}

// Buka modal formulir dalam mode Tambah Produk
function openAddProduct(){
    // Atur status ke mode tambah produk
    editMode = false;

    // Bersihkan seluruh isi formulir dari data sebelumnya
    resetProductForm();

    // Tampilkan jendela modal ke layar
    document.getElementById('productModal').style.display='flex';

    // Judul dan deskripsi sesuai translate
    document.getElementById('productModalTitle').innerText = translations.add_product_title;
    document.getElementById('productModalDescription').innerText = translations.add_product_description;

    // Kosongkan bidang input kode produk
    document.querySelector('[name="product_code"]').value = "";
}

// Isi form untuk mode Edit Produk
function openEditProduct(id){
    // Aktifkan flag mode edit produk
    editMode = true;

    // Ambil detail data produk dari server berdasarkan ID
    fetch('../api/get-product.php?id=' + id)

    .then(response => response.json())
    
    // Jika pengambilan data berhasil, masukkan data ke dalam bidang form
    .then(result => {
        if(result.status === 'success'){
            const product = result.data;
            document.getElementById('product_id').value = product.id;

            // Buka tampilan form edit
            openProductModal();

            // Set judul dan isi value form dari data produk
            document.getElementById('productModalTitle').innerText = translations.edit_product_title;
            document.getElementById('productModalDescription').innerText = translations.edit_product_description;

            // Isi setiap input formulir dengan data produk yang diperoleh
            document.querySelector('[name="product_name"]').value = product.product_name;
            document.querySelector('[name="product_code"]').value = product.product_code ?? "";
            document.querySelector('#productForm select[name="category_id"]').value = product.category_id ?? "";
            document.querySelector('[name="notes"]').value = product.notes ?? "";
            document.querySelector('[name="purchase_price"]').value = product.purchase_price;
            document.querySelector('[name="selling_price"]').value = product.selling_price;
            document.querySelector('[name="initial_stock"]').value = product.initial_stock;
            document.querySelector('[name="expiration_date"]').value = product.expiration_date ?? "";
        }
    });
}

// Mengambil jenis mata uang aktif
const activeCurrency = <?= json_encode($business['currency']) ?>;
// Membersihkan dan memformat input harga agar hanya berisi angka dan desimal yang valid
function normalizePriceInput(input) {
    // Ambil nilai input dan hapus spasi di awal/akhir
    let value = input.value.trim();

    // Hapus seluruh karakter selain angka, titik, dan koma
    value = value.replace(/[^0-9.,]/g, '');

    // Cari posisi pemisah desimal terakhir (titik atau koma)
    const separatorIndex = Math.max(
        value.lastIndexOf('.'),
        value.lastIndexOf(',')
    );

    // Pisahkan bagian angka bulat dan desimal jika pemisah ditemukan
    if (separatorIndex !== -1) {
        // Ambil bagian angka bulat dan bersihkan pemisah tambahan
        const integerPart = value
            .slice(0, separatorIndex)
            .replace(/[.,]/g, '');

        // Ambil maksimal 2 digit angka desimal di belakang koma/titik
        const decimalPart = value
            .slice(separatorIndex + 1)
            .replace(/[.,]/g, '')
            .slice(0, 2);

        // Gabungkan kembali angka bulat dan desimal dengan pemisah titik
        value = integerPart + '.' + decimalPart;
    } else {
        // Hapus pemisah jika tidak ada desimal yang valid
        value = value.replace(/[.,]/g, '');
    }

    // Setel kembali nilai input yang sudah dibersihkan
    input.value = value;
}

// Pasang penangan input pada seluruh elemen bertag currency-input untuk menormalisasi harga
document.querySelectorAll('.currency-input').forEach(input => {
    input.addEventListener('input', function () {
        // Simpan posisi kursor sebelum teks dinormalisasi
        const cursorPosition = this.selectionStart;
        
        // Bersihkan dan format ulang nilai harga yang diketik pengguna
        normalizePriceInput(this);
    });
});

// Hapus data produk secara permanen dari database
async function deleteProduct(productId) {
    // Tampilkan konfirmasi penghapusan sesuai pilihan bahasa aktif
    const confirmed = confirm(
        <?= json_encode($lang === 'en'
            ? 'Are you sure you want to permanently delete this product?'
            : 'Apakah kamu yakin ingin menghapus produk ini secara permanen?') ?>
    );

    // Batal jalankan fungsi jika pengguna memilih batal
    if (!confirmed) {
        return;
    }

    // Siapkan data ID produk dan ID bisnis untuk dikirim ke API
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('business_id', <?= (int) $business_id ?>);

    try {
        // Kirim permintaan hapus produk via Fetch API
        const response = await fetch('../api/delete-product.php', {
            method: 'POST',
            body: formData
        });

        // Konversi respons dari server ke format JSON
        const result = await response.json();

        // Cek jika status respons gagal atau bermasalah
        if (!response.ok || result.status !== 'success') {
            alert(result.message || 'Produk gagal dihapus.');
            return;
        }

        // Tampilkan pesan sukses dan muat ulang halaman
        alert(result.message);
        window.location.reload();
    } catch (error) {
        // Tangani jika terjadi error koneksi atau server
        console.error(error);
        alert('Terjadi kesalahan saat menghapus produk.');
    }
}
</script>

</body>
</html>