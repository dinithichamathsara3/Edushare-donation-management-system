USE edushare_main;
ALTER TABLE users ADD COLUMN IF NOT EXISTS province VARCHAR(100),
ADD COLUMN IF NOT EXISTS district VARCHAR(100),
ADD COLUMN IF NOT EXISTS city VARCHAR(100),
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,
7),
ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,
7),
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(80);
CREATE TABLE IF NOT EXISTS institutions (
 user_id INT PRIMARY KEY,
institution_type ENUM('School',
'Piriven',
'Educational Organization',
'Other') NOT NULL,
registration_number VARCHAR(100) NOT NULL UNIQUE,
contact_person VARCHAR(100) NOT NULL,
description TEXT,
verification_document VARCHAR(80) NOT NULL,
verification_status ENUM('pending',
'approved',
'rejected',
'more_information') NOT NULL DEFAULT 'pending',
review_note TEXT,
verified_by INT,
verified_at DATETIME,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
ALTER TABLE items ADD COLUMN IF NOT EXISTS subject VARCHAR(100),
ADD COLUMN IF NOT EXISTS education_level VARCHAR(100),
ADD COLUMN IF NOT EXISTS location VARCHAR(150),
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,
7),
ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,
7),
ADD COLUMN IF NOT EXISTS delivery_method VARCHAR(100),
ADD COLUMN IF NOT EXISTS availability_date DATE;
CREATE TABLE IF NOT EXISTS requirements (
 id INT AUTO_INCREMENT PRIMARY KEY,
recipient_id INT NOT NULL,
category_id INT NOT NULL,
title VARCHAR(160) NOT NULL,
subject VARCHAR(100),
education_level VARCHAR(100),
quantity_required INT NOT NULL,
quantity_received INT NOT NULL DEFAULT 0,
description TEXT,
reason TEXT,
required_date DATE NOT NULL,
urgency ENUM('normal',
'high') NOT NULL DEFAULT 'normal',
urgency_status ENUM('not_required',
'pending',
'approved',
'rejected') NOT NULL DEFAULT 'not_required',
status ENUM('open',
'completed',
'cancelled') NOT NULL DEFAULT 'open',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(recipient_id) REFERENCES users(id),
FOREIGN KEY(category_id) REFERENCES categories(id)
) ENGINE=InnoDB;
ALTER TABLE requests ADD COLUMN IF NOT EXISTS quantity INT NOT NULL DEFAULT 1,
ADD COLUMN IF NOT EXISTS requirement_id INT NULL,
ADD COLUMN IF NOT EXISTS source ENUM('claim',
'offer') NOT NULL DEFAULT 'claim',
ADD COLUMN IF NOT EXISTS response_note VARCHAR(255),
ADD COLUMN IF NOT EXISTS stage ENUM('accepted',
'preparing',
'ready',
'delivered',
'completed') NOT NULL DEFAULT 'accepted';
CREATE TABLE IF NOT EXISTS tracking (
 id INT AUTO_INCREMENT PRIMARY KEY,
request_id INT NOT NULL,
status VARCHAR(40) NOT NULL,
actor VARCHAR(100) NOT NULL,
remarks VARCHAR(255),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(request_id) REFERENCES requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS admin_notices (
 id INT AUTO_INCREMENT PRIMARY KEY,
message VARCHAR(255) NOT NULL,
is_read TINYINT NOT NULL DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS contact_messages (
 id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
email VARCHAR(120) NOT NULL,
message TEXT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT IGNORE INTO categories(name) VALUES ('Exercise Books'),
('School Bags'),
('Calculators'),
('Mathematical Instruments'),
('Educational Equipment'),
('Other Educational Items');
UPDATE requests SET stage='completed' WHERE status='completed';
ALTER TABLE items MODIFY COLUMN status ENUM('pending',
'approved',
'reserved',
'donated',
'rejected',
'cancelled') NOT NULL DEFAULT 'pending';
CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(80) PRIMARY KEY,
applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
UPDATE requests r JOIN items b ON b.id=r.item_id SET r.quantity=b.quantity WHERE NOT EXISTS (SELECT 1 FROM schema_migrations WHERE version='partial_quantities_v1');
INSERT IGNORE INTO schema_migrations(version) VALUES ('partial_quantities_v1');
