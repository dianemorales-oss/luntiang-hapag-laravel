<?php
session_start();
require 'config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
$slug = $_GET['slug'] ?? $_POST['slug'] ?? '';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    $productId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    $stmt = $conn->prepare("SELECT id, name, price, plants_available FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['cart_message'] = 'Product not available.';
        $ref = $_SERVER['HTTP_REFERER'] ?? 'products.php';
        header("Location: " . $ref); exit();
    }

    switch ($action) {
        case 'add':
            $cart = $_SESSION['cart']; $found = false;
            foreach ($cart as &$item) {
                if ($item['id'] === $productId) {
                    $item['qty'] = min($item['qty'] + $qty, $product['plants_available']);
                    $found = true; break;
                }
            }
            if (!$found) {
                $_SESSION['cart'][] = ['id' => $productId, 'qty' => min($qty, $product['plants_available'])];
            } else { $_SESSION['cart'] = $cart; }
            $_SESSION['cart_message'] = "{$product['name']} added to cart.";
            // Stay on current page, restore scroll position via sessionStorage
            $ref = $_SERVER['HTTP_REFERER'] ?? 'products.php';
            header("Location: " . $ref); exit();

        case 'buy_now':
            $_SESSION['cart'] = [['id' => $productId, 'qty' => 1]];
            $_SESSION['selected_cart'] = [$productId];
            header("Location: checkout.php"); exit();

        case 'update':
            $cart = $_SESSION['cart'];
            foreach ($cart as &$item) {
                if ($item['id'] === $productId) { $item['qty'] = max(1, min($qty, $product['plants_available'])); break; }
            }
            $_SESSION['cart'] = $cart; break;

        case 'remove':
            $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($i) => $i['id'] !== $productId));
            $_SESSION['cart_message'] = 'Item removed.'; break;

        case 'clear':
            $_SESSION['cart'] = []; unset($_SESSION['selected_cart']);
            $_SESSION['cart_message'] = 'Cart cleared.'; break;

        case 'apply_promo':
            $code = trim($_POST['promo_code'] ?? '');
            if ($code) {
                $promo = $conn->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())");
                $promo->execute([$code]);
                $promoData = $promo->fetch(PDO::FETCH_ASSOC);
                if ($promoData) { $_SESSION['applied_promo'] = $promoData; $_SESSION['cart_message'] = "Promo applied!"; }
                else { $_SESSION['cart_message'] = 'Invalid or expired promo code.'; }
            }
            break;
    }
}

// Redirect back for non-exit cases
if (isset($_GET['action']) && in_array($action, ['update','remove','clear','apply_promo'])) {
    $ref = $_GET['redirect'] ?? 'cart';
    header("Location: " . $ref . ".php"); exit();
}
$ref = $_SERVER['HTTP_REFERER'] ?? 'products.php';
header("Location: " . $ref); exit();
