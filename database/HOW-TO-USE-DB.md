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

INSERT INTO genres (id, name) VALUES
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
    status VARCHAR(50) DEFAULT 'Available',
    call_no VARCHAR(50) NOT NULL,
    genre_id VARCHAR(50) NOT NULL,
    is_hidden BOOLEAN DEFAULT 0,
    FOREIGN KEY (genre_id) REFERENCES genres(id)
);

-- 5. BORROW REQUESTS TABLE (Added 'user_id' to link the user to the request)
CREATE TABLE IF NOT EXISTS borrow_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    user_id INT NULL DEFAULT NULL,
    borrow_date DATE NOT NULL,
    return_date DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

Create an admin account with a password string:

```sql
INSERT INTO admins (username, password)
VALUES ('your-admin-name', 'your-password');
```
