<?php
/**
 * includes/cart-helpers.php
 * Persistent cart — syncs $_SESSION['cart'] with DB cart_items for logged-in users.
 * Include after config.php on any page that reads or mutates the cart.
 */
if (!function_exists('syncCartToDb')) {
    function syncCartToDb(PDO $conn): void {
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) return;
        $cart = $_SESSION['cart'] ?? [];
        // Clear old DB cart for this user, re-insert current session cart
        $conn->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$uid]);
        if (!empty($cart)) {
            $ins = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
            foreach ($cart as $item) {
                $ins->execute([$uid, (int)$item['id'], (int)$item['qty']]);
            }
        }
    }
}
if (!function_exists('loadCartFromDb')) {
    function loadCartFromDb(PDO $conn): void {
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) return;
        $rows = $conn->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
        $rows->execute([$uid]);
        $_SESSION['cart'] = [];
        while ($r = $rows->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['cart'][] = ['id' => (int)$r['product_id'], 'qty' => (int)$r['quantity']];
        }
    }
}
if (!function_exists('mergeGuestCartToDb')) {
    function mergeGuestCartToDb(PDO $conn, array $guestCart): void {
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) return;
        // Load existing DB cart into session
        loadCartFromDb($conn);
        $existing = $_SESSION['cart'];
        // Merge guest items: if same product, sum quantities
        foreach ($guestCart as $gItem) {
            $found = false;
            foreach ($existing as &$eItem) {
                if ($eItem['id'] === $gItem['id']) {
                    $eItem['qty'] += $gItem['qty'];
                    $found = true; break;
                }
            }
            if (!$found) {
                $existing[] = $gItem;
            }
        }
        $_SESSION['cart'] = $existing;
        // Persist merged cart to DB
        syncCartToDb($conn);
    }
}
