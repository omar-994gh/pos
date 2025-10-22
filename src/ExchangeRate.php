<?php
// src/ExchangeRate.php
class ExchangeRate {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all exchange rates
     */
    public function getAllRates() {
        $stmt = $this->db->prepare("SELECT * FROM Exchange_Rates WHERE is_active = 1 ORDER BY from_currency, to_currency");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get exchange rate between two currencies
     */
    public function getRate($fromCurrency, $toCurrency) {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        
        $stmt = $this->db->prepare("SELECT rate FROM Exchange_Rates WHERE from_currency = ? AND to_currency = ? AND is_active = 1");
        $stmt->execute([$fromCurrency, $toCurrency]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (float)$result['rate'] : null;
    }
    
    /**
     * Update or insert exchange rate
     */
    public function setRate($fromCurrency, $toCurrency, $rate) {
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO Exchange_Rates (from_currency, to_currency, rate, is_active, updated_at) 
            VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$fromCurrency, $toCurrency, $rate]);
    }
    
    /**
     * Convert price from one currency to another
     */
    public function convertPrice($amount, $fromCurrency, $toCurrency) {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }
        // Use break-even (usd_to_syp_rate) from settings for USD↔SYP conversions
        $settings = $this->getSystemSettings();
        $usdToSyp = (float)($settings['usd_to_syp_rate'] ?? 15000.0);
        if ($usdToSyp <= 0) { $usdToSyp = 15000.0; }

        if ($fromCurrency === 'SYP' && $toCurrency === 'USD') {
            return $amount / $usdToSyp; // SYP → USD: price_syp / break-even
        }
        if ($fromCurrency === 'USD' && $toCurrency === 'SYP') {
            return $amount * $usdToSyp; // USD → SYP: price_usd * break-even
        }

        $rate = $this->getRate($fromCurrency, $toCurrency);
        if ($rate === null) {
            // Try reverse conversion
            $reverseRate = $this->getRate($toCurrency, $fromCurrency);
            if ($reverseRate !== null && $reverseRate != 0) {
                $rate = 1 / $reverseRate;
            } else {
                return $amount; // Return original amount if no rate found
            }
        }
        
        return $amount * $rate;
    }
    
    /**
     * Get system exchange rate settings
     */
    public function getSystemSettings() {
        $stmt = $this->db->prepare("SELECT exchange_rate_enabled, base_currency, usd_to_syp_rate, try_to_syp_rate FROM System_Settings ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            // Always enable exchange rate logic by default
            return [
                'exchange_rate_enabled' => 1,
                'base_currency' => 'SYP',
                'usd_to_syp_rate' => 15000.0,
                'try_to_syp_rate' => 500.0
            ];
        }

        // Enforce exchange-rate feature ON globally as requested
        $result['exchange_rate_enabled'] = 1;

        return $result;
    }
    
    /**
     * Update system exchange rate settings
     */
    public function updateSystemSettings($enabled, $baseCurrency, $usdToSypRate, $tryToSypRate) {
        // Update the latest System_Settings record
        $stmt = $this->db->prepare("
            UPDATE System_Settings 
            SET exchange_rate_enabled = ?, base_currency = ?, usd_to_syp_rate = ?, try_to_syp_rate = ?
            WHERE id = (SELECT MAX(id) FROM System_Settings)
        ");
        $result = $stmt->execute([$enabled, $baseCurrency, $usdToSypRate, $tryToSypRate]);
        
        // Also update the exchange rates table
        if ($result) {
            $this->setRate('USD', 'SYP', $usdToSypRate);
            $this->setRate('TRY', 'SYP', $tryToSypRate);
            $this->setRate('SYP', 'USD', 1 / $usdToSypRate);
            $this->setRate('SYP', 'TRY', 1 / $tryToSypRate);
            
            // Calculate cross rates
            $tryToUsdRate = $tryToSypRate / $usdToSypRate;
            $usdToTryRate = $usdToSypRate / $tryToSypRate;
            
            $this->setRate('TRY', 'USD', $tryToUsdRate);
            $this->setRate('USD', 'TRY', $usdToTryRate);
        }
        
        return $result;
    }
    
    /**
     * Convert price to display currency based on system settings
     */
    public function convertToDisplayCurrency($price, $originalCurrency) {
        $settings = $this->getSystemSettings();
        $baseCurrency = $settings['base_currency'];
        // Always convert to the display currency; if same, convertPrice is a no-op
        return $this->convertPrice($price, $originalCurrency, $baseCurrency);
    }
    
    /**
     * Format price with currency symbol
     */
    public function formatPrice($price, $currency) {
        $symbols = [
            'SYP' => 'ل.س',
            'USD' => '$',
            'TRY' => '₺'
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        return number_format($price, 2) . ' ' . $symbol;
    }
}
?>

