/**
 * Modul Manajemen Produk & Kategori (Modal Tambah/Edit, Preview Foto, Format Harga, & Hapus Produk)
 */
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

let editMode = false;

function openProductModal() {
    const modal = document.getElementById('productModal');
    if (modal) modal.style.display = 'flex';
}

function closeProductModal() {
    const modal = document.getElementById('productModal');
    if (modal) modal.style.display = 'none';
    editMode = false;
    resetProductForm();
}

// Submit form tambah atau update produk
document.getElementById('productForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const endpoint = editMode ? '../api/update-product.php' : '../api/create-product.php';

    try {
        const response = await fetch(endpoint, { method: 'POST', body: new FormData(this) });
        const data = await response.json();

        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeProductModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || APP_TRANSLATIONS?.product_save_error || 'Error', 'error');
        }
    } catch (error) {
        console.error(error);
        showToast(APP_TRANSLATIONS?.connection_error || 'Connection error', 'error');
    }
});

// Pratinjau foto produk saat file dipilih
document.getElementById('productPhoto')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        const preview = document.getElementById('productPreview');
        const icon = document.getElementById('uploadIcon');
        if (preview) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        if (icon) icon.style.display = 'none';
    };
    reader.readAsDataURL(file);
});

function toggleCategoryForm() {
    const box = document.getElementById('newCategoryBox');
    if (box) box.style.display = box.style.display === 'none' ? 'block' : 'none';
}

// Simpan kategori baru secara asinkron
async function saveNewCategory() {
    const input = document.getElementById('newCategoryInput');
    const button = document.getElementById('saveCategoryButton');
    const categoryName = input.value.trim();

    if (!categoryName) {
        showToast(APP_TRANSLATIONS.category_name_required, 'error');
        input.focus();
        return;
    }

    const formData = new FormData();
    formData.append('business_id', CURRENT_BUSINESS_ID);
    formData.append('category_name', categoryName);

    button.disabled = true;
    button.textContent = APP_TRANSLATIONS.saving;

    try {
        const response = await fetch('../api/create-category.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            showToast(result.message || APP_TRANSLATIONS.category_save_failed, 'error');
            return;
        }

        const categorySelect = document.querySelector('#productForm select[name="category_id"]');
        if (categorySelect && result.category) {
            const option = new Option(result.category.category_name, result.category.id, true, true);
            categorySelect.add(option);
        }

        input.value = '';
        document.getElementById('newCategoryBox').style.display = 'none';
        showToast(result.message, 'success');
    } catch (error) {
        console.error(error);
        showToast(APP_TRANSLATIONS.category_error, 'error');
    } finally {
        button.disabled = false;
        button.textContent = APP_TRANSLATIONS.save_category;
    }
}

function resetProductForm() {
    const form = document.getElementById('productForm');
    if (!form) return;

    form.reset();
    document.getElementById('product_id').value = '';

    const preview = document.getElementById('productPreview');
    const icon = document.getElementById('uploadIcon');
    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
    }
    if (icon) icon.style.display = 'block';

    const categoryBox = document.getElementById('newCategoryBox');
    if (categoryBox) categoryBox.style.display = 'none';
}

function openAddProduct() {
    editMode = false;
    resetProductForm();
    openProductModal();

    document.getElementById('productModalTitle').innerText = APP_TRANSLATIONS.add_product_title;
    document.getElementById('productModalDescription').innerText = APP_TRANSLATIONS.add_product_description;
}

// Ambil detail produk dari server dan tampilkan di modal edit
async function openEditProduct(id) {
    editMode = true;
    try {
        const response = await fetch('../api/get-product.php?id=' + id);
        const result = await response.json();
        if (result.status !== 'success') return;

        const p = result.data;
        document.getElementById('product_id').value = p.id;
        openProductModal();

        document.getElementById('productModalTitle').innerText = APP_TRANSLATIONS.edit_product_title;
        document.getElementById('productModalDescription').innerText = APP_TRANSLATIONS.edit_product_description;

        const fields = ['product_name', 'product_code', 'category_id', 'notes', 'purchase_price', 'selling_price', 'initial_stock', 'expiration_date'];
        fields.forEach(name => {
            const el = document.querySelector(`#productForm [name="${name}"]`);
            if (el) el.value = p[name] ?? '';
        });
    } catch (error) {
        console.error(error);
    }
}

// Normalisasi input harga beli & harga jual
document.querySelectorAll('.currency-input').forEach(input => {
    input.addEventListener('input', function () {
        let val = this.value.trim().replace(/[^0-9.,]/g, '');
        const sepIdx = Math.max(val.lastIndexOf('.'), val.lastIndexOf(','));
        if (sepIdx !== -1) {
            const intPart = val.slice(0, sepIdx).replace(/[.,]/g, '');
            const decPart = val.slice(sepIdx + 1).replace(/[.,]/g, '').slice(0, 2);
            val = intPart + '.' + decPart;
        } else {
            val = val.replace(/[.,]/g, '');
        }
        this.value = val;
    });
});

// Hapus produk setelah konfirmasi pengguna
async function deleteProduct(productId) {
    const confirmed = await showConfirm(
        APP_TRANSLATIONS.delete_product_confirm,
        APP_TRANSLATIONS.delete,
        APP_TRANSLATIONS.cancel,
        true
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('business_id', CURRENT_BUSINESS_ID);

    try {
        const response = await fetch('../api/delete-product.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            showToast(result.message || APP_TRANSLATIONS.product_delete_failed, 'error');
            return;
        }
        showToast(result.message, 'success');
        setTimeout(() => window.location.reload(), 1000);
    } catch (error) {
        console.error(error);
        showToast(APP_TRANSLATIONS.product_delete_error, 'error');
    }
}
