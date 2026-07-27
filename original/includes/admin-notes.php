<?php
/**
 * includes/admin-notes.php
 * ------------------------------------------------------------------
 * Default Admin Note text for each status an admin can set on a
 * Warranty Request or Return & Refund Request. Used both server-side
 * (as a fallback when an admin saves a status change without typing
 * a custom note) and to power the client-side auto-fill in
 * admin/admin-warranty.php and admin/admin-returns.php.
 * ------------------------------------------------------------------
 */

if (!function_exists('defaultAdminNote')) {
    /**
     * @param string $context 'warranty' or 'return'
     * @param string $status  The new status being applied.
     */
    function defaultAdminNote(string $context, string $status): string
    {
        $notes = [
            'warranty' => [
                'pending'  => "Your warranty request is currently under review. We'll update you as soon as a decision has been made.",
                'approved' => "Your warranty request has been approved. Please bring the product to our service center together with your proof of purchase.",
                'denied'   => "After review, we're unable to approve this warranty request. Please contact our support team if you have questions or additional information to share.",
            ],
            'return' => [
                'pending'   => "Your return & refund request is currently under review. We'll update you as soon as a decision has been made.",
                'approved'  => "Your refund has been approved. Please return the product within seven (7) business days using the provided return instructions.",
                'denied'    => "After review, we're unable to approve this return request. Please contact our support team if you have questions or additional information to share.",
                'completed' => "Your return has been received and your refund has been processed. Please allow a few business days for it to reflect on your original payment method.",
            ],
        ];

        return $notes[$context][$status] ?? '';
    }
}
