<!-- Modal Tambah / Edit Produk -->
<div id="productModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h2 id="productModalTitle" style="margin:0;"><?= htmlspecialchars(__('add_product'), ENT_QUOTES, 'UTF-8') ?></h2>
            <button type="button" onclick="closeProductModal()" class="modal-close-btn">×</button>
        </div>

        <p id="productModalDescription" style="color:#81776D; margin-top:0;">
            <?= htmlspecialchars(__('add_product_page_description'), ENT_QUOTES, 'UTF-8') ?>
        </p>

        <div class="modal-scroll-body">
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="business_id" value="<?= (int) $business_id ?>">
                <input type="hidden" name="product_id" id="product_id">

                <!-- Foto Produk -->
                <div class="form-group">
                    <label class="form-label-lg"><?= htmlspecialchars(__('product_photo'), ENT_QUOTES, 'UTF-8') ?></label>
                    <label for="productPhoto" class="upload-box-lg">
                        <img id="productPreview" src="" class="img-cover" style="display:none;" alt="Preview">
                        <i id="uploadIcon" data-lucide="image-plus" style="width:35px; height:35px; color:#76553D;"></i>
                    </label>
                    <input type="file" id="productPhoto" name="photo" accept="image/*" style="display:none;">
                </div>

                <!-- Nama & Kode Produk -->
                <div class="form-group">
                    <label class="form-label-lg"><?= htmlspecialchars(__('product_name'), ENT_QUOTES, 'UTF-8') ?> <span style="color:red;">*</span></label>
                    <input type="text" name="product_name" maxlength="50" class="form-control"
                        placeholder="<?= htmlspecialchars(__('product_name_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label-lg"><?= htmlspecialchars(__('product_code'), ENT_QUOTES, 'UTF-8') ?> <span style="color:red;">*</span></label>
                    <input type="text" name="product_code" required maxlength="20" class="form-control"
                        placeholder="<?= htmlspecialchars(__('product_code_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Kategori & Tambah Kategori Baru -->
                <div class="form-group">
                    <label class="form-label-lg"><?= htmlspecialchars(__('category'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="category_id" class="form-control">
                        <option value=""><?= htmlspecialchars(__('select_category'), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php mysqli_data_seek($categories, 0); while ($category = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" onclick="toggleCategoryForm()" style="margin-top:10px; border:none; background:none; color:#76553D; cursor:pointer; padding:0; font-weight:600;">
                        + <?= htmlspecialchars(__('add_new_category'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>

                <div id="newCategoryBox" style="display:none; background:#FAF7F2; padding:15px; border-radius:10px; margin-bottom:18px;">
                    <label class="form-label-lg"><?= htmlspecialchars(__('new_category_name'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" id="newCategoryInput" name="new_category" maxlength="40" class="form-control"
                        placeholder="<?= htmlspecialchars(__('new_category_name'), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" id="saveCategoryButton" onclick="saveNewCategory()" class="btn btn-primary btn-sm" style="margin-top:12px;">
                        <?= htmlspecialchars(__('save_category'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>

                <!-- Harga, Stok, Tanggal Kedaluwarsa, & Catatan -->
                <div class="form-group">
                    <label class="form-label-lg"><?= htmlspecialchars(__('purchase_price'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="purchase_price" class="form-control currency-input" min="0" placeholder="0">
                </div>

                <div class="form-group">
                    <label class="form-label-lg"><?= htmlspecialchars(__('selling_price'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="selling_price" class="form-control currency-input" min="0" placeholder="0">
                </div>

                <div class="form-group">
                    <label class="form-label-lg"><?= htmlspecialchars(__('initial_stock'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="number" name="initial_stock" class="form-control" min="0" placeholder="0">
                </div>

                <div class="form-group">
                    <label class="form-label-lg"><?= htmlspecialchars(__('expiration_date'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" name="expiration_date" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label-lg"><?= htmlspecialchars(__('notes'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="notes" maxlength="40" class="form-control"
                        placeholder="<?= htmlspecialchars(__('notes_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeProductModal()" class="btn btn-outline"><?= htmlspecialchars(__('cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="submit" id="saveProductButton" class="btn btn-primary"><?= htmlspecialchars(__('save_product'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
