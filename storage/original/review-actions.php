<?php
session_start();
require 'config.php';
require_once __DIR__ . '/includes/form-helpers.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$action = $_GET['action'] ?? $_POST['action'] ?? 'submit';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');

    if ($action === 'submit') {
        // Handle image upload
        $photos = '';
        $upload = handleMultiFileUpload(
            'review_photos',
            ['jpg', 'jpeg', 'png'],
            __DIR__ . '/uploads/reviews',
            'uploads/reviews',
            false
        );
        if ($upload['ok'] && !empty($upload['paths'])) {
            $photos = encodeAttachmentPaths($upload['paths']);
        }

        $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, photos, is_verified, is_approved) VALUES (?,?,?,?,?,0,1)")
             ->execute([$_SESSION['user_id'], $productId, $rating, $comment ?: null, $photos]);

        $_SESSION['cart_message'] = 'Review submitted! Thank you 🌱';
    }

    if ($action === 'reply' && isset($_SESSION['admin_id'])) {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $reply = trim($_POST['admin_reply'] ?? '');
        if ($reviewId && $reply) {
            $conn->prepare("UPDATE reviews SET admin_reply = ?, admin_replied_at = NOW() WHERE id = ?")
                 ->execute([$reply, $reviewId]);
            $_SESSION['cart_message'] = 'Reply posted.';
        }
    }
}

$slug = '';
if ($action === 'submit') {
    $stmt = $conn->prepare("SELECT slug FROM products WHERE id = ?");
    $stmt->execute([$productId ?? 0]);
    $p = $stmt->fetch();
    if ($p) $slug = $p['slug'];
    header("Location: product.php?slug=" . urlencode($slug));
} else {
    // Admin reply redirect back to product page
    $stmt = $conn->prepare("SELECT p.slug FROM reviews r JOIN products p ON r.product_id = p.id WHERE r.id = ?");
    $stmt->execute([$reviewId ?? 0]);
    $p = $stmt->fetch();
    $slug = $p ? $p['slug'] : '';
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? ('product.php?slug=' . urlencode($slug))));
}
exit();
