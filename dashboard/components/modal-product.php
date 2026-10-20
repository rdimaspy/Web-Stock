<!-- MODAL TAMBAH / EDIT PRODUK -->
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
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?><span style="color:red;"> *</span></label>
                    <input type="text" name="product_name" placeholder="<?= htmlspecialchars(__('product_name_placeholder'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
                </div>

                <!-- Kode Produk -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?= htmlspecialchars(__('product_code'), ENT_QUOTES, 'UTF-8') ?> <span style="color:red;">*</span></label>
                    <input type="text" name="product_code" required placeholder="<?= htmlspecialchars(__('product_code_placeholder'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #E9E1D7; border-radius:8px; outline-color:#B99A7D;">
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
                        <?= htmlspecialchars(__('save_category'), ENT_QUOTES, 'UTF-8') ?>
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
                
                <!-- Notes Produk -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600; font-size:14px;"><?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="notes" maxlength="30" placeholder="<?= htmlspecialchars(__('notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #E9E1D7; border-radius:8px; font-size:14px; outline-color:#B99A7D;">
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