<?php
/**
 * db.php
 * ------------------------------------------------------------------
 * Luntiang H.A.P.A.G. — Automatic Database Initializer
 * ------------------------------------------------------------------
 * Visit this file directly in your browser (or run it once via CLI)
 * after importing the project into XAMPP:
 *
 *      http://localhost/site/db.php
 *
 * It will:
 *   1. Connect to MySQL using the credentials below.
 *   2. Create the project database if it doesn't already exist.
 *   3. Create every table this project needs (with the correct
 *      columns, keys, foreign keys, and default values) if they
 *      don't already exist.
 *   4. Report a summary of what it did.
 *
 * It is 100% safe to run multiple times — every statement uses
 * "IF NOT EXISTS", so nothing gets dropped or duplicated.
 *
 * This file is also loaded automatically by config.php on every
 * request, so the schema self-heals even if you forget to run it
 * manually. Running it directly just gives you a visual report.
 *
 * The schema below was reverse-engineered directly from the SQL
 * queries found in the project's PHP files (register.php, login.php,
 * submit-ticket.php, warranty-request.php, returns-refund.php,
 * feedback.php, my-profile.php) so it matches exactly what the
 * application expects — no guessed columns.
 * ------------------------------------------------------------------
 */

// ---------------------------------------------------------------
// 1. Connection settings
//    Change these if your MySQL credentials differ on this machine.
//    Every other file (config.php) reuses these same values, so you
//    only ever need to edit them in one place.
// ---------------------------------------------------------------
$host     = "localhost";
$dbname   = "luntiang-hapag";
$username = "root";
$password = ""; // Change this if your MySQL root account has a password
$charset  = "utf8mb4";

// Only produce a full HTML report when this file is opened directly
// in the browser. When included from config.php, stay silent.
$isDirectRequest = basename($_SERVER['SCRIPT_NAME']) === 'db.php';

$report = [];

