/**
 * Komponen Notifikasi Toast & Modal Konfirmasi Kustom
 * Menggantikan alert() dan confirm() bawaan browser
 */
(function () {
    function getToastContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    // Menampilkan notifikasi toast ('success', 'error', 'info')
    window.showToast = function (message, type = 'info', duration = 3500) {
        const container = getToastContainer();
        const toast = document.createElement('div');
        toast.className = `custom-toast ${type}`;
        toast.innerHTML = `<span>${message}</span><button class="toast-close">&times;</button>`;
        container.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('show'));

        const removeToast = () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        };

        toast.querySelector('.toast-close').addEventListener('click', removeToast);
        setTimeout(removeToast, duration);
    };

    // Menampilkan dialog modal konfirmasi kustom berbasis Promise
    window.showConfirm = function (message, confirmText = 'OK', cancelText = 'Cancel', isDanger = false) {
        return new Promise((resolve) => {
            let backdrop = document.getElementById('custom-confirm-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'custom-confirm-backdrop';
                document.body.appendChild(backdrop);
            }

            const btnClass = isDanger ? 'btn-danger' : 'btn-primary';
            backdrop.innerHTML = `
                <div class="custom-confirm-card">
                    <p style="margin:0 0 20px; color:#302921; font-size:15px; line-height:1.5; font-weight:500;">${message}</p>
                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button id="confirmBtnCancel" class="btn btn-soft btn-sm">${cancelText}</button>
                        <button id="confirmBtnOk" class="btn ${btnClass} btn-sm">${confirmText}</button>
                    </div>
                </div>
            `;

            backdrop.classList.add('active');

            const cleanup = (result) => {
                backdrop.classList.remove('active');
                setTimeout(() => {
                    backdrop.innerHTML = '';
                    resolve(result);
                }, 250);
            };

            backdrop.querySelector('#confirmBtnOk').addEventListener('click', () => cleanup(true));
            backdrop.querySelector('#confirmBtnCancel').addEventListener('click', () => cleanup(false));
        });
    };
})();
