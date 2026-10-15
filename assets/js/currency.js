
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

function formatMoney(amount, currency = 'IDR') {
    const config = CURRENCY_CONFIG[currency]
        || CURRENCY_CONFIG.IDR;

    return new Intl.NumberFormat(config.locale, {
        style: 'currency',
        currency: config.currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Number(amount) || 0);
}
