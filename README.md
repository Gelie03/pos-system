# Mabuhay Mart - Point of Sale (POS) System

Final project for **PHP and MySQL Development (Web Systems Technologies)**.
Built with **PHP, MySQL (MySQLi), HTML, CSS, and Bootstrap 5**.

> The store name "Mabuhay Mart" is a placeholder. Change `STORE_NAME` in `includes/functions.php` to your own store name.

## Features

- **Login system** with two roles: **admin** and **cashier**
- **Products (CRUD)**: add, view, edit, delete; search by name or item code (admin only)
- **Users (admin only)**: create and delete cashier/admin accounts
- **New Sale (cashier screen)**:
  - **Scan box**: type or scan an item code and press Enter (`10001` = 1 piece, `3*10001` = 3 pieces)
  - Tap product tiles as a backup, change quantities, live total and change
  - **Senior / PWD discount (20%)** with the ID number saved on the receipt
- **Checkout**: Cash or GCash, saved with a database transaction; stock is reduced automatically
- **Printable receipt** with receipt number, cashier name, and discount details
- **Sales history** with a date filter and totals (cashiers only see their own sales)
- **Dashboard (admin)**: today's sales, best sellers, low-stock alerts, recent sales

## Default accounts

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| Cashier | `cashier` | `cashier123` |

**Change these before you deploy online.** Log in as admin -> **Users** -> create your own admin account, log out, log in with the new one, then delete the default accounts.

| Role | Can do |
|------|--------|
| Admin | Everything: Dashboard, New Sale, Products, Sales (all), Users |
| Cashier | New Sale and Sales (own sales only) |

## Project structure

```
pos-system/
├── login.php            Login page
├── logout.php           Logs out
├── index.php            Dashboard (admin)
├── products.php         READ   - list/search products (admin)
├── product_add.php      CREATE - add a product (admin)
├── product_edit.php     UPDATE - edit a product (admin)
├── product_delete.php   DELETE - delete a product (admin)
├── users.php            Add/delete users (admin)
├── pos.php              New Sale (cashier) screen
├── cart_action.php      Scan / add / + / - / remove / clear cart
├── checkout.php         Save the sale (transaction) + reduce stock
├── receipt.php          Printable receipt
├── sales.php            Sales history
├── database.sql         Tables + sample data (import this)
├── assets/style.css     Custom styles (theme colors at the top)
└── includes/
    ├── db_connect.php   Database connection (EDIT when deploying)
    ├── functions.php    Helpers (escape, peso, login checks, validation, scan parsing)
    ├── header.php       Navbar + login check
    ├── footer.php
    └── product_form.php Form shared by Add and Edit
```

## Database design

| Table | Purpose | Key fields |
|-------|---------|------------|
| `users` | Accounts that can log in | id (PK), full_name, username (unique), password_hash, role |
| `products` | Items for sale | id (PK), product_name, barcode (unique), category, price, stock, description |
| `sales` | One row per transaction | id (PK), receipt_no, subtotal, discount_type, discount_id_no, discount_amount, total_amount, amount_paid, change_amount, payment_method, cashier_id (FK), cashier_name |
| `sale_items` | Items inside a sale | id (PK), sale_id (FK), product_id (FK), product_name, price, quantity, subtotal |

Relationships: one `user` makes many `sales`; one `sale` has many `sale_items`; each `sale_item` points to a `product`.

## Security basics used (good to mention in your presentation)

- **Prepared statements** on every query, so user input cannot break the SQL (SQL injection)
- **`htmlspecialchars` (the `e()` function)** on everything printed, to stop XSS
- **`password_hash` / `password_verify`**, so passwords are never stored as plain text
- **Role checks** (`require_login()`, `require_admin()`) on every page
- **Database transaction** at checkout, so a sale is saved completely or not at all

---

## Run it on your computer (XAMPP)

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy the `pos-system` folder into `C:\xampp\htdocs\` (so it is `C:\xampp\htdocs\pos-system\index.php`).
3. Open **http://localhost/phpmyadmin** -> create a database named `pos_db`.
4. Click `pos_db` -> **Import** -> choose `database.sql` -> **Import**.
   (If you already imported an older version, importing again is fine: it drops and recreates the tables.)
5. Check `includes/db_connect.php` (defaults already work with XAMPP).
6. Open **http://localhost/pos-system/login.php** and log in.

## Deploy to InfinityFree

1. Test every feature locally first.
2. Make an account at https://www.infinityfree.net.
3. Client Area -> create a new **Hosting Account** (choose your free subdomain).
4. Control Panel -> **MySQL Databases** -> create a database. Copy the **hostname, username, password, and database name**.
5. Edit `includes/db_connect.php` with those 4 values.
6. **File Manager** -> open **htdocs/** -> upload everything from the `pos-system` folder (so `login.php` is directly inside `htdocs/`).
7. Open **phpMyAdmin** from the Control Panel -> select your database -> **Import** -> upload `database.sql`.
8. Visit your live link, log in, and test everything again.

> Tip: a new InfinityFree account can take a few minutes to activate. Do not wait until the last day.

## Upload to GitHub (submission requirement)

1. Create a free account at https://github.com, then **New repository** (e.g. `pos-system`).
2. On the repo page: **Add file -> Upload files**, drag in all project files **including `database.sql`**, then **Commit changes**.
3. Before uploading, replace your real hosting password in `db_connect.php` with a placeholder so it is not public.
4. Email your **live link** and **repository link** to your instructor (see the guidelines).

## How to make it yours

- Change `STORE_NAME` in `includes/functions.php`.
- Change the colors in `assets/style.css` (`--brand` and `--accent`).
- Edit the categories in `categories()` inside `includes/functions.php`.
- Replace the sample products with items that match your own store idea.

## Notes

- The Senior/PWD discount is simplified to a flat **20% off the subtotal** (`DISCOUNT_RATE` in `includes/functions.php`). Real stores apply extra rules (such as VAT exemption); mention this as a limitation if asked.

## Presentation guide (5-7 minutes)

1. **Intro (30s)**: what a POS is and what your store system does.
2. **Demo (3 min)**: log in as cashier -> scan items (try `3*10001`) -> apply Senior discount -> show the receipt -> log in as admin -> show dashboard, products, users.
3. **Database (1 min)**: show the 4 tables and relationships.
4. **Code highlight (1-2 min)**: `product_add.php` (INSERT with prepared statements) and `checkout.php` (transaction).
5. **Deployment (30s)**: show the live InfinityFree link.
