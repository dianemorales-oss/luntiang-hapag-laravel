## Running the Project

1. Clone or download this repository.
2. Copy the project folder into your web server's `htdocs` directory.
3. Start **Apache** and **MySQL**.
4. Create a database named **`woodcraft_customer_service`**.
5. Import the SQL file located at: 
    database/woodcraft_customer_service.sql

6. If your MySQL credentials differ from the default XAMPP configuration, update them in: 
    database/db.php

7. Open the project in your browser: 
    http://localhost/woodcraft-care

---

## Sample Login Credentials

### Admin Account
- Email: `admin@woodcraftcare.com`
- Password: `Admin@123`

The admin login button is intentionally hidden located at the bottom-right corner of the homepage 
(`index.php`).

### Customer Accounts
- Email: Check the `users` table in the database.
- Password: `Password123!`

























# Steps:

Copy the project to C:\wamp64\www\.
Start WAMP.
Open phpMyAdmin.
Create the database woodcraft_customer_service (if it doesn't already exist).
Import woodcraft_customer_service.sql.
Open the website.

# All sample will be available.

------------------------------------
# DELETE ALL SAMPLE DATA (FOR ME)

USE woodcraft_customer_service;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM ticket_replies;
DELETE FROM tickets;

DELETE FROM warranty_requests;
DELETE FROM return_requests;

DELETE FROM feedback;
DELETE FROM live_chat_messages;

DELETE FROM notifications;
DELETE FROM users;

SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE ticket_replies AUTO_INCREMENT = 1;
ALTER TABLE tickets AUTO_INCREMENT = 1;

ALTER TABLE warranty_requests AUTO_INCREMENT = 1;
ALTER TABLE return_requests AUTO_INCREMENT = 1;

ALTER TABLE feedback AUTO_INCREMENT = 1;
ALTER TABLE live_chat_messages AUTO_INCREMENT = 1;

ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;