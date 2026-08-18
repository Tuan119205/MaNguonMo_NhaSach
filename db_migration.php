<?php
  /**
   * Database Migration
   * This file checks and updates the database schema
   */

  function runDatabaseMigrations() {
    require_once __DIR__ . "/functions/database_functions.php";
    $conn = db_connect();

    // Migration 0: Convert tables to utf8mb4 for Vietnamese support
    @mysqli_query($conn, "ALTER TABLE orders CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_query($conn, "ALTER TABLE order_items CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_query($conn, "ALTER TABLE customers CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_query($conn, "ALTER TABLE books CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_query($conn, "ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_query($conn, "ALTER TABLE admin CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_query($conn, "ALTER TABLE publisher CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_query($conn, "ALTER TABLE user_addresses CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_query($conn, "ALTER TABLE user_orders CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_query($conn, "ALTER DATABASE obs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Migration 1: Add a numeric book ID generated automatically from 1.
    // Keep book_isbn unchanged because existing orders reference it.
    $checkBookIdQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='books' AND COLUMN_NAME='book_id'";
    $bookIdResult = mysqli_query($conn, $checkBookIdQuery);
    if (!$bookIdResult || mysqli_num_rows($bookIdResult) === 0) {
      mysqli_query($conn, "ALTER TABLE books ADD COLUMN book_id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE FIRST");
    }

    // Migration 2: Expand amount and book_price to DECIMAL(12,2) for VND
    @mysqli_query($conn, "ALTER TABLE orders MODIFY COLUMN amount DECIMAL(12,2) DEFAULT 0.00");
    @mysqli_query($conn, "ALTER TABLE order_items MODIFY COLUMN item_price DECIMAL(12,2) DEFAULT 0.00");
    @mysqli_query($conn, "ALTER TABLE books MODIFY COLUMN book_price DECIMAL(12,2) DEFAULT 0.00");

    // Migration 3: Add order_status column to orders table if it doesn't exist
    $checkColumnQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='order_status'";
    $result = mysqli_query($conn, $checkColumnQuery);

    if (!$result || mysqli_num_rows($result) === 0) {
      $alterQuery = "ALTER TABLE orders ADD COLUMN order_status VARCHAR(50) NOT NULL DEFAULT 'chờ_xử_lý' AFTER amount";
      mysqli_query($conn, $alterQuery);
    }

    // Migration 4: Add payment_method column to orders table
    $checkPayQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='payment_method'";
    $payResult = mysqli_query($conn, $checkPayQuery);
    if (!$payResult || mysqli_num_rows($payResult) === 0) {
      $alterQuery = "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT 'cod' AFTER order_status";
      mysqli_query($conn, $alterQuery);
    }

    // Migration 5: Add notes and ship_phone columns to orders
    $checkPhoneQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                       WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='ship_phone'";
    $phoneResult = mysqli_query($conn, $checkPhoneQuery);
    if (!$phoneResult || mysqli_num_rows($phoneResult) === 0) {
      $alterQuery = "ALTER TABLE orders ADD COLUMN ship_phone VARCHAR(20) DEFAULT NULL AFTER ship_name";
      mysqli_query($conn, $alterQuery);
    }

    $checkNotesQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                       WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='notes'";
    $notesResult = mysqli_query($conn, $checkNotesQuery);
    if (!$notesResult || mysqli_num_rows($notesResult) === 0) {
      $alterQuery = "ALTER TABLE orders ADD COLUMN notes TEXT DEFAULT NULL AFTER ship_country";
      mysqli_query($conn, $alterQuery);
    }

    // Migration 6: Check if user_addresses table exists
    $checkTableQuery = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses'";
    $result = mysqli_query($conn, $checkTableQuery);

    if (!$result || mysqli_num_rows($result) === 0) {
      $createTableQuery = "CREATE TABLE IF NOT EXISTS user_addresses (
        address_id INT AUTO_INCREMENT PRIMARY KEY,
        userid INT NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        street_address VARCHAR(255) NOT NULL,
        city VARCHAR(50) NOT NULL,
        postal_code VARCHAR(20) NOT NULL,
        country VARCHAR(50) NOT NULL DEFAULT 'Việt Nam',
        is_default BOOLEAN DEFAULT FALSE,
        payment_method ENUM('cod', 'transfer') DEFAULT 'cod',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userid) REFERENCES users(userid) ON DELETE CASCADE
      ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      mysqli_query($conn, $createTableQuery);
    }

    // Migration 7: Add inventory column to books for stock management
    $checkInventoryQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='books' AND COLUMN_NAME='inventory'";
    $inventoryResult = mysqli_query($conn, $checkInventoryQuery);
      if (!$inventoryResult || mysqli_num_rows($inventoryResult) === 0) {
        mysqli_query($conn, "ALTER TABLE books ADD COLUMN inventory INT UNSIGNED NOT NULL DEFAULT 0 AFTER book_price");
      }

      // Migration 8: Create the promotions table for admin-managed vouchers
      $createPromotionsTable = "CREATE TABLE IF NOT EXISTS promotions (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        code VARCHAR(50) NOT NULL,
        name VARCHAR(120) NOT NULL,
        type ENUM('percent','fixed','shipping','special') NOT NULL DEFAULT 'percent',
        value DECIMAL(12,2) NOT NULL DEFAULT 0,
        min_order DECIMAL(12,2) NOT NULL DEFAULT 0,
        expires_at DATE NOT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uq_promotions_code (code)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
      mysqli_query($conn, $createPromotionsTable);

      $promotionCountResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM promotions");
      if ($promotionCountResult && (int)mysqli_fetch_assoc($promotionCountResult)['total'] === 0) {
        mysqli_query($conn, "INSERT INTO promotions (code,name,type,value,min_order,expires_at,active) VALUES
          ('SAVE10','Giảm 10%','percent',10,0,'2026-12-31',1),
          ('SAVE20','Giảm 20% đơn từ 500.000đ','percent',20,500000,'2026-12-31',1),
          ('FIRST50','Giảm 50.000đ cho khách hàng mới','fixed',50000,0,'2026-10-31',1),
          ('FREESHIP','Miễn phí vận chuyển','shipping',0,0,'2026-09-30',1),
          ('WEEKEND','Ưu đãi cuối tuần','percent',15,50000,'2026-12-30',1)");
      }

    }
?>
