// Konfigurasi standar format mata uang beserta lokasi wilayah (locale)
const CURRENCY_CONFIG = {
    IDR: {
        locale: 'id-ID',
        currency: 'IDR'
    },
    USD: {
        locale: 'en-US',
        currency: 'USD'
    },
    EUR: {
        locale: 'de-DE',
        currency: 'EUR'
    },
    JPY: {
        locale: 'ja-JP',
        currency: 'JPY'
    },
    GBP: {
        locale: 'en-GB',
        currency: 'GBP'
    },
    CNY: {
        locale: 'zh-CN',
        currency: 'CNY'
    }
};

// Fungsi untuk memformat angka nominal menjadi tampilan mata uang lengkap dengan simbolnya
function formatMoney(amount, currency = 'IDR') {
    // Memilih konfigurasi mata uang yang sesuai atau menggunakan IDR sebagai default
    const config = CURRENCY_CONFIG[currency]
        || CURRENCY_CONFIG.IDR;

    // Mengembalikan string harga terformat menggunakan standar Intl.NumberFormat
    return new Intl.NumberFormat(config.locale, {
        style: 'currency',
        currency: config.currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Number(amount) || 0);
}