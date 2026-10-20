// Inisialisasi ikon Lucide
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

// Flag penanda status (false = Tambah, true = Edit)
let editMode = false;

// Buka tampilan overlay modal produk
function openProductModal(){
    const modal = document.getElementById('productModal');
    if (modal) modal.style.display = 'flex';
}

// Tutup modal produk dan reset isi form
function closeProductModal(){
    const modal = document.getElementById('productModal');
    if (modal) modal.style.display = 'none';
    editMode = false;
    resetProductForm();
}

// Menangani pengiriman form simpan/update produk
document.getElementById('productForm')?.addEventListener('submit', async function(e){
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);

    try {
        const response = await fetch( 
            editMode ? '../api/update-product.php' : '../api/create-product.php', 
            {
                method: 'POST',
                body: formData
            }
        );

        const data = await response.json();

        if (data.status === 'success'){
            showToast(data.message, 'success');
            closeProductModal();
            form.reset();

            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(data.message || (typeof APP_TRANSLATIONS !== 'undefined' ? APP_TRANSLATIONS.product_save_error : 'Error'), 'error');
        }
    } catch (error) {
        console.error(error);
        showToast(typeof APP_TRANSLATIONS !== 'undefined' ? APP_TRANSLATIONS.connection_error : 'Connection error', 'error');
    }
});

// Event listener preview foto produk
document.getElementById('productPhoto')?.addEventListener('change', function(){
    const file = this.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(e){
            const preview = document.getElementById('productPreview');
            const icon = document.getElementById('uploadIcon');

            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            if (icon) icon.style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
});

// Toggle tampilkan/sembunyikan box input kategori baru
function toggleCategoryForm(){
    const box = document.getElementById('newCategoryBox');
    if(box){
        box.style.display = (box.style.display === 'none') ? 'block' : 'none';
    }
}

// Menyimpan kategori baru ke server secara asinkron
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
        const response = await fetch('../api/create-category.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            showToast(result.message || APP_TRANSLATIONS.category_save_failed, 'error');
            return;
        }

        const category = result.category;
        const categorySelect = document.querySelector('#productForm select[name="category_id"]');

        if (categorySelect) {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.category_name;
            categorySelect.appendChild(option);
            categorySelect.value = category.id;
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

// Reset semua isi form produk
function resetProductForm(){
    const form = document.getElementById('productForm');
    if (!form) return;

    form.reset();
    document.getElementById('product_id').value = "";

    const preview = document.getElementById('productPreview');
    const icon = document.getElementById('uploadIcon');

    if(preview){
        preview.src = "";
        preview.style.display = "none";
    }
    if(icon){
        icon.style.display = "block";
    }

    const categoryBox = document.getElementById('newCategoryBox');
    if(categoryBox){
        categoryBox.style.display = "none";
    }
}

// Buka modal dalam mode Tambah
function openAddProduct(){
    editMode = false;
    resetProductForm();
    openProductModal();

    document.getElementById('productModalTitle').innerText = APP_TRANSLATIONS.add_product_title;
    document.getElementById('productModalDescription').innerText = APP_TRANSLATIONS.add_product_description;
    
    const codeInput = document.querySelector('[name="product_code"]');
    if (codeInput) codeInput.value = "";
}

// Buka modal dalam mode Edit
function openEditProduct(id){
    editMode = true;

    fetch('../api/get-product.php?id=' + id)
    .then(response => response.json())
    .then(result => {
        if(result.status === 'success'){
            const product = result.data;
            document.getElementById('product_id').value = product.id;

            openProductModal();

            document.getElementById('productModalTitle').innerText = APP_TRANSLATIONS.edit_product_title;
            document.getElementById('productModalDescription').innerText = APP_TRANSLATIONS.edit_product_description;

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

// Normalisasi input harga
function normalizePriceInput(input) {
    let value = input.value.trim();
    value = value.replace(/[^0-9.,]/g, '');

    const separatorIndex = Math.max(value.lastIndexOf('.'), value.lastIndexOf(','));

    if (separatorIndex !== -1) {
        const integerPart = value.slice(0, separatorIndex).replace(/[.,]/g, '');
        const decimalPart = value.slice(separatorIndex + 1).replace(/[.,]/g, '').slice(0, 2);
        value = integerPart + '.' + decimalPart;
    } else {
        value = value.replace(/[.,]/g, '');
    }

    input.value = value;
}

document.querySelectorAll('.currency-input').forEach(input => {
    input.addEventListener('input', function () {
        normalizePriceInput(this);
    });
});

// Menghapus data produk
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
        const response = await fetch('../api/delete-product.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            showToast(result.message || APP_TRANSLATIONS.product_delete_failed, 'error');
            return;
        }

        showToast(result.message, 'success');
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } catch (error) {
        console.error(error);
        showToast(APP_TRANSLATIONS.product_delete_error, 'error');
    }
}