# Pharmacy Management System

This repository contains two separately configured snapshots of a PHP/MySQL pharmacy management application:

- `main-project/` — the main local version.
- `test-project/` — the test version.

The 56 application and asset files in these two snapshots are identical. Their local `config.php` files were intentionally kept out of this repository because they use separate database settings. Each folder includes a safe `config.example.php` to copy for local setup.

## Requirements

- PHP with the `mysqli` extension
- MySQL or MariaDB
- Apache (for example, through XAMPP, MAMP, or a similar local PHP stack)

## Database setup

1. Start Apache and MySQL/MariaDB.
2. In phpMyAdmin, create two local databases, for example `pharmacy_main` and `pharmacy_test`.
3. Select each database and import [`database/pharmacy.sql`](database/pharmacy.sql). The dump contains the application schema and synthetic non-authentication sample data. It omits admin/pharmacist password records and sales transaction rows.
4. In each project folder, copy `config.example.php` to `config.php` and set the local host, username, password, and database name. Use the database created for that specific copy. Keep `config.php` local; it is excluded by `.gitignore`.
5. Create your own local admin and pharmacist login records with unique passwords before using the login pages. The schema dump intentionally does not provide shared demo credentials.

## Run the main version

Place or symlink `main-project/` under your web server document root and open `http://localhost/main-project/mainpage.php` (or update the URL to match the folder name you choose).

## Run the test version

Place or symlink `test-project/` under your web server document root and open `http://localhost/test-project/mainpage.php`.

Use separate config files and databases so test activity cannot affect the main database.

## Local configuration

`config.example.php` is a template only. Copy it to `config.php` separately in each project folder and replace the placeholder password and database name with values from your local MySQL setup. Do not commit real credentials.