try {
    // -----------------------------------------------------------
    // 2. Connect to the MySQL server WITHOUT selecting a database
    //    yet, because the database itself might not exist.
    // -----------------------------------------------------------
    $serverConn = new PDO(
        "mysql:host=$host;charset=$charset",
        $username,
        $password
    );
    $serverConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // -----------------------------------------------------------
    // 3. Create the database if it does not already exist.
    // -----------------------------------------------------------
    $serverConn->exec(
        "CREATE DATABASE IF NOT EXISTS `$dbname`
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    $report[] = "Database `$dbname` is ready.";

    // -----------------------------------------------------------
    // 4. Reconnect, this time selecting the database, so all
    //    further statements (and the app itself) run against it.
    // -----------------------------------------------------------
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // -----------------------------------------------------------
    // 5b. Small helper: safely add a column to a table only if it
    //     doesn't already exist yet. MySQL/MariaDB on XAMPP can be
    //     old enough not to support "ADD COLUMN IF NOT EXISTS", so
    //     we check information_schema first. This lets us evolve
    //     tables that were created by an earlier version of this
    //     file without ever dropping existing data.
    // -----------------------------------------------------------
    function addColumnIfMissing(PDO $conn, string $dbname, string $table, string $column, string $definition): void
    {
        $check = $conn->prepare("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $check->execute([$dbname, $table, $column]);
        if ((int)$check->fetchColumn() === 0) {
            $conn->exec("ALTER TABLE `$table` ADD COLUMN $definition");
        }
    }

    // -----------------------------------------------------------
    // 5c. Small helper: widen a column to TEXT if it isn't already.
    //     The attachment path columns below used to hold a single
    //     VARCHAR(255) path; now that customers can attach multiple
    //     files per field, they hold a JSON-encoded array of paths
    //     instead, which can outgrow 255 characters. Safe to run on
    //     every request — it's a no-op once the column is TEXT.
    // -----------------------------------------------------------
    function widenColumnToText(PDO $conn, string $dbname, string $table, string $column): void
    {
        $check = $conn->prepare("
            SELECT DATA_TYPE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $check->execute([$dbname, $table, $column]);
        $currentType = $check->fetchColumn();
        if ($currentType !== false && strtolower((string)$currentType) !== 'text') {
            $conn->exec("ALTER TABLE `$table` MODIFY `$column` TEXT NULL");
        }
    }

    // -----------------------------------------------------------
    // 5. TABLE: users
    //    Required by: register.php, login.php, my-profile.php
    //    - email must be unique (register.php checks for duplicates)
    //    - password stores a password_hash() value
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            address VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_users_email (email),
            UNIQUE KEY uq_users_phone (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    addColumnIfMissing($conn, $dbname, 'users', 'reset_token', "reset_token VARCHAR(64) NULL AFTER password");
    addColumnIfMissing($conn, $dbname, 'users', 'reset_token_expires', "reset_token_expires DATETIME NULL AFTER reset_token");
    $report[] = "Table `users` is ready.";

    // -----------------------------------------------------------
    // 6. TABLE: admins
    //    Required by: admin/admin-login.php and every other admin/*
    //    page. Holds real admin accounts (instead of the old
    //    no-login mockup) authenticated with password_verify().
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'Admin',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_admins_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `admins` is ready.";

    // -----------------------------------------------------------
    // 7. TABLE: tickets
    //    Required by: submit-ticket.php (INSERT), my-profile.php
    //    (SELECT), admin/admin-tickets.php + admin-ticket-detail.php
    //    (SELECT/UPDATE). subject/category/admin_reply/replied_at
    //    were added to support the admin reply workflow.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) DEFAULT NULL,
            issue_description TEXT NOT NULL,
            status ENUM('open', 'in_progress', 'resolved', 'closed')
                NOT NULL DEFAULT 'open',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_tickets_user_id (user_id),
            CONSTRAINT fk_tickets_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    addColumnIfMissing($conn, $dbname, 'tickets', 'subject', "subject VARCHAR(150) NOT NULL DEFAULT 'General Inquiry' AFTER user_id");
    addColumnIfMissing($conn, $dbname, 'tickets', 'category', "category VARCHAR(50) NOT NULL DEFAULT 'General' AFTER subject");
    // priority and attachment_path support the Submit a Ticket form's
    // Priority dropdown and optional Attachment upload. Nullable/defaulted
    // so existing rows keep working without errors.
    addColumnIfMissing($conn, $dbname, 'tickets', 'priority', "priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium' AFTER category");
    addColumnIfMissing($conn, $dbname, 'tickets', 'attachment_path', "attachment_path VARCHAR(255) NULL AFTER issue_description");
    widenColumnToText($conn, $dbname, 'tickets', 'attachment_path');
    addColumnIfMissing($conn, $dbname, 'tickets', 'admin_reply', "admin_reply TEXT NULL AFTER status");
    addColumnIfMissing($conn, $dbname, 'tickets', 'replied_at', "replied_at TIMESTAMP NULL DEFAULT NULL AFTER admin_reply");
    $report[] = "Table `tickets` is ready.";

    // -----------------------------------------------------------
    // 7b. TABLE: ticket_replies
    //     Required by: admin/admin-ticket-detail.php (INSERT/SELECT)
    //     and ticket-view.php (INSERT/SELECT). Holds the full,
    //     ordered two-way conversation for a ticket — both customer
    //     follow-ups and admin replies — instead of the single
    //     admin_reply column tickets used to have.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS ticket_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            sender_type ENUM('customer', 'admin') NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ticket_replies_ticket_id (ticket_id),
            CONSTRAINT fk_ticket_replies_ticket
                FOREIGN KEY (ticket_id) REFERENCES tickets(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `ticket_replies` is ready.";

    // One-time migration: any older ticket that still has its reply
    // sitting in tickets.admin_reply (from before ticket_replies
    // existed) gets that reply copied into the thread, so nothing
    // written under the old system is lost. Safe to run every
    // request — the NOT IN subquery means an already-migrated
    // ticket is never duplicated.
    $conn->exec("
        INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at)
        SELECT id, 'admin', admin_reply, COALESCE(replied_at, created_at)
        FROM tickets
        WHERE admin_reply IS NOT NULL
          AND admin_reply <> ''
          AND id NOT IN (SELECT ticket_id FROM ticket_replies WHERE sender_type = 'admin')
    ");

    // -----------------------------------------------------------
    // 8. TABLE: warranty_requests
    //    Required by: warranty-request.php (INSERT), my-profile.php
    //    (SELECT), admin/admin-warranty.php (SELECT/UPDATE)
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS warranty_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_name VARCHAR(150) NOT NULL,
            defect_description TEXT NOT NULL,
            status ENUM('pending', 'approved', 'denied')
                NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_warranty_user_id (user_id),
            CONSTRAINT fk_warranty_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // purchase_date captures when the customer bought the product, used
    // by the admin side to judge warranty eligibility against the 5-year
    // policy. Nullable so existing rows created before this column
    // existed keep working without errors; new submissions always set it
    // (warranty-request.php requires it before allowing submission).
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'purchase_date', "purchase_date DATE NULL AFTER product_name");
    // order_number and quality_issue support the Freshness Request form's
    // Order Number field and Quality Issue dropdown. proof_of_purchase_path
    // and damage_photo_path store the uploaded supporting files. All are
    // nullable so existing rows keep working without errors; the form
    // itself requires order_number/quality_issue/proof of purchase before
    // allowing submission.
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'order_number', "order_number VARCHAR(50) NULL AFTER product_name");
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'quality_issue', "quality_issue VARCHAR(100) NULL AFTER purchase_date");
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'proof_of_purchase_path', "proof_of_purchase_path VARCHAR(255) NULL AFTER defect_description");
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'damage_photo_path', "damage_photo_path VARCHAR(255) NULL AFTER proof_of_purchase_path");
    widenColumnToText($conn, $dbname, 'warranty_requests', 'proof_of_purchase_path');
    widenColumnToText($conn, $dbname, 'warranty_requests', 'damage_photo_path');
    // admin_note lets an admin leave a (multi-line) update/instruction for
    // the customer without opening a full conversation thread. updated_at
    // tracks the last time the request's status or note changed, so the
    // customer's "Last Updated" display always reflects the latest admin
    // action, not just the original submission date.
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'admin_note', "admin_note TEXT NULL AFTER status");
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'updated_at', "updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    $report[] = "Table `warranty_requests` is ready.";

    // -----------------------------------------------------------
    // 9. TABLE: return_requests
    //    Required by: returns-refund.php (INSERT), my-profile.php
    //    (SELECT), admin/admin-returns.php (SELECT/UPDATE)
    //    order_number and reason are always supplied by the form,
    //    so both are NOT NULL here.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS return_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending', 'approved', 'denied', 'completed')
                NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_returns_user_id (user_id),
            CONSTRAINT fk_returns_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // product_name, purchase_date, reason_category, and product_condition
    // support the Return & Refund form's added fields. `reason` (already
    // NOT NULL above) continues to hold the Detailed Explanation textarea,
    // while reason_category holds the short dropdown selection. The two
    // path columns store the uploaded supporting files. All are nullable
    // so existing rows keep working without errors; the form itself
    // requires them before allowing submission.
    addColumnIfMissing($conn, $dbname, 'return_requests', 'product_name', "product_name VARCHAR(150) NULL AFTER order_number");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'purchase_date', "purchase_date DATE NULL AFTER product_name");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'reason_category', "reason_category VARCHAR(50) NULL AFTER purchase_date");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'product_condition', "product_condition VARCHAR(20) NULL AFTER reason");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'proof_of_purchase_path', "proof_of_purchase_path VARCHAR(255) NULL AFTER product_condition");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'damage_photo_path', "damage_photo_path VARCHAR(255) NULL AFTER proof_of_purchase_path");
    widenColumnToText($conn, $dbname, 'return_requests', 'proof_of_purchase_path');
    widenColumnToText($conn, $dbname, 'return_requests', 'damage_photo_path');
    // Same Admin Note / Last Updated pattern used on warranty_requests —
    // see the comment there for why these two columns exist.
    addColumnIfMissing($conn, $dbname, 'return_requests', 'admin_note', "admin_note TEXT NULL AFTER status");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'updated_at', "updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    $report[] = "Table `return_requests` is ready.";

    // -----------------------------------------------------------
    // 9b. TABLE: notifications
    //     Required by: includes/notifications.php (INSERT helper used
    //     by submit-ticket.php, ticket-view.php, warranty-request.php,
    //     returns-refund.php, admin/admin-warranty.php,
    //     admin/admin-returns.php) and admin/notifications.php +
    //     admin/includes/admin-topbar.php (SELECT/UPDATE). Notifies
    //     admins of important customer activity (new ticket, ticket
    //     reply/reopen/close, new warranty/return requests, status
    //     changes). Global (not tied to a single admin account) since
    //     any admin should be able to see and act on them.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            related_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            customer_name VARCHAR(150) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_notifications_is_read (is_read),
            KEY idx_notifications_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `notifications` is ready.";

    // -----------------------------------------------------------
    // 10. TABLE: feedback
    //     Required by: feedback.php (INSERT, logged-in),
    //     contact-support.php (INSERT, guests allowed too),
    //     my-profile.php (SELECT), admin/admin-feedback.php
    //     (SELECT/DELETE).
    //     user_id is nullable so guests (not logged in) can leave
    //     feedback from the Contact Support page; guest_name/
    //     guest_email capture who they are in that case.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comments TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_feedback_user_id (user_id),
            CONSTRAINT fk_feedback_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT chk_feedback_rating CHECK (rating BETWEEN 1 AND 5)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // Allow guest feedback: user_id must be nullable (older installs
    // may have created it as NOT NULL before this update).
    $conn->exec("ALTER TABLE feedback MODIFY user_id INT NULL");
    addColumnIfMissing($conn, $dbname, 'feedback', 'guest_name', "guest_name VARCHAR(150) NULL AFTER user_id");
    addColumnIfMissing($conn, $dbname, 'feedback', 'guest_email', "guest_email VARCHAR(150) NULL AFTER guest_name");
    addColumnIfMissing($conn, $dbname, 'feedback', 'subject', "subject VARCHAR(150) NULL AFTER guest_email");
    $report[] = "Table `feedback` is ready.";

    // -----------------------------------------------------------
    // 11. TABLE: live_chat_messages
    //     Required by: live-chat.php (customer widget) and
    //     admin/admin-live-chat.php. Conversations are grouped by
    //     `chat_key` (the customer's PHP session ID) so both logged
    //     in users and guests can chat; customer_name is stored
    //     per-message so admin sees a readable name even for guests.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS live_chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chat_key VARCHAR(64) NOT NULL,
            user_id INT NULL,
            customer_name VARCHAR(150) NOT NULL,
            sender ENUM('customer', 'admin') NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_chat_key (chat_key),
            KEY idx_chat_user_id (user_id),
            CONSTRAINT fk_chat_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // Widen the sender enum to add 'bot', for the AI assistant that now
    // greets and triages every conversation before a human joins in.
    // Existing 'customer'/'admin' rows and every query that already
    // compares sender = 'admin' / sender = 'customer' are unaffected.
    $conn->exec("ALTER TABLE live_chat_messages MODIFY sender ENUM('customer', 'admin', 'bot') NOT NULL");
    $report[] = "Table `live_chat_messages` is ready.";

    // -----------------------------------------------------------
    // 11b. TABLE: chat_bot_state
    //      One row per chat_key. Tracks whether the AI assistant is
    //      still handling this conversation (bot_active) and any
    //      pending follow-up question it's mid-way through asking
    //      (pending_intent / pending_context), so a multi-turn
    //      clarification ("was the crack there on delivery, or did
    //      it happen after use?") survives across separate
    //      chat-send.php requests. Required by: includes/
    //      chatbot-engine.php, chat-send.php.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS chat_bot_state (
            chat_key VARCHAR(64) NOT NULL PRIMARY KEY,
            bot_active TINYINT(1) NOT NULL DEFAULT 1,
            pending_intent VARCHAR(50) NULL,
            pending_context TEXT NULL,
            last_topic VARCHAR(100) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // Add last_topic to installs created before this column existed.
    $lastTopicCol = $conn->query("SHOW COLUMNS FROM chat_bot_state LIKE 'last_topic'")->fetch();
    if (!$lastTopicCol) {
        $conn->exec("ALTER TABLE chat_bot_state ADD COLUMN last_topic VARCHAR(100) NULL AFTER pending_context");
    }
    $report[] = "Table `chat_bot_state` is ready.";

    // -----------------------------------------------------------
    // 13. TABLE: faqs
    //     Required by: admin/admin-faq.php (full CRUD) and could be
    //     used to drive the public faq.php page in the future.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS faqs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question VARCHAR(255) NOT NULL,
            answer TEXT NOT NULL,
            category VARCHAR(50) NOT NULL DEFAULT 'General',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `faqs` is ready.";

    // -----------------------------------------------------------
    // 🌱 E-COMMERCE TABLES
    // -----------------------------------------------------------

    // 14. TABLE: categories
    $conn->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NULL,
            image VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `categories` is ready.";

    // 15. TABLE: products (full e-commerce)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NULL,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            variety VARCHAR(200) NULL,
            description TEXT NULL,
            price DECIMAL(10,2) NOT NULL,
            unit VARCHAR(50) NOT NULL DEFAULT 'per cup',
            image VARCHAR(255) NULL,
            image_2 VARCHAR(255) NULL,
            image_3 VARCHAR(255) NULL,
            calories INT NULL,
            protein DECIMAL(5,1) NULL,
            fiber DECIMAL(5,1) NULL,
            vitamin_a VARCHAR(50) NULL,
            vitamin_c VARCHAR(50) NULL,
            best_for TEXT NULL,
            storage_instructions TEXT NULL,
            shelf_life VARCHAR(100) NULL,
            harvest_time VARCHAR(100) NULL DEFAULT '1-3 hours after order',
            plants_available INT NOT NULL DEFAULT 0,
            is_best_seller TINYINT(1) NOT NULL DEFAULT 0,
            is_new TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_products_category (category_id),
            KEY idx_products_active (is_active),
            CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `products` is ready.";

    // 16. TABLE: orders
    $conn->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(20) NOT NULL UNIQUE,
            status ENUM('preparing','ready','delivered','completed','cancelled','refund_requested','replacement_requested') NOT NULL DEFAULT 'preparing',
            subtotal DECIMAL(10,2) NOT NULL,
            delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total DECIMAL(10,2) NOT NULL,
            delivery_method ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
            payment_method VARCHAR(50) NOT NULL DEFAULT 'cod',
            promo_code VARCHAR(50) NULL,
            delivery_address TEXT NULL,
            delivery_city VARCHAR(100) NULL,
            delivery_province VARCHAR(100) NULL,
            delivery_zip VARCHAR(20) NULL,
            delivery_notes TEXT NULL,
            gift_note TEXT NULL,
            preferred_delivery_time VARCHAR(100) NULL,
            is_free_delivery TINYINT(1) NOT NULL DEFAULT 0,
            estimated_harvest_time VARCHAR(100) NULL,
            customer_name VARCHAR(200) NULL,
            customer_email VARCHAR(150) NULL,
            customer_phone VARCHAR(30) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_orders_user (user_id),
            KEY idx_orders_status (status),
            KEY idx_orders_created (created_at),
            CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `orders` is ready.";

    // Migrate old order status ENUM to new 4-step flow
    try {
        $oldEnum = $conn->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'status'")->fetchColumn();
        if ($oldEnum && strpos($oldEnum, 'pending') !== false) {
            $conn->exec("ALTER TABLE orders MODIFY status ENUM('preparing','ready','delivered','completed','cancelled','refund_requested','replacement_requested') NOT NULL DEFAULT 'preparing'");
            // Migrate old status values
            $conn->exec("UPDATE orders SET status = 'preparing' WHERE status IN ('pending','payment_confirmed','harvest_queue','harvesting','quality_check','packing')");
            $conn->exec("UPDATE orders SET status = 'ready' WHERE status IN ('ready_pickup','out_delivery')");
            $report[] = "Orders status ENUM migrated to 4-step flow: preparing → ready → delivered → completed.";
        }
    } catch (Exception $e) { /* non-critical */ }

    // 17. TABLE: order_items
    $conn->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NULL,
            product_name VARCHAR(200) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            harvest_notes VARCHAR(200) NULL,
            KEY idx_order_items_order (order_id),
            CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `order_items` is ready.";

    // 18. TABLE: wishlist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_wishlist (user_id, product_id),
            KEY idx_wishlist_user (user_id),
            CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_wishlist_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `wishlist` is ready.";

    // 19. TABLE: reviews
    $conn->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            order_id INT NULL,
            rating TINYINT UNSIGNED NOT NULL,
