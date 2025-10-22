// public/js/exchange_rates.js
class ExchangeRateManager {
    constructor() {
        this.settings = null;
        this.rates = {};
        this.init();
    }

    async init() {
        await this.loadSettings();
        await this.loadRates();
    }

    async loadSettings() {
        try {
            const response = await fetch('exchange_rates_api.php?path=settings');
            const result = await response.json();
            if (result.success) {
                this.settings = result.data;
                // Enforce exchange logic ON globally
                this.settings.exchange_rate_enabled = 1;
            }
        } catch (error) {
            console.error('Failed to load exchange rate settings:', error);
        }
    }

    async loadRates() {
        try {
            const response = await fetch('exchange_rates_api.php?path=rates');
            const result = await response.json();
            if (result.success) {
                result.data.forEach(rate => {
                    const key = `${rate.from_currency}_${rate.to_currency}`;
                    this.rates[key] = parseFloat(rate.rate);
                });
            }
        } catch (error) {
            console.error('Failed to load exchange rates:', error);
        }
    }

    getRate(fromCurrency, toCurrency) {
        if (fromCurrency === toCurrency) {
            return 1.0;
        }

        // Use rates from system settings first
        if (this.settings) {
            // Enforce break-even rules explicitly
            const usdToSyp = parseFloat(this.settings.usd_to_syp_rate || 15000);
            const tryToSyp = parseFloat(this.settings.try_to_syp_rate || 500);
            if (fromCurrency === 'USD' && toCurrency === 'SYP') return usdToSyp;
            if (fromCurrency === 'SYP' && toCurrency === 'USD') return 1 / usdToSyp;
            if (fromCurrency === 'TRY' && toCurrency === 'SYP') return tryToSyp;
            if (fromCurrency === 'SYP' && toCurrency === 'TRY') return 1 / tryToSyp;
            if (fromCurrency === 'USD' && toCurrency === 'TRY') return usdToSyp / tryToSyp;
            if (fromCurrency === 'TRY' && toCurrency === 'USD') return tryToSyp / usdToSyp;
        }

        // Fallback to database rates
        const key = `${fromCurrency}_${toCurrency}`;
        if (this.rates[key]) {
            return this.rates[key];
        }

        // Try reverse conversion
        const reverseKey = `${toCurrency}_${fromCurrency}`;
        if (this.rates[reverseKey] && this.rates[reverseKey] !== 0) {
            return 1 / this.rates[reverseKey];
        }

        return 1.0; // Default to 1 if no rate found
    }

    convertPrice(amount, fromCurrency, toCurrency) {
        const rate = this.getRate(fromCurrency, toCurrency);
        return amount * rate;
    }

    convertToDisplayCurrency(price, originalCurrency) {
        const baseCurrency = this.settings?.base_currency || 'SYP';
        return this.convertPrice(price, originalCurrency, baseCurrency);
    }

    formatPrice(price, currency) {
        const symbols = {
            'SYP': 'ل.س',
            'USD': '$',
            'TRY': '₺'
        };

        const symbol = symbols[currency] || currency;
        return new Intl.NumberFormat('ar-SY', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(price) + ' ' + symbol;
    }

    formatDisplayPrice(price, originalCurrency) {
        const baseCurrency = this.settings?.base_currency || 'SYP';
        const convertedPrice = this.convertToDisplayCurrency(price, originalCurrency);
        return this.formatPrice(convertedPrice, baseCurrency);
    }

    // Update all prices on the page
    updatePagePrices() {
        // Find all elements with data-price attributes
        const priceElements = document.querySelectorAll('[data-price]');
        priceElements.forEach(element => {
            const originalPrice = parseFloat(element.getAttribute('data-price'));
            const originalCurrency = element.getAttribute('data-currency') || 'SYP';
            
            if (!isNaN(originalPrice)) {
                const displayPrice = this.formatDisplayPrice(originalPrice, originalCurrency);
                element.textContent = displayPrice;
            }
        });

        // Update total amounts
        const totalElements = document.querySelectorAll('[data-total]');
        totalElements.forEach(element => {
            const originalTotal = parseFloat(element.getAttribute('data-total'));
            const originalCurrency = element.getAttribute('data-currency') || 'SYP';
            
            if (!isNaN(originalTotal)) {
                const displayTotal = this.formatDisplayPrice(originalTotal, originalCurrency);
                element.textContent = displayTotal;
            }
        });
    }

    // Get currency symbol for display
    getCurrencySymbol(currency) {
        const symbols = {
            'SYP': 'ل.س',
            'USD': '$',
            'TRY': '₺'
        };
        return symbols[currency] || currency;
    }

    // Get display currency
    getDisplayCurrency() {
        return this.settings?.base_currency || 'SYP';
    }

    // Check if exchange rates are enabled
    isEnabled() {
        return true;
    }
}

// Global instance
let exchangeRateManager;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', async function() {
    exchangeRateManager = new ExchangeRateManager();
    
    // Wait a bit for initialization then update prices
    setTimeout(() => {
        if (exchangeRateManager.isEnabled()) {
            exchangeRateManager.updatePagePrices();
        }
    }, 500);
});

// Helper function for other scripts to use
function formatPriceWithExchange(price, currency) {
    if (exchangeRateManager) {
        return exchangeRateManager.formatDisplayPrice(price, currency);
    }
    return new Intl.NumberFormat('ar-SY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price) + ' ' + currency;
}

function convertPriceWithExchange(price, fromCurrency, toCurrency) {
    if (exchangeRateManager) {
        return exchangeRateManager.convertPrice(price, fromCurrency, toCurrency);
    }
    return price;
}

