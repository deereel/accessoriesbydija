// Currency Converter
class CurrencyConverter {
    constructor() {
        this.currentCurrency = localStorage.getItem('currency') || 'GBP';
        this.rates = { GBP: 1 }; // Base currency
        this.symbols = { GBP: '£', USD: '$', EUR: '€', CNY: '¥', NGN: '₦' };
        this.init();
    }

    async init() {
        await this.fetchRates();
        this.updateUI();
        this.bindEvents();
        this.convertAllPrices();
    }

    async fetchRates() {
        try {
            // Using a free API for currency conversion
            const response = await fetch('https://api.exchangerate-api.com/v4/latest/GBP');
            const data = await response.json();
            this.rates = { GBP: 1, ...data.rates };
        } catch (error) {
            console.log('API unavailable, keeping prices in GBP');
            // Keep only GBP when API fails
            this.rates = { GBP: 1 };
            this.currentCurrency = 'GBP';
            localStorage.setItem('currency', 'GBP');
        }
    }

    bindEvents() {
        const currencyBtn = document.getElementById('currency-btn');
        const currencyDropdown = document.getElementById('currency-dropdown');
        const currencyOptions = document.querySelectorAll('.currency-option');

        currencyBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            currencyDropdown.classList.toggle('active');
        });

        currencyOptions.forEach(option => {
            option.addEventListener('click', (e) => {
                const currency = e.target.dataset.currency;
                this.changeCurrency(currency);
                currencyDropdown.classList.remove('active');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            currencyDropdown?.classList.remove('active');
        });
    }

    changeCurrency(newCurrency) {
        // Only allow currency change if rates are available
        if (this.rates[newCurrency]) {
            this.currentCurrency = newCurrency;
            localStorage.setItem('currency', newCurrency);
            this.updateUI();
            this.convertAllPrices();
        } else {
            // Show message that currency is unavailable
            alert('Currency conversion unavailable. Prices shown in GBP.');
        }
    }

    updateUI() {
        const currentCurrencyEl = document.getElementById('current-currency');
        const currencyOptions = document.querySelectorAll('.currency-option');
        
        if (currentCurrencyEl) {
            currentCurrencyEl.textContent = this.currentCurrency;
        }

        currencyOptions.forEach(option => {
            option.classList.remove('selected');
            if (option.dataset.currency === this.currentCurrency) {
                option.classList.add('selected');
            }
        });
    }

    convertPrice(gbpPrice) {
        const rate = this.rates[this.currentCurrency] || 1;
        const convertedPrice = gbpPrice * rate;
        const symbol = this.symbols[this.currentCurrency] || '£';
        
        return `${symbol}${convertedPrice.toFixed(2)}`;
    }

    convertAllPrices() {
        // Convert product prices
        document.querySelectorAll('.product-price[data-price]').forEach(priceEl => {
            const gbpPrice = parseFloat(priceEl.dataset.price);
            if (!isNaN(gbpPrice)) {
                priceEl.innerHTML = this.convertPrice(gbpPrice);
            }
        });

        // Convert featured product prices
        document.querySelectorAll('.featured-price .price-amount[data-price]').forEach(priceEl => {
            const gbpPrice = parseFloat(priceEl.dataset.price);
            if (!isNaN(gbpPrice)) {
                priceEl.textContent = (gbpPrice * (this.rates[this.currentCurrency] || 1)).toFixed(2);
            }
        });

        // Convert currency symbols
        document.querySelectorAll('.currency-symbol').forEach(symbolEl => {
            symbolEl.textContent = this.symbols[this.currentCurrency] || '£';
        });

        // Convert price ranges in shop by price section
        document.querySelectorAll('.price-amount[data-price]').forEach(priceEl => {
            const gbpPrice = parseFloat(priceEl.dataset.price);
            if (!isNaN(gbpPrice)) {
                priceEl.textContent = (gbpPrice * (this.rates[this.currentCurrency] || 1)).toFixed(2);
            }
        });
    }
}

// Initialize currency converter when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.currencyConverter = new CurrencyConverter();
});

// Update prices when new products are loaded dynamically
function updateProductPrices() {
    if (window.currencyConverter) {
        window.currencyConverter.convertAllPrices();
    }
}

// Global function to update prices (called from other scripts)
function updatePrices() {
    if (window.currencyConverter) {
        window.currencyConverter.convertAllPrices();
    }
}