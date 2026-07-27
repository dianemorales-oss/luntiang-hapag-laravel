<?php

namespace App\Helpers;

use App\Models\CustomerNotification;

class CustomerNotificationHelper
{
    public static function create(int $userId, string $type, string $title, string $message, ?int $relatedId = null, ?string $relatedType = null, ?string $link = null): void
    {
        try {
            CustomerNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'related_id' => $relatedId,
                'related_type' => $relatedType,
                'link' => $link,
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            \Log::error('Customer notification failed: '.$e->getMessage());
        }
    }

    public static function orderStatusChanged(int $userId, int $orderId, string $orderNumber, string $newStatus): void
    {
        $statusLabels = [
            'preparing' => 'Preparing',
            'ready' => 'Ready for Pickup/Delivery',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];
        $label = $statusLabels[$newStatus] ?? ucfirst($newStatus);
        self::create(
            $userId,
            'order_status',
            "Order {$orderNumber} is now {$label}",
            "Your order #{$orderNumber} status has been updated to {$label}. Track your order for more details.",
            $orderId,
            'order',
            route('orders.tracking', ['order' => $orderNumber])
        );
    }

    public static function ticketStatusChanged(int $userId, int $ticketId, string $subject, string $newStatus): void
    {
        self::create(
            $userId,
            'ticket_status',
            "Support Ticket #{$ticketId} – {$newStatus}",
            "Your support request '{$subject}' is now marked as {$newStatus}. Our team will follow up if needed.",
            $ticketId,
            'ticket',
            route('tickets.show', ['id' => $ticketId])
        );
    }

    public static function chatAgentReply(int $userId, int $messageId, string $agentName = 'Support Agent'): void
    {
        self::create(
            $userId,
            'chat_reply',
            "New reply from {$agentName}",
            "{$agentName} replied to your live chat. Open chat to view the message.",
            $messageId,
            'chat',
            route('chat.index')
        );
    }
}
