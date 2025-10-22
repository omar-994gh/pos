@echo off
REM ================================
REM Batch Script: إنشاء بنية المشروع (بما في ذلك سكيمة القاعدة والملفات الأساسية)
REM ================================

REM --- إنشاء المجلدات ---
mkdir public
mkdir public\css
mkdir public\js
mkdir public\images
mkdir src
mkdir config
mkdir assets
mkdir docs

REM --- إنشاء ملفات الواجهة العامة (public) ---
type nul > public\index.html
type nul > public\login.php
type nul > public\register.php
type nul > public\dashboard.php
type nul > public\items.php
type nul > public\item\_form.php
type nul > public\groups.php
type nul > public\group\_form.php
type nul > public\printers.php
type nul > public\printer\_form.php
type nul > public\pos.php
type nul > public\warehouse\_entry\_in.php
type nul > public\warehouse\_entry\_out.php
type nul > public\warehouse\_log.php
type nul > public\sales\_log.php
type nul > public\invoice\_settings.php

REM --- إنشاء ملفات الموارد (CSS/JS/Images) ---
type nul > public\css\style.css
type nul > public\js\app.js
type nul > public\images\logo.png

REM --- إنشاء ملفات PHP الأساسية (src) ---
type nul > src\init.php
type nul > src\db.php
type nul > src\Auth.php
type nul > src\User.php
type nul > src\Item.php
type nul > src\Group.php
type nul > src\Printer.php
type nul > src\Order.php
type nul > src\Warehouse.php
type nul > src\print\_helper.php

REM --- إنشاء ملفات الإعدادات (config) ---
type nul > config\db.php
type nul > config\schema.sql

REM --- كتابة سكيمة قاعدة البيانات إلى config\schema.sql ---
echo -- SQLite schema for POS system > config\schema.sql
echo CREATE TABLE IF NOT EXISTS Users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password\_hash TEXT NOT NULL, role TEXT NOT NULL, created\_at DATETIME DEFAULT CURRENT\_TIMESTAMP); >> config\schema.sql
echo CREATE TABLE IF NOT EXISTS Groups (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, printer\_id INTEGER, FOREIGN KEY(printer\_id) REFERENCES Printers(id)); >> config\schema.sql
echo CREATE TABLE IF NOT EXISTS Printers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, address TEXT NOT NULL, created\_at DATETIME DEFAULT CURRENT\_TIMESTAMP); >> config\schema.sql
echo CREATE TABLE IF NOT EXISTS Items (id INTEGER PRIMARY KEY AUTOINCREMENT, name\_ar TEXT NOT NULL, name\_en TEXT, barcode TEXT UNIQUE, group\_id INTEGER, price REAL NOT NULL, stock REAL DEFAULT 0, unit TEXT, FOREIGN KEY(group\_id) REFERENCES Groups(id)); >> config\schema.sql
echo CREATE TABLE IF NOT EXISTS Orders (id INTEGER PRIMARY KEY AUTOINCREMENT, user\_id INTEGER, total REAL NOT NULL, created\_at DATETIME DEFAULT CURRENT\_TIMESTAMP, FOREIGN KEY(user\_id) REFERENCES Users(id)); >> config\schema.sql
echo CREATE TABLE IF NOT EXISTS Order\_Items (order\_id INTEGER, item\_id INTEGER, quantity REAL NOT NULL, unit\_price REAL NOT NULL, PRIMARY KEY(order\_id, item\_id), FOREIGN KEY(order\_id) REFERENCES Orders(id), FOREIGN KEY(item\_id) REFERENCES Items(id)); >> config\schema.sql
echo CREATE TABLE IF NOT EXISTS Warehouses (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL); >> config\schema.sql
echo CREATE TABLE IF NOT EXISTS Warehouse\_Entries (id INTEGER PRIMARY KEY AUTOINCREMENT, warehouse\_id INTEGER, item\_id INTEGER, supplier TEXT, date DATETIME NOT NULL, qty REAL NOT NULL, unit\_price REAL NOT NULL, total\_price REAL NOT NULL, entry\_type TEXT NOT NULL, FOREIGN KEY(warehouse\_id) REFERENCES Warehouses(id), FOREIGN KEY(item\_id) REFERENCES Items(id)); >> config\schema.sql
echo CREATE TABLE IF NOT EXISTS Invoice\_Settings (id INTEGER PRIMARY KEY AUTOINCREMENT, field\_logo INTEGER DEFAULT 1, field\_name INTEGER DEFAULT 1, field\_date INTEGER DEFAULT 1, field\_time INTEGER DEFAULT 1, field\_user INTEGER DEFAULT 1, field\_barcode INTEGER DEFAULT 0, field\_item\_name\_ar INTEGER DEFAULT 1, field\_item\_name\_en INTEGER DEFAULT 0, paper\_width INTEGER NOT NULL); >> config\schema.sql

REM --- إنشاء ملفات الاتصال والتهيئة ---
echo ^<?php> config\db.php
echo // PDO connection to SQLite database >> config\db.php
echo try { >> config\db.php
echo     $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite'); >> config\db.php
echo     $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); >> config\db.php
echo } catch (PDOException $e) { >> config\db.php
echo     die('Connection failed: ' . $e->getMessage()); >> config\db.php
echo ^?> >> config\db.php

echo ^<?php> src\init.php
echo session_start(); >> src\init.php
echo require_once __DIR__ . '/../config/db.php'; >> src\init.php
echo // Autoload classes if using PSR-4 or simple requires >> src\init.php
echo function isLoggedIn() { >> src\init.php
echo     return isset($_SESSION['user_id']); >> src\init.php
echo } >> src\init.php
echo function requireLogin() { >> src\init.php
echo     if (!isLoggedIn()) { >> src\init.php
echo         header('Location: login.php'); exit; >> src\init.php
echo     } >> src\init.php
echo } >> src\init.php
echo ^?> >> src\init.php

REM --- إنشاء مكتبات ومحاكيات Bootstrap (assets) ---
type nul > assets\bootstrap.min.css
type nul > assets\bootstrap.min.js

REM --- إنشاء ملفات التوثيق ---
type nul > docs\README.md

echo Project structure, database schema, and basic config files created successfully.
pause
