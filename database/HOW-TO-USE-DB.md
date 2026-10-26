# How to connect to the database properly

1. Place all PHP files in xampp/htdocs/library
2. Open XAMPP and start the servers (Apache and MySQL)
3. Go to phpMyAdmin and paste the following into the SQL query:

```sql
CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. ADMINS TABLE
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. GENRES TABLE
CREATE TABLE IF NOT EXISTS genres (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

INSERT IGNORE INTO genres (id, name) VALUES
('scifi', 'Sci-Fi & Cyberpunk'),
('fantasy', 'Fantasy & Supernatural'),
('action', 'Action, Adventure & Martial Arts'),
('romance', 'Romance & Drama'),
('mystery', 'Mystery, Horror & Psychological'),
('academics', 'Academics, Non-Fiction & Skill Building');

-- 4. BOOKS TABLE (Removed 'borrower' column as it is handled dynamically via JOIN)
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    year VARCHAR(10) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    status VARCHAR(50) DEFAULT 'Available',
    call_no VARCHAR(50) NOT NULL,
    genre_id VARCHAR(50) NOT NULL,
    is_hidden BOOLEAN DEFAULT 0,
    description TEXT NULL,
    cover_image MEDIUMBLOB NULL,
    cover_type VARCHAR(50) NULL,
    FOREIGN KEY (genre_id) REFERENCES genres(id)
);

-- 5. BORROW REQUESTS TABLE (Added 'user_id' to link the user to the request)
CREATE TABLE IF NOT EXISTS borrow_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    user_id INT NULL DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    borrow_date DATE NOT NULL,
    return_date DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 6. UPGRADE FOR EXISTING DATABASES (quantity, description + cover image)
-- Skips automatically if the columns already exist, so it is safe to run this whole script again.
SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'quantity') = 0,
            'ALTER TABLE books ADD COLUMN quantity INT NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'borrow_requests' AND COLUMN_NAME = 'quantity') = 0,
            'ALTER TABLE borrow_requests ADD COLUMN quantity INT NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'description') = 0,
            'ALTER TABLE books ADD COLUMN description TEXT NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'cover_image'
               AND DATA_TYPE <> 'mediumblob') > 0,
            'ALTER TABLE books DROP COLUMN cover_image', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'cover_image') = 0,
            'ALTER TABLE books ADD COLUMN cover_image MEDIUMBLOB NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'cover_type') = 0,
            'ALTER TABLE books ADD COLUMN cover_type VARCHAR(50) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
```

> Everyone (new or existing database) just pastes and runs the SQL above - no separate file needed.
> It creates anything missing and upgrades older databases. Cover images are stored inside the database
> (max 800KB, admin upload only), so exporting/importing `library_db` also carries the images.

Generate a password hash using PHP's `password_hash()` method (the same method used for user signups). Open the shell or terminal provided by your XAMPP distribution and run:

```sh
php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
```

If `php` is not recognized, run the bundled PHP CLI executable from your XAMPP installation (`php.exe` or `php`, depending on your system). Its location varies by XAMPP distribution; use that executable in place of `php` in the command above.

Common Windows locations are:

```text
C:\xampp\php\php.exe
C:\Program Files\xampp\php\php.exe
```

For example, in Windows Command Prompt, use:

```bat
"C:\xampp\php\php.exe" -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
```

In PowerShell, prefix the quoted executable path with `&`:

```powershell
& "C:\xampp\php\php.exe" -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
```

Replace `C:\xampp\php\php.exe` with the path where your XAMPP distribution installed `php.exe`.

Copy the generated hash into the database instead of storing the plain-text password:

```sql
INSERT INTO admins (username, password)
VALUES ('your-admin-name', 'paste-generated-hash-here');
```

For an existing admin account, update its password column with a generated hash:

```sql
UPDATE admins
SET password = 'paste-generated-hash-here'
WHERE username = 'your-admin-name';
```
