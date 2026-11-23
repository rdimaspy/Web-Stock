/**
 * Helper UI Global (Ikon, Dropdown, Toggle Password, Preview Foto, & Submit Form Async)
 */

// Render ikon Lucide jika tersedia
function initIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Jalankan callback segera jika DOM sudah siap, atau tunggu DOMContentLoaded
function onDomReady(fn) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn);
    } else {
        fn();
    }
}

// Toggle buka/tutup elemen menu dropdown berdasarkan ID
function toggleMenu(menuId) {
    const menu = document.getElementById(menuId);
    if (menu) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }
}

// Alias kompatibilitas untuk header dashboard & sidebar
function toggleLanguageMenu() {
    toggleMenu('language-menu');
}

function toggleSubmenu(event, id) {
    if (event) event.preventDefault();
    toggleMenu(id);
}

// Toggle tampil/sembunyikan kata sandi pada input password
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    const showText = btn.dataset.showText || window.APP_CONFIG?.showPassword || 'Show';
    const hideText = btn.dataset.hideText || window.APP_CONFIG?.hidePassword || 'Hide';
    const titleText = isPassword ? hideText : showText;

    btn.setAttribute('title', titleText);
    btn.setAttribute('aria-label', titleText);
    btn.innerHTML = `<i data-lucide="${isPassword ? 'eye-off' : 'eye'}" style="width:18px; height:18px;"></i>`;
    initIcons();
}

// Pasang pratinjau gambar otomatis saat input file dipilih
function setupImagePreview(inputId, previewImgId, iconId) {
    const attach = () => {
        const input = document.getElementById(inputId);
        if (!input || input.dataset.previewBound === '1') return;
        input.dataset.previewBound = '1';

        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                const preview = document.getElementById(previewImgId);
                const icon = document.getElementById(iconId);
                if (preview) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                if (icon) icon.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    };

    if (document.getElementById(inputId)) {
        attach();
    } else {
        onDomReady(attach);
    }
}

// Helper pengiriman form secara asinkron menggunakan Fetch API
function bindAsyncForm(formId, apiUrl, options = {}) {
    const attach = () => {
        const form = document.getElementById(formId);
        if (!form || form.dataset.asyncBound === '1') return;
        form.dataset.asyncBound = '1';

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const url = typeof apiUrl === 'function' ? apiUrl(formData) : apiUrl;

            try {
                const response = await fetch(url, { method: 'POST', body: formData });
                const result = await response.json();

                if (result.status === 'success' || result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => {
                        if (options.redirect) {
                            window.location.href = options.redirect;
                        } else if (options.reload) {
                            window.location.reload();
                        }
                    }, options.delay || 1100);
                } else {
                    showToast(result.message || 'Error', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast(window.APP_CONFIG?.connectionError || 'Connection error', 'error');
            }
        });
    };

    if (document.getElementById(formId)) {
        attach();
    } else {
        onDomReady(attach);
    }
}

// Hapus akun pengguna secara permanen (businesses.php)
async function deleteUserAccount() {
    const cfg = window.APP_CONFIG || {};
    const confirmed = await showConfirm(cfg.confirmDeleteAccount, cfg.deleteAccountBtn, cfg.cancelBtn, true);
    if (!confirmed) return;

    try {
        const response = await fetch('api/delete-account.php', { method: 'POST' });
        const result = await response.json();

        if (result.status === 'success') {
            showToast(result.message, 'success');
            setTimeout(() => { window.location.href = 'login.php'; }, 1200);
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        console.error(err);
        showToast(cfg.connectionError || 'Connection error', 'error');
    }
}

// Hapus bisnis/toko aktif secara permanen (dashboard/settings.php)
async function deleteActiveBusiness() {
    const cfg = window.APP_CONFIG || {};
    const confirmed = await showConfirm(cfg.confirmDeleteBusiness, cfg.deleteBusinessBtn, cfg.cancelBtn, true);
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('business_id', cfg.businessId);

    try {
        const response = await fetch('../api/delete-business.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.status === 'success') {
            showToast(result.message, 'success');
            setTimeout(() => { window.location.href = '../businesses.php'; }, 1200);
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        console.error(err);
        showToast(cfg.connectionError || 'Connection error', 'error');
    }
}

// Inisialisasi seluruh handler form dan ikon
function initApp() {
    initIcons();

    const langBtn = document.getElementById('languageButton');
    if (langBtn && !langBtn.dataset.bound) {
        langBtn.dataset.bound = '1';
        langBtn.addEventListener('click', () => toggleMenu('languageMenu'));
    }

    bindAsyncForm('loginForm', 'api/api-login.php', { redirect: 'businesses.php', delay: 1000 });
    bindAsyncForm('registerForm', 'api/api-register.php', { redirect: 'login.php', delay: 1200 });
    bindAsyncForm('createBusinessForm', 'api/api-create-business.php', { redirect: 'businesses.php', delay: 1200 });
    bindAsyncForm('settingsForm', (fd) => '../api/update-settings.php?business_id=' + fd.get('business_id'), { reload: true, delay: 1200 });

    setupImagePreview('business-photo', 'businessPhotoPreview', 'upload-icon');
    setupImagePreview('businessPhoto', 'businessPreview', 'businessUploadIcon');
}

initApp();
onDomReady(initApp);
