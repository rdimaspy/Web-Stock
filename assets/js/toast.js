/*
Komponen Toast Notification & Modal Konfirmasi Kustom untuk menggantikan alert() dan confirm() bawaan browser.
*/

(function () {
    // Memeriksa dan menyuntikkan gaya CSS kustom jika elemen gaya belum ditambahkan ke tag head
    if (!document.getElementById('custom-ui-styles')) {
        const style = document.createElement('style');
        style.id = 'custom-ui-styles';
        style.innerHTML = `
            /* Kontainer penampung notifikasi toast di pojok kanan atas */
            #toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                pointer-events: none;
            }

            /* Gaya dasar elemen notifikasi toast */
            .custom-toast {
                pointer-events: auto;
                min-width: 280px;
                max-width: 380px;
                padding: 14px 18px;
                border-radius: 10px;
                font-family: Arial, sans-serif;
                font-size: 14px;
                font-weight: 500;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                border: 1px solid #E9E1D7;
                transform: translateX(120%);
                transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease;
                opacity: 0;
            }

            /* Kelas aktif untuk menampilkan toast dengan efek transisi sliding */
            .custom-toast.show {
                transform: translateX(0);
                opacity: 1;
            }

            /* Tampilan variabel warna berdasarkan status notifikasi */
            .custom-toast.success {
                background: #FFFFFF;
                color: #302921;
                border-left: 5px solid #6F8966;
            }

            .custom-toast.error {
                background: #FFFFFF;
                color: #B45F5F;
                border-left: 5px solid #B45F5F;
            }

            .custom-toast.info {
                background: #FFFFFF;
                color: #76553D;
                border-left: 5px solid #76553D;
            }

            /* Gaya tombol tutup notifikasi toast */
            .toast-close {
                background: none;
                border: none;
                font-size: 16px;
                cursor: pointer;
                color: #81776D;
                padding: 0;
                line-height: 1;
            }

            /* Gaya latar belakang transparan (backdrop) untuk modal konfirmasi */
            #custom-confirm-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(48, 41, 33, 0.4);
                backdrop-filter: blur(2px);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.25s ease;
            }

            /* Kelas aktif untuk memunculkan modal konfirmasi */
            #custom-confirm-backdrop.active {
                opacity: 1;
                pointer-events: auto;
            }

            /* Kartu utama tempat dialog konfirmasi */
            .custom-confirm-card {
                background: #FFFFFF;
                border-radius: 14px;
                width: 90%;
                max-width: 400px;
                padding: 24px;
                border: 1px solid #E9E1D7;
                box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                transform: scale(0.9);
                transition: transform 0.25s ease;
                font-family: Arial, sans-serif;
            }

            /* Animasi membesar saat modal konfirmasi aktif */
            #custom-confirm-backdrop.active .custom-confirm-card {
                transform: scale(1);
            }
        `;
        document.head.appendChild(style);
    }

    // Fungsi internal untuk membuat atau mengambil kontainer toast
    function getToastContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Menampilkan Notifikasi Toast Kustom
     * @param {string} message - Teks pesan yang akan ditampilkan
     * @param {string} type - Jenis notifikasi ('success', 'error', atau 'info')
     * @param {number} duration - Durasi waktu tampil dalam milidetik
     */
    window.showToast = function (message, type = 'info', duration = 3500) {
        // Mengambil elemen kontainer toast
        const container = getToastContainer();

        // Membuat elemen div baru untuk notifikasi toast
        const toast = document.createElement('div');
        toast.className = `custom-toast ${type}`;

        // Mengisi elemen toast dengan pesan dan tombol tutup
        toast.innerHTML = `
            <span>${message}</span>
            <button class="toast-close">&times;</button>
        `;

        // Menambahkan elemen toast ke dalam kontainer
        container.appendChild(toast);

        // Menjalankan animasi kemunculan toast
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Menyiapkan fungsi penutupan toast
        const closeBtn = toast.querySelector('.toast-close');
        const removeToast = () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        };

        closeBtn.addEventListener('click', removeToast);

        // Mengatur penghapusan otomatis berdasarkan durasi waktu
        setTimeout(removeToast, duration);
    };

    /**
     * Menampilkan Modal Konfirmasi Kustom
     * @param {string} message - Pesan pertanyaan konfirmasi
     * @param {string} confirmText - Teks pada tombol konfirmasi (dapat disesuaikan terjemahannya)
     * @param {string} cancelText - Teks pada tombol batal (dapat disesuaikan terjemahannya)
     * @param {boolean} isDanger - Status warna tombol konfirmasi (true = merah, false = cokelat)
     * @returns {Promise<boolean>}
     */
    window.showConfirm = function (message, confirmText = 'OK', cancelText = 'Cancel', isDanger = false) {
        return new Promise((resolve) => {
            // Mengambil atau membuat elemen backdrop konfirmasi
            let backdrop = document.getElementById('custom-confirm-backdrop');
            
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'custom-confirm-backdrop';
                document.body.appendChild(backdrop);
            }

            // Menentukan warna tombol berdasarkan parameter aksi berbahaya
            const btnConfirmBg = isDanger ? '#B45F5F' : '#76553D';

            // Menyusun HTML dialog modal konfirmasi
            backdrop.innerHTML = `
                <div class="custom-confirm-card">
                    <p style="margin: 0 0 20px; color: #302921; font-size: 15px; line-height: 1.5; font-weight: 500;">
                        ${message}
                    </p>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button id="confirmBtnCancel" style="padding: 9px 16px; background: #F5EFE8; color: #76553D; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px;">
                            ${cancelText}
                        </button>
                        <button id="confirmBtnOk" style="padding: 9px 16px; background: ${btnConfirmBg}; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px;">
                            ${confirmText}
                        </button>
                    </div>
                </div>
            `;

            // Mengaktifkan tampilan backdrop modal konfirmasi
            backdrop.classList.add('active');

            // Menangkap elemen tombol aksi
            const btnOk = backdrop.querySelector('#confirmBtnOk');
            const btnCancel = backdrop.querySelector('#confirmBtnCancel');

            // Fungsi pembersihan dan pengembalian nilai promise
            function cleanup(result) {
                backdrop.classList.remove('active');
                setTimeout(() => {
                    backdrop.innerHTML = '';
                    resolve(result);
                }, 250);
            }

            // Menambahkan listener event pada tombol OK dan Batal
            btnOk.addEventListener('click', () => cleanup(true));
            btnCancel.addEventListener('click', () => cleanup(false));
        });
    };
})();