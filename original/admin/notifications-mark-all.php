<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
}

$redirect = $_POST['redirect'] ?? 'notifications.php';
// Only allow redirecting back to a local admin page, never an external URL.
if (!is_string($redirect) || $redirect === '' || str_contains($redirect, '://')) {
    $redirect = 'notifications.php';
}

header("Location: " . $redirect);
exit();
