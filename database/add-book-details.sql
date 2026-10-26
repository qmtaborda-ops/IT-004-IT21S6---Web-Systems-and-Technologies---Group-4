-- ============================================================
-- Book quantity, description + cover image  (safe to run more than once)
-- Run in phpMyAdmin: select library_db -> SQL tab -> paste -> Go
-- Works for old databases AND new ones; it only adds what is missing.
-- ============================================================
USE library_db;

CREATE TABLE IF NOT EXISTS payment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('cash', 'gcash', 'card') NOT NULL,
    gcash_reference VARCHAR(100) NULL,
    card_last4 CHAR(4) NULL,
    notes VARCHAR(500) NULL,
    status ENUM('Pending', 'Paid', 'Rejected') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'borrow_requests' AND COLUMN_NAME = 'item_condition') = 0,
            'ALTER TABLE borrow_requests ADD COLUMN item_condition ENUM(''Good'', ''Damaged'', ''Missing'') NOT NULL DEFAULT ''Good''',
            'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Available inventory quantity
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'quantity') = 0,
            'ALTER TABLE books ADD COLUMN quantity INT NOT NULL DEFAULT 1',
            'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Listed replacement price used for liabilities
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'price') = 0,
            'ALTER TABLE books ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Quantity requested for each borrowing record
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'borrow_requests' AND COLUMN_NAME = 'quantity') = 0,
            'ALTER TABLE borrow_requests ADD COLUMN quantity INT NOT NULL DEFAULT 1',
            'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- description
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'description') = 0,
            'ALTER TABLE books ADD COLUMN description TEXT NULL',
            'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- cover_image (stored in the database as binary). If an older trial version created it
-- as text, replace it.
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'cover_image'
               AND DATA_TYPE <> 'mediumblob') > 0,
            'ALTER TABLE books DROP COLUMN cover_image',
            'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'cover_image') = 0,
            'ALTER TABLE books ADD COLUMN cover_image MEDIUMBLOB NULL',
            'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- cover_type (image/jpeg, image/png, ...)
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'cover_type') = 0,
            'ALTER TABLE books ADD COLUMN cover_type VARCHAR(50) NULL',
            'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
