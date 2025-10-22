-- Exchange Rates Migration
-- This file adds exchange rate functionality to the POS system

-- Create Exchange_Rates table to store currency exchange rates
CREATE TABLE IF NOT EXISTS Exchange_Rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency TEXT NOT NULL,
    to_currency TEXT NOT NULL,
    rate REAL NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(from_currency, to_currency)
);

-- Add exchange rate settings to System_Settings table
-- These columns will be added via ALTER TABLE if they don't exist

-- exchange_rate_enabled: Whether to use exchange rate conversion (0/1)
-- base_currency: The base currency for exchange rate calculations (SYP, USD, TRY)
-- usd_to_syp_rate: Exchange rate from USD to SYP
-- try_to_syp_rate: Exchange rate from TRY to SYP

-- Insert default exchange rates (these can be updated by users)
INSERT OR IGNORE INTO Exchange_Rates (from_currency, to_currency, rate, is_active) VALUES
('USD', 'SYP', 15000.0, 1),
('TRY', 'SYP', 500.0, 1),
('SYP', 'USD', 0.0000667, 1),
('SYP', 'TRY', 0.002, 1),
('USD', 'TRY', 30.0, 1),
('TRY', 'USD', 0.0333, 1);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_exchange_rates_from ON Exchange_Rates(from_currency);
CREATE INDEX IF NOT EXISTS idx_exchange_rates_to ON Exchange_Rates(to_currency);
CREATE INDEX IF NOT EXISTS idx_exchange_rates_active ON Exchange_Rates(is_active);

