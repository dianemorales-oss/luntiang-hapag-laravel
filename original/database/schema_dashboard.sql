-- NOTE: This file is kept for historical reference only.
-- Database setup is now fully automated by db.php in the project
-- root — just open db.php in your browser once and it will create
-- the database, this table, and every other required table for you.
-- You do not need to run this .sql file manually anymore.
--
-- Run this against the woodcraft_customer_service database
-- to add the tables needed for the customer dashboard.

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) DEFAULT NULL,
    issue_description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS warranty_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    purchase_date DATE NULL,
    defect_description TEXT NOT NULL,
    status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS return_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) DEFAULT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'denied', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comments TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;
