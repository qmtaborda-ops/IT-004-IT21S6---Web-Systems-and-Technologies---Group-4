-- ============================================================
-- Book description + cover image  (safe to run more than once)
-- Run in phpMyAdmin: select library_db -> SQL tab -> paste -> Go
-- Works for old databases AND new ones; it only adds what is missing.
-- ============================================================
USE library_db;

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
