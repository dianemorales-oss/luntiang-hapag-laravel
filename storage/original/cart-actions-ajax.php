<?php
/**
 * cart-actions-ajax.php
 * AJAX-only endpoint for async cart operations: add, update, remove, apply_promo, claim_coupon
 */
session_start();
require 'config.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$action = $data['action'] ?? $_POST['action'] ?? '';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$productId = (int)($data['id'] ?? $_POST['id'] ?? 0);

switch ($action) {
    case 'add':
        $qty = max(1, (int)($data['qty'] ?? $_POST['qty'] ?? 1));
        $stmt = $conn->prepare("SELECT id, name, price, plants_available FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not available.']);
            exit();
        }
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
        syncCartToDb($conn);  // ← persist
        $cartCount = count(array_unique(array_column($_SESSION['cart'], 'id')));
        echo json_encode(['success' => true, 'message' => $product['name'] . ' added to cart!', 'count' => $cartCount]);
        break;

    case 'update':
        $qty = max(1, (int)($data['qty'] ?? $_POST['qty'] ?? 1));
        $stmt = $conn->prepare("SELECT id, plants_available, price FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) { echo json_encode(['success' => false]); exit(); }
        $newQty = min($qty, $product['plants_available']);
        $cart = $_SESSION['cart'];
        foreach ($cart as &$item) {
            if ($item['id'] === $productId) { $item['qty'] = $newQty; break; }
        }
        $_SESSION['cart'] = $cart;
        syncCartToDb($conn);  // ← persist
        $lineTotal = $product['price'] * $newQty;
        echo json_encode(['success' => true, 'qty' => $newQty, 'line_total' => number_format($lineTotal, 2)]);
        break;

    case 'remove':
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($i) => $i['id'] !== $productId));
        if (empty($_SESSION['cart'])) unset($_SESSION['selected_cart']);
        syncCartToDb($conn);  // ← persist
        $cartCount = count(array_unique(array_column($_SESSION['cart'], 'id')));
        echo json_encode(['success' => true, 'message' => 'Item removed.', 'count' => $cartCount]);
        break;

    case 'apply_promo':
        $code = trim($data['promo_code'] ?? $_POST['promo_code'] ?? '');
        if ($code) {
            // Check if claimed AND active
            $userId = $_SESSION['user_id'] ?? 0;
            $promo = $conn->prepare("SELECT p.* FROM promotions p INNER JOIN claimed_coupons cc ON p.id = cc.promotion_id WHERE cc.user_id = ? AND p.code = ? AND p.is_active = 1 AND (p.expires_at IS NULL OR p.expires_at >= CURDATE())");
            $promo->execute([$userId, $code]);
            $promoData = $promo->fetch(PDO::FETCH_ASSOC);
            if ($promoData) {
                $_SESSION['applied_promo'] = $promoData;
                echo json_encode(['success' => true, 'promo' => $promoData, 'message' => 'Coupon applied!']);
            } else {
                // Try public promo codes too
                $pub = $conn->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())");
                $pub->execute([$code]);
                $pubData = $pub->fetch(PDO::FETCH_ASSOC);
                if ($pubData) {
                    $_SESSION['applied_promo'] = $pubData;
                    echo json_encode(['success' => true, 'promo' => $pubData, 'message' => 'Promo applied!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid or expired promo code.']);
                }
            }
        }
        break;

    case 'claim_coupon':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Login required', 'redirect' => 'login.php']);
            exit();
        }
        $promoId = (int)($data['promo_id'] ?? 0);
        if ($promoId) {
            // Check if already claimed
            $check = $conn->prepare("SELECT id FROM claimed_coupons WHERE user_id = ? AND promotion_id = ?");
            $check->execute([$_SESSION['user_id'], $promoId]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Already claimed.']);
                exit();
            }
            $conn->prepare("INSERT INTO claimed_coupons (user_id, promotion_id) VALUES (?, ?)")
                 ->execute([$_SESSION['user_id'], $promoId]);
            echo json_encode(['success' => true, 'message' => 'Coupon claimed!']);
        }
        break;

    case 'select_promo':
        $code = trim($data['promo_code'] ?? '');
        if ($code) {
            // Verify customer actually claimed this coupon
            $userId = $_SESSION['user_id'] ?? 0;
            $valid = $conn->prepare("SELECT p.* FROM promotions p INNER JOIN claimed_coupons cc ON p.id = cc.promotion_id WHERE cc.user_id = ? AND p.code = ? AND p.is_active = 1 AND (p.expires_at IS NULL OR p.expires_at >= CURDATE())");
            $valid->execute([$userId, $code]);
            $promoData = $valid->fetch(PDO::FETCH_ASSOC);
            if ($promoData) {
                $_SESSION['applied_promo'] = $promoData;
                echo json_encode(['success' => true, 'promo' => $promoData]);
            } else {
                echo json_encode(['success' => false, 'message' => 'You have not claimed this coupon or it is no longer valid.']);
            }
        }
        break;

    case 'remove_promo':
        unset($_SESSION['applied_promo']);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
