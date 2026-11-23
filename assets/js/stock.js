/**
 * Modul Transaksi Stok Terpusat (Stock In, Stock Out, & Stock Audit)
 * Menangani filter kategori produk, kalkulasi stok/harga/selisih, dan penyimpanan API
 */
(function () {
    function initStockModule() {
        const cfg = window.STOCK_CONFIG || {};
        const productSelect = document.getElementById('product_id');
        const categoryFilter = document.getElementById('category_filter');
        const quantityInput = document.getElementById('quantity');
        const physicalInput = document.getElementById('physical_stock');

        let currentPrice = 0;

        // Simpan cache seluruh opsi produk untuk penyaringan berdasarkan kategori
        const allProductOptions = productSelect
            ? Array.from(productSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                stock: opt.dataset.stock || 0,
                price: opt.dataset.price || 0,
                category: opt.dataset.category || 'UNCATEGORIZED'
            }))
            : [];

        // Format nominal sesuai mata uang bisnis
        function formatCurrencyValue(value) {
            const currency = cfg.currency || 'IDR';
            try {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: currency,
                    minimumFractionDigits: 0
                }).format(value);
            } catch (e) {
                return currency + ' ' + Number(value).toLocaleString('en-US');
            }
        }

        // Hitung total harga pada halaman Stock Out
        function calculateOutTotal() {
            const totalAmount = document.getElementById('totalAmount');
            if (!totalAmount || !quantityInput) return;

            const qty = parseInt(quantityInput.value) || 0;
            const total = qty * currentPrice;
            totalAmount.value = total > 0 ? formatCurrencyValue(total) : '-';
        }

        // Hitung selisih stok fisik vs sistem pada halaman Stock Audit
        window.calculateDifference = function () {
            const sysEl = document.getElementById('system_stock');
            const diffEl = document.getElementById('difference');
            if (!sysEl || !diffEl || !physicalInput) return;

            const sys = parseInt(sysEl.textContent);
            const phys = parseInt(physicalInput.value);

            if (isNaN(sys) || isNaN(phys)) {
                diffEl.textContent = '-';
                return;
            }
            const diff = phys - sys;
            diffEl.textContent = diff > 0 ? '+' + diff : String(diff);
        };

        // Perbarui informasi stok & harga saat produk dipilih
        window.updateProductInfo = function () {
            if (!productSelect) return;
            const selected = productSelect.options[productSelect.selectedIndex];
            const hasProduct = Boolean(productSelect.value && selected);
            const stock = hasProduct ? Number(selected.dataset.stock || 0) : 0;
            const price = hasProduct ? parseFloat(selected.dataset.price || 0) : 0;

            // 1. Tampilan Stock In
            const stockInfo = document.getElementById('currentStockInfo');
            if (stockInfo) {
                stockInfo.textContent = hasProduct
                    ? (cfg.currentStockLabel || 'Current stock: ') + stock.toLocaleString()
                    : (cfg.currentStockPrompt || '');
            }

            // 2. Tampilan Stock Out
            const stockDisplay = document.getElementById('stockDisplay');
            const priceDisplay = document.getElementById('priceDisplay');
            if (stockDisplay && priceDisplay) {
                currentPrice = hasProduct ? price : 0;
                stockDisplay.textContent = hasProduct ? stock.toLocaleString() : '-';
                priceDisplay.textContent = hasProduct ? formatCurrencyValue(price) : '-';
                calculateOutTotal();
            }

            // 3. Tampilan Stock Audit
            const sysStock = document.getElementById('system_stock');
            if (sysStock) {
                sysStock.textContent = hasProduct ? String(stock) : '-';
                window.calculateDifference();
            }
        };
        window.updateAuditStock = window.updateProductInfo;

        productSelect?.addEventListener('change', window.updateProductInfo);
        quantityInput?.addEventListener('input', calculateOutTotal);
        physicalInput?.addEventListener('input', window.calculateDifference);

        // Filter opsi dropdown produk berdasarkan kategori terpilih
        categoryFilter?.addEventListener('change', function () {
            if (!productSelect) return;
            const selectedCat = this.value;
            productSelect.innerHTML = '';

            allProductOptions.forEach(opt => {
                if (!opt.value) {
                    productSelect.add(new Option(opt.text, opt.value));
                    return;
                }
                const prodCat = String(opt.category);
                const isMatch =
                    selectedCat === 'ALL' ||
                    (selectedCat === 'UNCATEGORIZED' && (!prodCat || prodCat === 'null' || prodCat === 'UNCATEGORIZED')) ||
                    prodCat === selectedCat;

                if (isMatch) {
                    const newOpt = new Option(opt.text, opt.value);
                    newOpt.dataset.stock = opt.stock;
                    newOpt.dataset.price = opt.price;
                    newOpt.dataset.category = opt.category;
                    productSelect.add(newOpt);
                }
            });

            window.updateProductInfo();
        });

        // Handler Submit Stock In
        const stockInForm = document.getElementById('stockInForm');
        stockInForm?.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!stockInForm.reportValidity()) return;

            const btn = document.getElementById('stockInSubmitButton');
            const origHTML = btn.innerHTML;
            btn.disabled = true;
            btn.textContent = cfg.savingText || 'Saving...';

            try {
                const res = await fetch('../api/create-stock-in.php', {
                    method: 'POST',
                    body: new FormData(stockInForm)
                });
                const result = await res.json();

                if (!res.ok || result.status !== 'success') {
                    showToast(result.message || cfg.failedMsg, 'error');
                    return;
                }
                showToast(result.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } catch (err) {
                console.error(err);
                showToast(cfg.errorMsg, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origHTML;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });

        // Handler Submit Stock Out
        const saveStockOutBtn = document.getElementById('saveStockOut');
        saveStockOutBtn?.addEventListener('click', async function () {
            const productId = productSelect?.value;
            const quantity = quantityInput?.value;
            const notes = document.getElementById('notes')?.value || '';

            if (!productId) return showToast(cfg.selectProductFirst, 'error');
            if (!quantity || quantity < 1) return showToast(cfg.enterQuantityOut, 'error');

            const selected = productSelect.options[productSelect.selectedIndex];
            const available = parseInt(selected.dataset.stock || 0);
            if (parseInt(quantity) > available) {
                return showToast(`${cfg.insufficientStock} ${available}`, 'error');
            }

            const formData = new FormData();
            formData.append('business_id', cfg.businessId);
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('notes', notes);

            try {
                const res = await fetch('../api/create-stock-out.php', { method: 'POST', body: formData });
                const result = await res.json();

                if (result.success || result.status === 'success') {
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast(cfg.systemError, 'error');
            }
        });

        // Handler Submit Stock Audit
        window.saveAudit = async function () {
            const productId = productSelect?.value;
            const physicalStock = physicalInput?.value;
            const notes = document.getElementById('notes')?.value.trim() || '';
            const btn = document.getElementById('saveAuditButton');

            if (!productId) return showToast(cfg.selectProductFirst, 'error');
            if (physicalStock === '' || parseInt(physicalStock) < 0) {
                return showToast(cfg.enterValidPhysical, 'error');
            }

            const origHTML = btn ? btn.innerHTML : '';
            if (btn) btn.disabled = true;

            const formData = new FormData();
            formData.append('business_id', cfg.businessId);
            formData.append('product_id', productId);
            formData.append('physical_stock', physicalStock);
            formData.append('notes', notes);

            try {
                const res = await fetch('../api/create-stock-audit.php', { method: 'POST', body: formData });
                const result = await res.json();

                if (result.success || result.status === 'success') {
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast(cfg.systemError, 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHTML;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            }
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStockModule);
    } else {
        initStockModule();
    }
})();
