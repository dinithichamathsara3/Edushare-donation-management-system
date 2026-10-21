-- EduShare: Book & Education Items Donation Management System
-- Two databases behind ONE login page: main = donors/recipients/items, admin = staff
CREATE DATABASE IF NOT EXISTS edushare_main  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS edushare_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edushare_main;
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
email VARCHAR(120) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
phone VARCHAR(20),
address VARCHAR(255),
institution VARCHAR(120),
role ENUM('recipient',
'donor') NOT NULL,
status ENUM('active',
'blocked') NOT NULL DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
donor_id INT NOT NULL,
category_id INT NOT NULL,
item_type ENUM('book',
'education_item') NOT NULL DEFAULT 'book',
title VARCHAR(160) NOT NULL,
brand VARCHAR(120),
quantity INT NOT NULL DEFAULT 1,
description TEXT,
condition_status ENUM('New',
'Good',
'Fair') NOT NULL DEFAULT 'Good',
type ENUM('donate',
'exchange') NOT NULL DEFAULT 'donate',
image VARCHAR(80),
status ENUM('pending',
'approved',
'reserved',
'donated',
'rejected') NOT NULL DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
item_id INT NOT NULL,
recipient_id INT NOT NULL,
message VARCHAR(255),
status ENUM('pending',
'approved',
'rejected',
'completed',
'cancelled') NOT NULL DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
message VARCHAR(255) NOT NULL,
link VARCHAR(160),
is_read TINYINT(1) NOT NULL DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
INSERT IGNORE INTO categories (name) VALUES ('Textbooks'),
('Science & Maths'),
('Technology & IT'),
('Literature'),
('Business'),
('Languages'),
('Exam Papers & Notes'),
('Stationery'),
('School Uniforms & Bags'),
('Calculators & Instruments'),
('Laptops & Tablets'),
('Lab Equipment'),
('Art Supplies');
USE edushare_admin;
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
email VARCHAR(120) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
admin_id INT NOT NULL,
action VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;
