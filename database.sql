-- ============================================================
-- database.sql  -  Database for the POS System (version 2)
-- ------------------------------------------------------------
-- HOW TO USE:
--   1. In phpMyAdmin, create an EMPTY database first (e.g. "pos_db").
--      (On InfinityFree, create it in Control Panel -> MySQL Databases.)
--   2. Click that database, go to the "Import" tab, choose this file.
-- Note: there is no CREATE DATABASE line on purpose, because
-- InfinityFree gives you a database name you cannot choose freely.
--
-- DEFAULT LOGINS (change them after deploying!):
--   admin    / admin123
--   cashier  / cashier123
-- ============================================================

-- Start clean if re-importing (child tables are dropped first)
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------------
-- TABLE 1: users  (people who can log in)
-- ------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,     -- primary key
    full_name     VARCHAR(100) NOT NULL,
    username      VARCHAR(30)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,                    -- hashed, NEVER plain text
    role          ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLE 2: products  (the main CRUD table)
-- ------------------------------------------------------------
CREATE TABLE products (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,      -- primary key
    product_name VARCHAR(100) NOT NULL,                     -- name shown at the counter
    barcode      VARCHAR(30)  DEFAULT NULL,                 -- item code / barcode used for scanning
    category     VARCHAR(50)  NOT NULL,                     -- Food, Drinks, Snacks, ...
    price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,       -- selling price in pesos
    stock        INT UNSIGNED NOT NULL DEFAULT 0,           -- pieces left
    description  VARCHAR(255) DEFAULT NULL,                 -- optional note
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_barcode (barcode)                         -- no two products share a code
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLE 3: sales  (one row per transaction / receipt)
-- ------------------------------------------------------------
CREATE TABLE sales (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,   -- primary key
    receipt_no      VARCHAR(30) DEFAULT NULL,               -- e.g. R20261105-0001
    subtotal        DECIMAL(10,2) NOT NULL,                 -- total BEFORE discount
    discount_type   ENUM('None','Senior','PWD') NOT NULL DEFAULT 'None',
    discount_id_no  VARCHAR(30) DEFAULT NULL,               -- Senior/PWD ID number
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount    DECIMAL(10,2) NOT NULL,                 -- amount to pay (after discount)
    amount_paid     DECIMAL(10,2) NOT NULL,                 -- cash given by customer
    change_amount   DECIMAL(10,2) NOT NULL DEFAULT 0.00,    -- sukli
    payment_method  ENUM('Cash','GCash') NOT NULL DEFAULT 'Cash',
    cashier_id      INT UNSIGNED DEFAULT NULL,              -- foreign key -> users.id
    cashier_name    VARCHAR(100) NOT NULL DEFAULT '',       -- copy of the name at time of sale
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_receipt_no (receipt_no),
    -- If a cashier account is deleted, old sales stay (cashier_name keeps the name)
    CONSTRAINT fk_sales_user
        FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLE 4: sale_items  (the items inside each sale)
-- Relationship: one sale has MANY sale_items (one-to-many)
-- ------------------------------------------------------------
CREATE TABLE sale_items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,      -- primary key
    sale_id      INT UNSIGNED NOT NULL,                     -- foreign key -> sales.id
    product_id   INT UNSIGNED DEFAULT NULL,                 -- foreign key -> products.id
    product_name VARCHAR(100) NOT NULL,                     -- copy of the name at time of sale
    price        DECIMAL(10,2) NOT NULL,                    -- copy of the price at time of sale
    quantity     INT UNSIGNED NOT NULL,
    subtotal     DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_items_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    -- If a product is deleted later, old receipts still keep their data
    CONSTRAINT fk_items_product
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- DEFAULT USERS (passwords are stored as bcrypt hashes)
-- ------------------------------------------------------------
INSERT INTO users (full_name, username, password_hash, role) VALUES
('Administrator', 'admin',   '$2y$10$W3kpWrb97p3j029ABvHSDOgTZVcx4shZ/fMEzhEsFbYOmSBCGMZau', 'admin'),
('Cashier One',   'cashier', '$2y$10$f0p4DuuMpW3SKRBrcapF3OL0Ww4nTpZrg/kgtLP.RUntL1nVPy9Ai', 'cashier');

-- ------------------------------------------------------------
-- SAMPLE PRODUCTS (item codes 10001-10012 so you can test scanning)
-- ------------------------------------------------------------
INSERT INTO products (product_name, barcode, category, price, stock, description) VALUES
('Instant Noodles (Pancit Canton)', '10001', 'Food',      18.00, 60, 'Single pack'),
('Canned Sardines',                 '10002', 'Food',      24.00, 40, '155g can'),
('Rice (1 kg)',                     '10003', 'Food',      58.00, 30, 'Well-milled'),
('Egg (1 pc)',                      '10004', 'Food',      10.00, 90, NULL),
('Soft Drink 295ml',                '10005', 'Drinks',    15.00, 48, 'Bottle'),
('Bottled Water 500ml',             '10006', 'Drinks',    15.00, 36, NULL),
('3-in-1 Coffee Sachet',            '10007', 'Drinks',     7.00, 100, NULL),
('Potato Chips',                    '10008', 'Snacks',    20.00, 25, 'Small pack'),
('Biscuit Pack',                    '10009', 'Snacks',    10.00, 50, NULL),
('Bath Soap',                       '10010', 'Household', 32.00, 20, NULL),
('Laundry Detergent Sachet',        '10011', 'Household', 12.00, 4,  'Low stock demo'),
('Sugar (1/4 kg)',                  '10012', 'Others',    20.00, 0,  'Out of stock demo');
