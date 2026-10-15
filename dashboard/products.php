<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

include "../config/database.php";

// Muat pengelola bahasa
require_once __DIR__ . '/../includes/language.php';

// Muat Mata Uang
require_once __DIR__ . '/../includes/currency.php';

// Validasi bisnis yang dipilih
$business_id = filter_input(INPUT_GET, 'business_id', FILTER_VALIDATE_INT);
if (!$business_id) {
    header("Location: ../businesses.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, business_name, currency FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $business_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

$business_result = mysqli_stmt_get_result($stmt);
$business = mysqli_fetch_assoc($business_result);

if (!$business) {
    http_response_code(404);
    exit("Bisnis tidak ditemukan atau kamu tidak memiliki akses.");
}

// Pencarian produk
$search = trim($_GET['search'] ?? '');
$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);

// Ambil kategori milik bisnis aktif
$stmt = mysqli_prepare($conn, "SELECT id, category_name FROM categories WHERE business_id = ? ORDER BY category_name ASC");
mysqli_stmt_bind_param($stmt, "i", $business_id);
mysqli_stmt_execute($stmt);
$categories = mysqli_stmt_get_result($stmt);

// Ambil produk
$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id AND c.business_id = p.business_id WHERE p.business_id = ?";
$params = [$business_id];
$types = "i";

if ($search !== '') {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if ($category_id !== false && $category_id !== null) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$sql .= " ORDER BY p.product_name ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('products'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>
</head>

<body style="width:100%; margin:0; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'products.php';?>

<!-- Layout utama halaman Produk -->
<div style="display:flex; width:100%; height:100vh;">

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
                <input type="hidden" name="business_id" value="<?= $business_id ?>">

                <div style="flex:1; min-width:200px; position:relative;">
                    <i data-lucide="search" style="position:absolute; left:12px; top:13px; width:18px; height:18px; color:#948578;"></i>
                    <input type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('search_product'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:12px 12px 12px 39px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                </div>

                <select name="category_id" style="min-width:180px; padding:12px; border:1px solid #E9E1D7; border-radius:8px; background:white; color:#49382D;">
                    <option value=""><?= htmlspecialchars(__('all_categories'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php $stmtModalCategories = mysqli_prepare(
                        $conn,
                        "SELECT id, category_name
                        FROM categories
                        WHERE business_id = ?
                        ORDER BY category_name ASC"
                    );

                    mysqli_stmt_bind_param($stmtModalCategories, "i", $business_id);
                    mysqli_stmt_execute($stmtModalCategories);

                    $modalCategories = mysqli_stmt_get_result($stmtModalCategories);
                    ?>

                    <?php while ($category = mysqli_fetch_assoc($modalCategories)): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $category_id === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" style="padding:12px 18px; background:#76553D; color:white; border:0; border-radius:8px; cursor:pointer;"><?= htmlspecialchars(__('search'), ENT_QUOTES, 'UTF-8') ?></button>
                <a href="products.php?business_id=<?= $business_id ?>" style="display:flex; align-items:center; padding:12px; color:#76553D; text-decoration:none;"><?= htmlspecialchars(__('reset'), ENT_QUOTES, 'UTF-8') ?></a>
            </form>

            <!-- TABEL PRODUK -->
            <div style=" height:500px; overflow-y:auto; overflow-x:auto; position:relative; background:white; border:1.5px solid #E9E1D7; border-radius:12px;">
                <table style="width:100%; border-collapse:collapse; text-align:left; white-space:nowrap;">
                    <thead>
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
                        <?php if (mysqli_num_rows($products) > 0): ?>
                            <?php while ($product = mysqli_fetch_assoc($products)): ?>
                                <tr style="border-top:1px solid #F0E8DF;">
                                    <td style="padding:10px 12px;">
                                        <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                                            <?php if(!empty($product['photo']) && file_exists(__DIR__ . '/../uploads/products/' . $product['id'] . '/' . $product['photo'])): ?>
                                                <img src="../uploads/products/<?= $product['id'] ?>/<?= htmlspecialchars($product['photo'], ENT_QUOTES, 'UTF-8') ?>" style="width:50px; height:50px; min-width:50px; flex-shrink:0; border-radius:10px; object-fit:cover; border:1px solid #E9E1D7;">
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

<!-- Produk -->
<div id="productModal" style="display:none; position:fixed; inset:0; background:rgba(73,56,45,0.35); z-index:999; align-items:center; justify-content:center; padding:20px;">
    <div style="width:520px; max-height:85vh; background:white; border-radius:16px; padding:25px; box-sizing:border-box; display:flex; flex-direction:column;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 id="productModalTitle" style="margin:0;"><?= htmlspecialchars(__('add_product'), ENT_QUOTES, 'UTF-8') ?></h2>
            <button onclick="closeProductModal()" style="border:none; background:none; font-size:22px; cursor:pointer; color:#76553D;">×</button>
        </div>

        <p id="productModalDescription" style="color:#81776D; margin-top:0;"><?= htmlspecialchars(__('add_product_page_description'), ENT_QUOTES, 'UTF-8') ?></p>

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

<script src="https://unpkg.com/lucide@latest"></script>
<script src="../assets/js/currency.js"></script>

<script>

// translate bahasa
const translations = {
    add_product_title:
    "<?= __('add_product_title') ?>",

    add_product_description:
    "<?= __('add_product_description') ?>",

    edit_product_title:
    "<?= __('edit_product_title') ?>",

    edit_product_description:
    "<?= __('edit_product_description') ?>"
};

</script>

<script>
lucide.createIcons();

let editMode = false;

function openProductModal(){
    document.getElementById('productModal').style.display='flex';
}

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

// Foto Produk
document.getElementById('productPhoto').addEventListener('change', function(){
    const file = this.files[0];

    if(file){
        const reader = new FileReader();

        reader.onload = function(e){
            const preview = document.getElementById('productPreview');
            const icon = document.getElementById('uploadIcon');

            preview.src = e.target.result;
            preview.style.display = 'block';

            icon.style.display = 'none';
        }

        reader.readAsDataURL(file);

    }

});

// Kaategori
function toggleCategoryForm(){
    const box = document.getElementById('newCategoryBox');

    if(box.style.display === 'none'){
        box.style.display = 'block';
    }else{
        box.style.display = 'none';
    }

}

// Simpan kategori baru
async function saveNewCategory() {
    const input = document.getElementById('newCategoryInput');
    const button = document.getElementById('saveCategoryButton');
    const categoryName = input.value.trim();

    if (!categoryName) {
        alert('Nama kategori wajib diisi.');
        input.focus();
        return;
    }

    const formData = new FormData();
    formData.append('business_id', '<?= (int) $business_id ?>');
    formData.append('category_name', categoryName);

    button.disabled = true;
    button.textContent = 'Menyimpan...';

    try {
        const response = await fetch('../api/create-category.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            alert(result.message || 'Kategori gagal disimpan.');
            return;
        }

        const category = result.category;
        const categorySelect = document.querySelector(
            '#productForm select[name="category_id"]'
        );

        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.category_name;

        categorySelect.appendChild(option);
        categorySelect.value = category.id;

        input.value = '';
        document.getElementById('newCategoryBox').style.display = 'none';

        alert(result.message);
    } catch (error) {
        console.error(error);
        alert('Terjadi kesalahan saat menyimpan kategori.');
    } finally {
        button.disabled = false;
        button.textContent = 'Simpan Kategori';
    }
}


function resetProductForm(){
    const form = document.getElementById('productForm');

    form.reset();

    document.getElementById('product_id').value = "";

    // reset preview foto
    const preview = document.getElementById('productPreview');
    const icon = document.getElementById('uploadIcon');

    if(preview){
        preview.src = "";
        preview.style.display = "none";
    }

    if(icon){
        icon.style.display = "block";
    }

    // tutup form kategori baru
    const categoryBox = document.getElementById('newCategoryBox');
    if(categoryBox){
        categoryBox.style.display = "none";
    }
}

// Tambah Produk
function openAddProduct(){
    editMode = false;
    resetProductForm();

    document.getElementById('productModal').style.display='flex';
    document.getElementById('productModalTitle').innerText = translations.add_product_title;
    document.getElementById('productModalDescription').innerText = translations.add_product_description;
    document.querySelector('[name="product_code"]').value = "";
}

// Edit produk
function openEditProduct(id){
    editMode = true;
    fetch('../api/get-product.php?id=' + id)

    .then(response => response.json())
    
    .then(result => {
        if(result.status === 'success'){
            const product = result.data;
            document.getElementById('product_id').value = product.id;

            openProductModal();

            document.getElementById('productModalTitle').innerText = translations.edit_product_title;
            document.getElementById('productModalDescription').innerText = translations.edit_product_description;

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

// Penulisan mata Uang
const activeCurrency = <?= json_encode($business['currency']) ?>;

function normalizePriceInput(input) {
    let value = input.value.trim();

    // mata uang 0., satu pemisah desimal, dan maksimal dua angka desimal.
    value = value.replace(/[^0-9.,]/g, '');

    const separatorIndex = Math.max(
        value.lastIndexOf('.'),
        value.lastIndexOf(',')
    );

    if (separatorIndex !== -1) {
        const integerPart = value
            .slice(0, separatorIndex)
            .replace(/[.,]/g, '');

        const decimalPart = value
            .slice(separatorIndex + 1)
            .replace(/[.,]/g, '')
            .slice(0, 2);

        value = integerPart + '.' + decimalPart;
    } else {
        value = value.replace(/[.,]/g, '');
    }

    input.value = value;
}

document.querySelectorAll('.currency-input').forEach(input => {
    input.addEventListener('input', function () {
        const cursorPosition = this.selectionStart;
        normalizePriceInput(this);
    });
});

// Delete Produk
async function deleteProduct(productId) {
    const confirmed = confirm(
        <?= json_encode($lang === 'en'
            ? 'Are you sure you want to permanently delete this product?'
            : 'Apakah kamu yakin ingin menghapus produk ini secara permanen?') ?>
    );

    if (!confirmed) {
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('business_id', <?= (int) $business_id ?>);

    try {
        const response = await fetch('../api/delete-product.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            alert(result.message || 'Produk gagal dihapus.');
            return;
        }

        alert(result.message);
        window.location.reload();
    } catch (error) {
        console.error(error);
        alert('Terjadi kesalahan saat menghapus produk.');
    }
}


</script>

</body>
</html>