<?php

namespace App\Helpers;

use App\Models\CustomerNotification;

class CustomerNotificationHelper
{
    public static function create(int $userId, string $type, string $title, string $message, ?int $relatedId = null, ?string $relatedType = null, ?string $link = null): void
    {
        try {
            // A status can be saved twice by a double-click or retry. One notification
            // per related record/status title keeps the customer's history meaningful.
            $duplicate = CustomerNotification::where('user_id', $userId)
                ->where('type', $type)
                ->where('related_id', $relatedId)
                ->where('related_type', $relatedType)
                ->where('title', $title)
                ->exists();
            if ($duplicate) return;

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
        $statuses = [
            'active' => ['Order Confirmed', 'Your order has been confirmed and is queued for harvest.'],
            'preparing' => ['Preparing for Harvest', 'Our team is preparing your order for fresh harvest.'],
            'harvesting' => ['Harvesting', 'Your fresh produce is being harvested now.'],
            'packing' => ['Packing', 'Your harvested produce is being carefully packed.'],
            'ready' => ['Ready for Delivery', 'Your order is packed and ready for delivery or pickup.'],
            'out_for_delivery' => ['Out for Delivery', 'Your order is on the way to you.'],
            'delivered' => ['Delivered', 'Your order has been delivered. Enjoy your fresh harvest!'],
            'completed' => ['Completed', 'Your order is complete. Thank you for choosing Luntiang H.A.P.A.G.!'],
            'cancelled' => ['Cancelled', 'Your order has been cancelled. Please contact support if you need assistance.'],
        ];
        [$title, $description] = $statuses[$newStatus] ?? [ucwords(str_replace('_', ' ', $newStatus)), 'Your order status has been updated.'];
        self::create(
            $userId, 'order_status', "Order #{$orderNumber}: {$title}",
            "{$description} Order number: #{$orderNumber}.", $orderId, 'order',
            route('orders.tracking', ['order' => $orderNumber])
        );
    }

    public static function orderPlaced(int $userId, int $orderId, string $orderNumber): void
    {
        self::create($userId, 'order_status', "Order #{$orderNumber}: Order Placed",
            "We received your order #{$orderNumber}. We will notify you at every step.", $orderId, 'order',
            route('orders.tracking', ['order' => $orderNumber]));
    }

    public static function returnStatusChanged(int $userId, int $returnId, string $orderNumber, string $status): void
    {
        $map = [
            'pending' => ['Return Requested', 'We received your return/refund request and will review it shortly.'],
            'approved' => ['Return Approved', 'Your return request has been approved.'],
            'denied' => ['Return Rejected', 'Your return request was not approved. Please see the request details for more information.'],
            'refund_processing' => ['Refund Processing', 'Your refund is now being processed.'],
            'refunded' => ['Refunded', 'Your refund has been completed.'],
        ];
        [$title, $description] = $map[$status] ?? ['Return Update', 'Your return request has been updated.'];
        self::create($userId, 'return_status', "Order #{$orderNumber}: {$title}",
            "{$description} Order number: #{$orderNumber}.", $returnId, 'return', route('returns.index'));
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
