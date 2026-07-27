<?php
/**
 * admin/includes/admin-auth.php
 * ------------------------------------------------------------------
 * Session guard for every admin page except admin-login.php.
 * Include this at the very top of any admin/*.php file, right
 * after session_start() + config.php, to require a logged-in admin.
 * ------------------------------------------------------------------
 */

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}
