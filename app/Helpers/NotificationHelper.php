<?php
namespace App\Helpers;
use App\Models\Notification;

class NotificationHelper
{
    public static function create(string $type, int $relatedId, string $title, string $message, ?string $customerName = null, ?string $relatedLink = null): void
    {
        try {
            Notification::create([
                'type' => $type,
                'related_id' => $relatedId,
                'title' => $title,
                'message' => $message,
                'customer_name' => $customerName,
                'related_link' => $relatedLink,
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            // Intentionally ignored
        }
    }

    public static function targetUrl(string $type, int $relatedId): string
    {
        return match($type) {
            'ticket_new', 'ticket_reply', 'ticket_reopen', 'ticket_closed' => route('admin.tickets.show', $relatedId),
            'warranty_new' => route('admin.warranty.index'),
            'return_new' => route('admin.returns.index'),
            'order_new', 'order_status' => route('admin.orders.index'),
            default => route('admin.dashboard'),
        };
    }
}
