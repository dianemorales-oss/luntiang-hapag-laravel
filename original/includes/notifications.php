<?php
/**
 * includes/notifications.php
 * ------------------------------------------------------------------
 * Small shared helper for creating Admin Notification System entries.
 * Used from customer-facing pages (new ticket/warranty/return, ticket
 * replies, reopen, close) and from admin pages (status changes on
 * warranty/return requests).
 *
 * Does not require config.php itself — the caller is expected to have
 * already loaded config.php (for $conn) before including this file.
 * ------------------------------------------------------------------
 */

if (!function_exists('createNotification')) {
    /**
     * Insert a new admin notification. Failures are swallowed on
     * purpose (a notification is a nice-to-have side effect — it
     * should never block or break the primary action, like submitting
     * a ticket or saving an admin note).
     */
    function createNotification(
        PDO $conn,
        string $type,
        int $relatedId,
        string $title,
        string $message,
        ?string $customerName = null
    ): void {
        try {
            $stmt = $conn->prepare("
                INSERT INTO notifications (type, related_id, title, message, customer_name, is_read)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$type, $relatedId, $title, $message, $customerName]);
        } catch (PDOException $e) {
            // Intentionally ignored — see docblock above.
        }
    }
}

if (!function_exists('notificationTargetUrl')) {
    /**
     * Map a notification's type to the admin page an admin should land
     * on after clicking it.
     */
    function notificationTargetUrl(string $type, int $relatedId): string
    {
        switch ($type) {
            case 'ticket_new':
            case 'ticket_reply':
            case 'ticket_reopen':
            case 'ticket_closed':
                return 'admin-ticket-detail.php?id=' . $relatedId;
            case 'warranty_new':
                return 'admin-warranty.php';
            case 'return_new':
                return 'admin-returns.php';
            case 'order_new':
            case 'order_status':
                return 'admin-orders.php';
            default:
                return 'admin-dashboard.php';
        }
    }
}
