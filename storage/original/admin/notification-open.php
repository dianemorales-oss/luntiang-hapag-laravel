<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/../includes/notifications.php';

$notificationId = (int)($_GET['id'] ?? 0);

if ($notificationId > 0) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->execute([$notificationId]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($notification) {
        $update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $update->execute([$notificationId]);

        header("Location: " . notificationTargetUrl($notification['type'], (int)$notification['related_id']));
        exit();
    }
}

header("Location: notifications.php");
exit();
