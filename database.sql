CREATE DATABASE IF NOT EXISTS inventaris_db;
USE inventaris_db;

CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('consumable', 'non-consumable') NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    unit VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE borrow_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    borrower_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    borrow_date DATE NOT NULL,
    return_status BOOLEAN NOT NULL DEFAULT FALSE,
    return_date DATE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

CREATE TABLE withdrawal_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    recipient_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    withdrawal_date DATE NOT NULL,
    remarks TEXT,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);
