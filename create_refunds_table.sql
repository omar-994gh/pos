-- Create Refunds table for tracking item refunds
CREATE TABLE IF NOT EXISTS Refunds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    amount REAL NOT NULL,
    reason TEXT NOT NULL,
    user_id INTEGER NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES Items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_refunds_order_id ON Refunds(order_id);
CREATE INDEX IF NOT EXISTS idx_refunds_item_id ON Refunds(item_id);
CREATE INDEX IF NOT EXISTS idx_refunds_user_id ON Refunds(user_id);
CREATE INDEX IF NOT EXISTS idx_refunds_created_at ON Refunds(created_at);