# REMOVED FRESHNESS ENTRY
            freshness_rating TINYINT UNSIGNED NULL,
            packaging_rating TINYINT UNSIGNED NULL,
            delivery_rating TINYINT UNSIGNED NULL,
            comment TEXT NULL,
            photos TEXT NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            is_approved TINYINT(1) NOT NULL DEFAULT 0,
            helpful_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_reviews_product (product_id),
            KEY idx_reviews_user (user_id),
            CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `reviews` is ready.";

    // 20. TABLE: promotions
    $conn->exec("
        CREATE TABLE IF NOT EXISTS promotions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            discount_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
            discount_value DECIMAL(10,2) NOT NULL,
            min_order DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            max_uses INT NULL,
            used_count INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_free_delivery TINYINT(1) NOT NULL DEFAULT 0,
            expires_at DATE NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `promotions` is ready.";

    // 21. TABLE: knowledge_base
    $conn->exec("
        CREATE TABLE IF NOT EXISTS knowledge_base (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content TEXT NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT 'General',
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `knowledge_base` is ready.";

    // 21b. TABLE: customer_addresses
    $conn->exec("
        CREATE TABLE IF NOT EXISTS customer_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            label VARCHAR(100) NULL DEFAULT 'Default',
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            province VARCHAR(100) NOT NULL,
            zip VARCHAR(20) NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_addresses_user (user_id),
            CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `customer_addresses` is ready.";

    // 21c. TABLE: claimed_coupons
    $conn->exec("
        CREATE TABLE IF NOT EXISTS claimed_coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            promotion_id INT NOT NULL,
            claimed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_claimed (user_id, promotion_id),
            KEY idx_claimed_user (user_id),
            CONSTRAINT fk_claimed_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_claimed_promo FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `claimed_coupons` is ready.";

    // 21d. TABLE: cart_items — persistent cart for logged-in users
    $conn->exec("
        CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_cart_user_product (user_id, product_id),
            KEY idx_cart_user (user_id),
            CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `cart_items` is ready.";

    // Add new notification types: add related_link column
    addColumnIfMissing($conn, $dbname, 'notifications', 'related_link', "related_link VARCHAR(255) NULL AFTER message");

    // Reviews: add admin_reply and admin_replied_at
    addColumnIfMissing($conn, $dbname, 'reviews', 'admin_reply', "admin_reply TEXT NULL AFTER helpful_count");
    addColumnIfMissing($conn, $dbname, 'reviews', 'admin_replied_at', "admin_replied_at TIMESTAMP NULL DEFAULT NULL AFTER admin_reply");

    // Live chat: add image_path for image uploads
    addColumnIfMissing($conn, $dbname, 'live_chat_messages', 'image_path', "image_path VARCHAR(500) NULL AFTER message");

    // Orders: add cancellation fields
    addColumnIfMissing($conn, $dbname, 'orders', 'cancellation_reason', "cancellation_reason VARCHAR(100) NULL AFTER status");
    addColumnIfMissing($conn, $dbname, 'orders', 'cancellation_notes', "cancellation_notes TEXT NULL AFTER cancellation_reason");
    addColumnIfMissing($conn, $dbname, 'orders', 'cancelled_at', "cancelled_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");

    // Reviews: add review_title
    addColumnIfMissing($conn, $dbname, 'reviews', 'review_title', "review_title VARCHAR(200) NULL AFTER rating");

    // -----------------------------------------------------------
    // SEED: Categories
    // -----------------------------------------------------------
    $catCount = (int)$conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount === 0) {
        $seedCat = $conn->prepare("INSERT INTO categories (name, slug, description, sort_order) VALUES (?,?,?,?)");
        $seedCat->execute(['Green Lettuce', 'green-lettuce', 'Crisp, classic green hydroponic lettuce varieties', 1]);
        $seedCat->execute(['Red Lettuce', 'red-lettuce', 'Vibrant red-tipped and burgundy lettuce varieties', 2]);
        $seedCat->execute(['Whole Lettuce', 'whole-lettuce', 'Single head lettuce cups — harvest on demand', 3]);
        $seedCat->execute(['Twin Packs', 'twin-packs', 'Two cups of the same variety — perfect for couples', 4]);
        $seedCat->execute(['Family Packs', 'family-packs', 'Four cups — ideal for family meals', 5]);
        $seedCat->execute(['Salad Mix Bundles', 'salad-mix-bundles', 'Pre-mixed varieties with dressings and extras', 6]);
        $seedCat->execute(['Wholesale', 'wholesale', 'Bulk packs for restaurants, events, and resellers', 7]);
        $seedCat->execute(['Best Sellers', 'best-sellers', 'Our most popular lettuce varieties and bundles', 8]);
        $report[] = "Seeded 8 product categories.";
    }

    // -----------------------------------------------------------
    // SEED: Products
    // -----------------------------------------------------------
    $prodCount = (int)$conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($prodCount === 0) {
        $seedProd = $conn->prepare("INSERT INTO products (category_id, name, slug, variety, description, price, unit, image, calories, best_for, shelf_life, harvest_time, plants_available, is_best_seller, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $seedProd->execute([1, 'Romaine Lettuce', 'romaine-lettuce', 'Giulia NH & Grizzari NZ', 'Tall, crisp dark green leaves — the essential Caesar salad green. Our most versatile variety, perfect for everything from salads to wraps.', 45.00, 'per cup', 'images/lettuce/romaine-lettuce.png', 17, 'Caesar salad, sandwiches, wraps, grilling', '5-7 days refrigerated', '1-3 hours after order', 150, 1, 1]);
        $seedProd->execute([1, 'Batavia Lettuce', 'batavia-lettuce', 'Graction NZ, Rijk Zwaan', 'Broad, crunchy leaves with excellent texture. The ideal sandwich and burger lettuce — holds up beautifully to heat and dressings.', 40.00, 'per cup', 'images/lettuce/batavia-lettuce.png', 15, 'Sandwiches, burgers, wraps, everyday salads', '5-7 days refrigerated', '1-3 hours after order', 110, 1, 1]);
        $seedProd->execute([1, 'Bianca Lettuce', 'bianca-lettuce', 'Butterhead, NH', 'Smooth, pliable leaves with a mild sweet flavor. The delicate butterhead variety prized by chefs for its tender texture.', 45.00, 'per cup', 'images/lettuce/bianca-lettuce.png', 13, 'Lettuce wraps, delicate salads, garnish', '5-7 days refrigerated', '1-3 hours after order', 80, 1, 1]);
        $seedProd->execute([1, 'Dabi Lettuce', 'dabi-lettuce', 'Lollo Bionda, Frizz Zakken', 'Frilly, crinkled bright green leaves with a delicate crunch. Adds visual drama and texture to any plate.', 40.00, 'per cup', 'images/lettuce/dabi-lettuce.png', 14, 'Garnishes, mixed salads, elegant plating', '5-7 days refrigerated', '1-3 hours after order', 85, 0, 1]);
        $seedProd->execute([2, 'Red Lettuce', 'red-lettuce', 'Lollo Rossa', 'Vibrant red-tipped leaves with a nutty, slightly bitter flavor. Rich in antioxidants — as nutritious as it is beautiful.', 42.00, 'per cup', 'images/lettuce/red-lettuce.png', 16, 'Gourmet salads, colorful plating, antioxidant boost', '5-7 days refrigerated', '1-3 hours after order', 95, 0, 1]);
        $seedProd->execute([1, 'Estrosa Lettuce', 'estrosa-lettuce', 'Lollo Bionda, Frizz Zakken', 'Intense green, firm crinkled leaves. The go-to choice for artisan salads and garnishes that make an impression.', 38.00, 'per cup', 'images/lettuce/estrosa-lettuce.png', 12, 'Artisan salads, restaurant garnishes', '5-7 days refrigerated', '1-3 hours after order', 75, 0, 1]);
        $seedProd->execute([1, 'Olmetie Lettuce', 'olmetie-lettuce', 'Batavia, Rijk Zwaan', 'Premium Batavia cultivar loved by chefs for its crisp bite and deep flavor. A standout lettuce for discerning palates.', 48.00, 'per cup', 'images/lettuce/olmetie-lettuce.png', 15, 'Chef-preferred, premium salads, fine dining', '5-7 days refrigerated', '1-3 hours after order', 90, 0, 1]);
        $seedProd->execute([4, 'Romaine Twin Pack', 'romaine-twin-pack', 'Giulia NH · 2 Cups', 'Two cups of our bestselling Romaine. Perfect for couples or small households who want fresh lettuce twice a week.', 85.00, 'per twin pack', 'images/lettuce/romaine-lettuce.png', 34, 'Small families, couples, meal prep', '5-7 days refrigerated', '1-3 hours after order', 75, 0, 1]);
        $seedProd->execute([5, 'Romaine Family Pack', 'romaine-family-pack', 'Giulia NH · 4 Cups', 'Four cups of crisp Romaine — a week\'s supply for the family. Best value for regular Romaine fans.', 160.00, 'per family pack', 'images/lettuce/romaine-lettuce.png', 68, 'Family meals, weekly supply', '5-7 days refrigerated', '1-3 hours after order', 40, 1, 1]);
        $seedProd->execute([6, 'Mixed Greens Cup', 'mixed-greens-cup', 'Butterhead + Lollo Rossa + Romaine', 'A colorful medley of three hand-picked varieties tossed together. Instant salad — just add dressing.', 60.00, 'per cup', 'images/lettuce/mixed-greens.png', 18, 'Instant colorful salads, quick meals', 'Best consumed immediately', '1-3 hours after order', 50, 0, 1]);
        $seedProd->execute([6, 'Garden Salad Mix', 'garden-salad-mix', 'Batavia + Estrosa + Red Leaf', 'Crisp, layered textures and colors for a restaurant-style garden salad at home. Our most popular mixed option.', 65.00, 'per cup', 'images/lettuce/garden-salad.png', 20, 'Restaurant-style garden salads at home', 'Best consumed immediately', '1-3 hours after order', 45, 1, 1]);
        $seedProd->execute([6, 'Family Bundle', 'family-bundle', '4 Cups + House Dressing', 'Four assorted lettuce cups paired with our signature house dressing. Ready for family dinner.', 180.00, 'per bundle', 'images/lettuce/family-bundle.png', 75, 'Family dinner, 4 servings', '5-7 days refrigerated', '1-3 hours after order', 30, 0, 1]);
        $seedProd->execute([6, 'Weekend Bundle', 'weekend-bundle', '6 Cups + Dressing + Wrap Kit', 'Our best-selling weekend bundle: six assorted cups, house dressing, and a wrap kit for the whole table.', 260.00, 'per bundle', 'images/lettuce/weekend-bundle.png', 110, 'Weekend gatherings, family of 4-6', '5-7 days refrigerated', '1-3 hours after order', 20, 1, 1]);
        $seedProd->execute([6, 'Healthy Starter Bundle', 'healthy-starter-bundle', '2 Romaine + 2 Bianca + Dressing', 'The perfect introduction to hydroponic lettuce. Two classic varieties with our house dressing.', 160.00, 'per bundle', 'images/lettuce/family-bundle.png', 60, 'New to hydroponics, starter kit', '5-7 days refrigerated', '1-3 hours after order', 25, 0, 1]);
        $seedProd->execute([6, 'Caesar Salad Bundle', 'caesar-salad-bundle', '3 Romaine + Dressing + Croutons', 'Everything you need for classic Caesar salads — three Romaine cups, dressing, and crunchy croutons.', 170.00, 'per bundle', 'images/lettuce/romaine-lettuce.png', 65, 'Classic Caesar salads for the family', '5-7 days refrigerated', '1-3 hours after order', 25, 0, 1]);
        $seedProd->execute([7, 'Restaurant Pack', 'restaurant-pack', '10 Cups - Chef\'s Assortment', 'Ten cups of chef-selected varieties. Ideal for small restaurants, cafés, and food stalls.', 380.00, 'per pack', 'images/lettuce/garden-salad.png', 160, 'Small restaurants, cafés, food stalls', '5-7 days refrigerated', '2-3 hours after order', 15, 0, 1]);
        $seedProd->execute([7, 'Wholesale Tray', 'wholesale-tray', '20 Cups - Bulk Assorted', 'Bulk tray of 20 mixed-variety cups. Perfect for resellers, canteens, and events.', 700.00, 'per tray', 'images/lettuce/wholesale-tray.png', 300, 'Resellers, canteens, small events', '5-7 days refrigerated', '2-4 hours after order', 8, 0, 1]);
        $seedProd->execute([7, 'Wholesale Box', 'wholesale-box', '50 Cups - Bulk Assorted', 'Our biggest bulk box of 50 cups. Maximum value for wholesale buyers and large events.', 1650.00, 'per box', 'images/lettuce/wholesale-tray.png', 750, 'Restaurants, events, large gatherings', '5-7 days refrigerated', '3-5 hours after order', 3, 0, 1]);
        $report[] = "Seeded 18 hydroponic lettuce products.";
    }

    // -----------------------------------------------------------
    // SEED: Promotions
    // -----------------------------------------------------------
    $promoCount = (int)$conn->query("SELECT COUNT(*) FROM promotions")->fetchColumn();
    if ($promoCount === 0) {
        $seedPromo = $conn->prepare("INSERT INTO promotions (code, description, discount_type, discount_value, min_order, is_active) VALUES (?,?,?,?,?,?)");
        $seedPromo->execute(['FRESH10', '10% off your first order', 'percentage', 10.00, 0.00, 1]);
        $seedPromo->execute(['FREESUBD', 'Free delivery within Nostalji Subdivision', 'fixed', 0.00, 0.00, 1]);
        $seedPromo->execute(['BUNDLE5', '₱50 off any bundle purchase', 'fixed', 50.00, 150.00, 1]);
        $report[] = "Seeded 3 promotion codes.";
    }

    // -----------------------------------------------------------
    // SEED: Knowledge Base
    // -----------------------------------------------------------
    $kbCount = (int)$conn->query("SELECT COUNT(*) FROM knowledge_base")->fetchColumn();
    if ($kbCount === 0) {
        $seedKb = $conn->prepare("INSERT INTO knowledge_base (title, slug, content, category) VALUES (?,?,?,?)");
        $seedKb->execute(['How to Order', 'how-to-order', "## How to Order Fresh Hydroponic Lettuce\n\n1. **Browse** our Products page to see available lettuce varieties, bundles, and wholesale options.\n2. **Add to Cart** the items you want — choose quantities for each.\n3. **Review Your Cart** to confirm items, quantities, and see delivery fee estimates.\n4. **Checkout** — enter your delivery address or choose Pick-Up. We automatically detect if you're within our free delivery area (Nostalji Subdivision).\n5. **Choose Payment** — Cash on Delivery, GCash, Maya, or Bank Transfer.\n6. **Place Order** — once confirmed, we add your order to the harvest queue.\n\nYou'll receive order status updates as we harvest, quality-check, pack, and deliver your lettuce — all on the same day!", 'Ordering']);
# REMOVED FRESHNESS ENTRY
        $seedKb->execute(['Harvest-on-Demand Guide', 'harvest-on-demand-guide', "## What is Harvest-on-Demand?\n\nUnlike supermarkets where lettuce sits on shelves for days, our harvest-on-demand model means:\n\n- **Lettuce stays growing** in our hydroponic system until you order\n- **Harvested only after order confirmation** — usually within 1-3 hours\n- **Same-day delivery or pick-up** — maximum freshness\n- **Zero food waste** — nothing is pre-harvested and left unsold\n- **Better nutrition** — nutrients peak when freshly harvested\n\nThis is why our lettuce lasts 5-7 days in your refrigerator — it starts fresher than anything you'll find at the grocery store.", 'Harvest']);
        $seedKb->execute(['Delivery Guide', 'delivery-guide', "## Delivery Information\n\n### Free Delivery\nFREE delivery within **Nostalji Subdivision, Paliparan I, Dasmariñas, Cavite** — no minimum order required.\n\n### Paid Delivery\nDelivery to areas outside the subdivision incurs a fee automatically calculated based on your address.\n\n### Same-Day Delivery\nOrders placed before 2 PM are delivered the same day. Orders after 2 PM are delivered the following morning.\n\n### Same-Day Pick-Up\nOrder online and pick up at the farm. Ready within 1-3 hours after order confirmation. No delivery fee.\n\n### Delivery Hours\nMonday – Sunday, 8:00 AM – 6:00 PM", 'Delivery']);
        $seedKb->execute(['Storage Guide', 'storage-guide', "## How to Store Your Lettuce\n\n### Whole Heads\n- **Refrigerate immediately** at 2-4°C\n- **Do not wash** until ready to use\n- **Keep in a sealed container** or wrap in paper towel\n- **Store in crisper drawer** away from ethylene-producing fruits\n- **Shelf life:** 5-7 days refrigerated\n\n### Cut Leaves / Salad Mix\n- **Refrigerate immediately**\n- **Best consumed within 24 hours**\n- Room temperature: less than 24 hours\n\n### Pro Tips\n- Revive slightly wilted lettuce by soaking in cold water for 10-15 minutes\n- Pat dry with paper towels before using\n- Keep away from apples, bananas, and avocados (they release ethylene)", 'Storage']);
        $report[] = "Seeded 4 knowledge base articles.";
    }

    $faqCount = (int)$conn->query("SELECT COUNT(*) FROM faqs")->fetchColumn();
    if ($faqCount === 0) {
        $seedFaq = $conn->prepare("INSERT INTO faqs (question, answer, category) VALUES (?, ?, ?)");
        
        // Original starter FAQs
        $seedFaq->execute(["How fresh is our lettuce?", "Our lettuce is harvested only after you order — it stays growing in our hydroponic system until your order is confirmed. We harvest, quality-check, pack, and deliver all on the same day. When properly refrigerated at 2-4°C, whole heads stay fresh for 5-7 days.", "Freshness"]);
        $seedFaq->execute(["What is our harvest-on-demand policy?", "Unlike supermarkets where lettuce may have been cut days ago, our harvest-on-demand model means every head of lettuce stays growing in our hydroponic system until you place your order. We only harvest after your order confirmation — usually within 1-3 hours before delivery or pick-up.", "Orders"]);
        $seedFaq->execute(["How should I store my lettuce?", "Refrigerate immediately after receiving at 2-4°C. Do not wash until ready to use. Keep inside a sealed container in the crisper drawer. Store away from ethylene-producing fruits like apples and bananas. Whole heads last 5-7 days refrigerated.", "Care"]);
        $seedFaq->execute(["My lettuce arrived damaged. What should I do?", "Please contact our support team within 24 hours of delivery and include a few photos of the issue along with your order number. We'll send a replacement at no additional cost — fresh, same-day harvested.", "Quality"]);
        $seedFaq->execute(["How does delivery work?", "We offer same-day delivery and same-day pick-up. Delivery is FREE within Nostalji Subdivision, Paliparan I, Dasmariñas, Cavite. For locations outside the subdivision, a delivery fee is automatically calculated based on your address.", "Delivery"]);
        
        // NEW: Technical Support & Account FAQs
        $seedFaq->execute(["How do I submit a support ticket?", "To submit a support ticket:\n\n1. Log into your Luntiang H.A.P.A.G. account\n2. Go to the Submit a Ticket page\n3. Fill in the Subject, Category, and a clear description\n4. Add your Order Number (if applicable)\n5. Attach any relevant photos or files\n6. Click Submit\n\nYou can track your ticket's status in My Support Tickets.", "Technical Support"]);
# REMOVED FRESHNESS ENTRY
        $seedFaq->execute(["How do I submit a freshness guarantee request?", "To submit a freshness guarantee request:\n\n1. Log into your Luntiang H.A.P.A.G. account\n2. Go to Freshness Guarantee Request\n3. Provide:\n   • Product Name\n   • Order Number\n   • Delivery Date\n   • Quality Issue (category)\n   • Description of the issue\n   • Photos of the product (required)\n4. Click Submit\n\nOur team will review your request within 1-2 business days.", "Freshness"]);
        $seedFaq->execute(["How do I request a return or refund?", "To request a return or refund:\n\n1. Log into your Luntiang H.A.P.A.G. account\n2. Go to Return Request\n3. Provide:\n   • Order Number\n   • Product Name\n   • Delivery Date\n   • Reason for Return\n   • Detailed Explanation\n   • Product Condition\n   • Photos (required)\n4. Click Submit\n\nOur team will review your request within 1-2 business days.", "Returns"]);
        $seedFaq->execute(["How do I create an account?", "Creating an account is quick:\n\n1. Go to the Register page\n2. Enter your full name, email address, phone number, and a password\n3. Confirm your password and submit the form\n4. You'll be logged in automatically\n\nOnce that's done, you can browse products, place orders, and track deliveries right away.", "Account"]);
# REMOVED FRESHNESS ENTRY
        $seedFaq->execute(["What does the freshness guarantee cover?", "Our freshness guarantee covers:\n\n• Wilted or damaged lettuce upon delivery\n• Wrong variety delivered\n• Missing items from your order\n• Quality below our standards\n\nSimply submit a Freshness Guarantee Request with photos within 24 hours of delivery. We'll approve a replacement or refund at no cost to you.", "Freshness"]);
        $seedFaq->execute(["How does delivery work in my area?", "Delivery is FREE within Nostalji Subdivision, Paliparan I, Dasmariñas, Cavite.\n\nFor locations outside the subdivision, a delivery fee is automatically calculated based on your address.\n\nSame-day delivery is available for orders placed before 2 PM. Same-day pick-up is always available — your lettuce is ready 1-3 hours after order confirmation.", "Delivery"]);
        
        $report[] = "Seeded 11 FAQ entries (5 original + 6 new technical support/account FAQs).";
    } else {
        // Check if new FAQs exist, if not add them
        $newFaqs = [
            ["How do I submit a support ticket?", "To submit a support ticket:\n\n1. Log into your Luntiang H.A.P.A.G. account\n2. Go to the Submit a Ticket page\n3. Fill in the Subject, Category, and a clear description\n4. Add your Order Number (if applicable)\n5. Attach any relevant photos or files\n6. Click Submit\n\nYou can track your ticket's status in My Support Tickets.", "Technical Support"],
# REMOVED FRESHNESS ENTRY
            ["How do I submit a freshness guarantee request?", "To submit a freshness guarantee request:\n\n1. Log into your Luntiang H.A.P.A.G. account\n2. Go to Freshness Guarantee Request\n3. Provide:\n   • Product Name\n   • Order Number\n   • Delivery Date\n   • Quality Issue (category)\n   • Description of the issue\n   • Photos of the product (required)\n4. Click Submit\n\nOur team will review your request within 1-2 business days.", "Freshness"],
            ["How do I request a return or refund?", "To request a return or refund:\n\n1. Log into your Luntiang H.A.P.A.G. account\n2. Go to Return Request\n3. Provide:\n   • Order Number\n   • Product Name\n   • Delivery Date\n   • Reason for Return\n   • Detailed Explanation\n   • Photos (required)\n4. Click Submit\n\nOur team will review your request within 1-2 business days.", "Returns"],
            ["How do I create an account?", "Creating an account is quick:\n\n1. Go to the Register page\n2. Enter your full name, email address, phone, and a password\n3. Confirm your password and submit the form\n\nOnce done, you can browse products, place orders, and track deliveries.", "Account"],
# REMOVED FRESHNESS ENTRY
            ["What does the freshness guarantee cover?", "Our freshness guarantee covers wilted or damaged lettuce, wrong varieties, missing items, and quality below our standards. Submit a request with photos within 24 hours of delivery for a free replacement or refund.", "Freshness"],
            ["How does delivery work in my area?", "FREE delivery within Nostalji Subdivision. For outside areas, a delivery fee is automatically calculated. Same-day delivery for orders before 2 PM. Same-day pick-up always available — ready 1-3 hours after confirmation.", "Delivery"],
        ];
        
        $added = 0;
        foreach ($newFaqs as $newFaq) {
            $check = $conn->prepare("SELECT COUNT(*) FROM faqs WHERE question = ?");
            $check->execute([$newFaq[0]]);
            if ((int)$check->fetchColumn() === 0) {
                $seedFaq = $conn->prepare("INSERT INTO faqs (question, answer, category) VALUES (?, ?, ?)");
                $seedFaq->execute($newFaq);
                $added++;
            }
        }
        if ($added > 0) {
            $report[] = "Added $added new technical support/account FAQs to existing set.";
        } else {
            $report[] = "All FAQs already exist.";
        }
    }

    // -----------------------------------------------------------
    // 14. Seed / default data
    //     Always ensure admin@luntianghapag.com exists.
    //     If the table is empty, create it fresh.
    //     If an old admin@woodcraftcare.com still exists from a
    //     previous install, update it to the new email.
    // -----------------------------------------------------------
    $adminCount = (int)$conn->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($adminCount === 0) {
        $defaultHash = password_hash("Admin@123", PASSWORD_DEFAULT);
        $seed = $conn->prepare("
            INSERT INTO admins (name, email, password, role)
            VALUES (?, ?, ?, ?)
        ");
        $seed->execute(["Luntiang H.A.P.A.G. Admin", "admin@luntianghapag.com", $defaultHash, "Super Admin"]);
        $report[] = "Default admin account created (admin@luntianghapag.com / Admin@123).";
    } else {
        // Check for old WoodCraft email and update it
        $old = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $old->execute(["admin@woodcraftcare.com"]);
        if ($oldId = $old->fetchColumn()) {
            $newHash = password_hash("Admin@123", PASSWORD_DEFAULT);
            $conn->prepare("UPDATE admins SET email = ?, name = ?, password = ? WHERE id = ?")
                 ->execute(["admin@luntianghapag.com", "Luntiang H.A.P.A.G. Admin", $newHash, $oldId]);
            $report[] = "Updated old admin account to admin@luntianghapag.com.";
        } else {
            $report[] = "Admin account(s) already exist.";
        }
    }

} catch (PDOException $e) {
    $error = "Database initialization failed: " . $e->getMessage();

    if ($isDirectRequest) {
        http_response_code(500);
        echo "<h1>Database Setup Error</h1><p>" . htmlspecialchars($error) . "</p>";
        exit();
    }

    // When included by config.php, fail loudly too — the app can't
    // run without a working database connection.
    die($error);
}

// ---------------------------------------------------------------
// 11. Visual confirmation when this file is run directly.
//     When included by config.php, $conn is simply handed back to
//     the caller and nothing is printed.
// ---------------------------------------------------------------
if ($isDirectRequest) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Database Setup | Luntiang H.A.P.A.G.</title>
        <style>
            body { font-family: system-ui, sans-serif; background: #F3F0E4; color: #2E1D14; padding: 40px; }
            .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
            h1 { font-size: 22px; margin-bottom: 4px; }
            p.sub { color: #6b7280; margin-top: 0; margin-bottom: 24px; }
            ul { list-style: none; padding: 0; margin: 0; }
            li { padding: 10px 14px; margin-bottom: 8px; background: #F8F6F2; border-radius: 10px; display: flex; align-items: center; gap: 10px; }
            li:before { content: "✓"; color: #2E7D32; font-weight: bold; }
            a.btn { display: inline-block; margin-top: 24px; padding: 10px 20px; background: #6B4226; color: #fff; text-decoration: none; border-radius: 999px; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Database setup complete</h1>
            <p class="sub">Database: <strong><?= htmlspecialchars($dbname) ?></strong> on <strong><?= htmlspecialchars($host) ?></strong></p>
            <ul>
                <?php foreach ($report as $line): ?>
                    <li><?= htmlspecialchars($line) ?></li>
                <?php endforeach; ?>
            </ul>
            <a class="btn" href="../index.php">Go to the site →</a>
        </div>
    </body>
    </html>
    <?php
}
?>