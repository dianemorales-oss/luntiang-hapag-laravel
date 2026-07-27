<?php
namespace App\Helpers;
use App\Models\CartItem;
use Illuminate\Support\Facades\Session;

class CartHelper
{
    public static function getCart(): array
    {
        return Session::get('cart', []);
    }

    public static function syncToDb(int $userId): void
    {
        $cart = Session::get('cart', []);
        CartItem::where('user_id', $userId)->delete();
        foreach ($cart as $item) {
            CartItem::updateOrCreate(
                ['user_id' => $userId, 'product_id' => $item['id']],
                ['quantity' => $item['qty']]
            );
        }
    }

    public static function loadFromDb(int $userId): void
    {
        $rows = CartItem::where('user_id', $userId)->get();
        $cart = [];
        foreach ($rows as $r) {
            $cart[] = ['id' => (int)$r->product_id, 'qty' => (int)$r->quantity];
        }
        Session::put('cart', $cart);
    }

    public static function mergeGuestCart(int $userId, array $guestCart): void
    {
        self::loadFromDb($userId);
        $existing = Session::get('cart', []);
        foreach ($guestCart as $gItem) {
            $found = false;
            foreach ($existing as &$eItem) {
                if ($eItem['id'] === $gItem['id']) {
                    $eItem['qty'] += $gItem['qty'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $existing[] = $gItem;
            }
        }
        Session::put('cart', $existing);
        self::syncToDb($userId);
    }

    public static function countUnique(): int
    {
        $cart = Session::get('cart', []);
        return count(array_unique(array_column($cart, 'id')));
    }

    public static function countTotal(): int
    {
        $cart = Session::get('cart', []);
        return array_sum(array_column($cart, 'qty'));
    }
}
