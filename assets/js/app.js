// Inisialisasi ikon Lucide jika library Lucide JS dimuat di halaman
if (window.lucide) {
    window.lucide.createIcons();
}

// Elemen pemicu dan container dropdown pilihan bahasa
const languageButton = document.getElementById("languageButton");
const languageMenu = document.getElementById("languageMenu");

// Menangani klik tombol bahasa untuk menampilkan atau menyembunyikan menu dropdown
if (languageButton && languageMenu) {
    languageButton.addEventListener("click", function () {
        if (languageMenu.style.display === "block") {
            languageMenu.style.display = "none";
        } else {
            languageMenu.style.display = "block";
        }
    });
}

// Fungsi untuk mengganti bahasa tampilan teks di halaman berdasarkan key
function changeLanguage(language) {
    const lang = (language === 'en') ? 'en' : 'id';

    // Ambil file kamus dari API atau JSON statis jika ada, atau gunakan map sederhana
    fetch(`lang/${lang}.php`)
        .then(response => response.text())
        .then(() => {
            // Memperbarui elemen HTML berdasarkan data-lang-key
            document.querySelectorAll("[data-lang-key]").forEach(function(element) {
                const key = element.getAttribute("data-lang-key");
                if (window.translations && window.translations[lang] && window.translations[lang][key]) {
                    element.innerHTML = window.translations[lang][key];
                }
            });
        }).catch(() => {
            // Fallback ubah style aktif saja jika request file gagal
        });

    // Simpan pilihan bahasa pengguna ke penyimpanan lokal browser
    localStorage.setItem("language", lang);

    // Memperbarui warna latar belakang tombol bahasa yang sedang aktif
    const indonesia = document.getElementById("langIndonesia");
    const english = document.getElementById("langEnglish");

    if (indonesia && english) {
        indonesia.style.background = lang === "id" ? "#F5EEE5" : "none";
        english.style.background = lang === "en" ? "#F5EEE5" : "none";
    }

    if (languageMenu) {
        languageMenu.style.display = "none";
    }
}

// Memuat dan menerapkan pilihan bahasa terakhir yang tersimpan saat halaman selesai dimuat
document.addEventListener("DOMContentLoaded", function(){
    const savedLanguage = localStorage.getItem("language");
    if (savedLanguage) {
        changeLanguage(savedLanguage);
    }
});