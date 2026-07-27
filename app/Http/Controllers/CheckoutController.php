<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\ClaimedCoupon;
use App\Models\CustomerAddress;
use App\Helpers\CartHelper;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            return redirect()->route('login');
        }

        $userId = $request->session()->get('user_id');
        $cart = $request->session()->get('cart', []);
        $selectedIds = array_map('intval', (array) $request->input('sel', $request->session()->get('selected_cart', [])));
        $request->session()->put('selected_cart', $selectedIds);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty');
        }

        $productIds = array_column($cart, 'id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $cartItems = [];
        $subtotal = 0;
        foreach ($cart as $item) {
            $prod = $products[$item['id']] ?? null;
            if (!$prod) continue;
            $qty = (int)$item['qty'];
            if (!in_array($prod->id, $selectedIds)) continue;
            $line = (float)$prod->price * $qty;
            $cartItems[] = [
                'id' => $prod->id,
                'name' => $prod->name,
                'price' => (float)$prod->price,
                'qty' => $qty,
                'line_total' => $line,
            ];
            $subtotal += $line;
        }

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('error', 'No items selected');
        }

        // addresses
        $savedAddresses = CustomerAddress::where('user_id', $userId)->get();
        $defaultAddr = $savedAddresses->where('is_default', true)->first() ?? $savedAddresses->first();
        $defaultAddress = $defaultAddr->address ?? '';
        $defaultCity = $defaultAddr->city ?? '';
        $defaultProvince = $defaultAddr->province ?? '';
        $defaultZip = $defaultAddr->zip ?? '';

        // user info
        $user = \App\Models\User::find($userId);

        // delivery fee logic
        $isFreeZone = false;
        if ($defaultAddr && stripos($defaultAddr->address, 'Nostalji') !== false) {
            $isFreeZone = true;
        }
        // also check posted address? We'll handle in POST

        $deliveryFee = $isFreeZone ? 0 : 50;
        $appliedPromoCode = $request->session()->get('applied_promo');
        $promo = null;
        $discount = 0;
        if ($appliedPromoCode) {
            $promo = $this->validClaimedPromo($userId, $appliedPromoCode);
            if ($promo) {
                if ($promo->is_free_delivery) $deliveryFee = 0;
                $discount = $promo->discount_type === 'percentage'
                    ? $subtotal * ($promo->discount_value/100)
                    : (float)$promo->discount_value;
            } else {
                $request->session()->forget('applied_promo');
            }
        }

        $total = max(0, $subtotal + $deliveryFee - $discount);
        $isFreeDeliveryZone = $isFreeZone;

        return view('checkout.index', compact('cartItems','subtotal','deliveryFee','discount','total','savedAddresses','defaultAddress','defaultCity','defaultProvince','defaultZip','isFreeZone','promo','user','isFreeDeliveryZone'));
    }

    public function store(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            return redirect()->route('login');
        }
        $userId = $request->session()->get('user_id');
        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        $productIds = array_column($cart, 'id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $selectedIds = array_map('intval', (array) $request->session()->get('selected_cart', []));
        if (empty($selectedIds)) {
            return redirect()->route('cart.index')->with('error', 'No items selected');
        }

        $cartItems = [];
        $subtotal = 0;
        foreach ($cart as $item) {
            $prod = $products[$item['id']] ?? null;
            if (!$prod || !in_array((int)$prod->id, $selectedIds, true)) continue;
            $qty = (int)$item['qty'];
            $line = (float)$prod->price * $qty;
            $cartItems[] = ['id'=>$prod->id,'name'=>$prod->name,'price'=>(float)$prod->price,'qty'=>$qty,'line_total'=>$line,'product'=>$prod];
            $subtotal += $line;
        }

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('error', 'No selected cart items found');
        }

        $deliveryMethod = $request->input('delivery_method', 'delivery');
        $paymentMethod = $request->input('payment_method', 'cod');
        $paymentReference = trim($request->input('payment_reference', ''));
        $address = $request->input('address');
        $city = $request->input('city');
        $province = $request->input('province');
        $zip = $request->input('zip');
        $deliveryNotes = $request->input('delivery_notes');
        $giftNote = $request->input('gift_note');
        $preferredTime = $request->input('preferred_time');

        if (!in_array($paymentMethod, ['cod', 'gcash', 'maya', 'bank_transfer'], true)) {
            return back()->with('error', 'Please select a valid payment method.')->withInput();
        }

        if (in_array($paymentMethod, ['gcash', 'maya'], true) && !preg_match('/^\d{11}$/', $paymentReference)) {
            return back()->with('error', 'Please enter a valid 11-digit mobile number for ' . strtoupper($paymentMethod) . '.')->withInput();
        }
        if ($paymentMethod === 'bank_transfer' && !preg_match('/^\d{6,30}$/', $paymentReference)) {
            return back()->with('error', 'Please enter a valid bank account number.')->withInput();
        }

        if ($paymentMethod !== 'cod' && $paymentReference !== '') {
            $paymentLabel = ['gcash' => 'GCash', 'maya' => 'Maya', 'bank_transfer' => 'Bank Transfer'][$paymentMethod] ?? strtoupper($paymentMethod);
            $paymentNote = "Payment {$paymentLabel} reference: {$paymentReference}";
            $deliveryNotes = trim($deliveryNotes ? $deliveryNotes . "\n" . $paymentNote : $paymentNote);
        }

        $isFreeZone = false;
        if ($deliveryMethod === 'pickup') {
            $isFreeZone = true;
            $address = $city = $province = $zip = null;
        } else {
            if ($address && stripos($address, 'Nostalji') !== false) $isFreeZone = true;
        }

        $deliveryFee = $isFreeZone ? 0 : 50;

        $appliedPromoCode = $request->session()->get('applied_promo');
        $promo = null;
        $discount = 0;
        if ($appliedPromoCode) {
            $promo = $this->validClaimedPromo($userId, $appliedPromoCode);
            if ($promo) {
                if ($promo->is_free_delivery) $deliveryFee = 0;
                $discount = $promo->discount_type === 'percentage'
                    ? $subtotal * ($promo->discount_value/100)
                    : (float)$promo->discount_value;
            } else {
                $request->session()->forget('applied_promo');
            }
        }

        $total = max(0, $subtotal + $deliveryFee - $discount);

        // generate order number LH-XXXX unique
        $orderNumber = 'LH-' . str_pad(random_int(0,9999),4,'0',STR_PAD_LEFT);
        while (Order::where('order_number',$orderNumber)->exists()) {
            $orderNumber = 'LH-' . str_pad(random_int(0,9999),4,'0',STR_PAD_LEFT);
        }

        $user = \App\Models\User::find($userId);

        \DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'status' => 'preparing',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount' => $discount,
                'total' => $total,
                'delivery_method' => $deliveryMethod,
                'payment_method' => $paymentMethod,
                'promo_code' => $appliedPromoCode,
                'delivery_address' => $address,
                'delivery_city' => $city,
                'delivery_province' => $province,
                'delivery_zip' => $zip,
                'delivery_notes' => $deliveryNotes,
                'gift_note' => $giftNote,
                'preferred_delivery_time' => $preferredTime,
                'is_free_delivery' => $isFreeZone,
                'estimated_harvest_time' => '1-3 hours',
                'customer_name' => $user->first_name . ' ' . $user->last_name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone,
            ]);

            foreach ($cartItems as $ci) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $ci['id'],
                    'product_name' => $ci['name'],
                    'price' => $ci['price'],
                    'quantity' => $ci['qty'],
                ]);
                // decrement stock
                // Five-cup bundle products deduct 5 plants/cups per purchased bundle.
                // If a bundle points to a source product, deduct from both the bundle row
                // and the source product row so admin inventory stays in sync.
                $stockMultiplier = max(1, (int)($ci['product']->stock_multiplier ?? 1));
                $stockToDeduct = $ci['qty'] * $stockMultiplier;
                $stockTargets = [$ci['product']];

                if (!empty($ci['product']->stock_product_id)) {
                    $sourceProduct = Product::find($ci['product']->stock_product_id);
                    if ($sourceProduct && $sourceProduct->id !== $ci['product']->id) {
                        $stockTargets[] = $sourceProduct;
                    }
                }

                foreach ($stockTargets as $stockProduct) {
                    if ($stockProduct && $stockToDeduct > 0) {
                        $stockProduct->plants_available = max(0, (int)$stockProduct->plants_available - $stockToDeduct);
                        $stockProduct->save();
                    }
                }
            }

            // promo usage: mark claimed voucher as redeemed once.
            if ($promo) {
                $promo->increment('used_count');
                $claimedCoupon = ClaimedCoupon::where('user_id', $userId)->where('promotion_id', $promo->id)->first();
                if ($claimedCoupon && Schema::hasColumn('claimed_coupons', 'used_at')) {
                    $claimedCoupon->used_at = now();
                    $claimedCoupon->save();
                }
            }

            // clear only purchased/selected items; leave unselected cart items intact
            $remainingCart = array_values(array_filter($cart, function($item) use ($selectedIds) {
                return !in_array((int)$item['id'], $selectedIds, true);
            }));
            $request->session()->put('cart', $remainingCart);
            $request->session()->forget('applied_promo');
            $request->session()->forget('selected_cart');
            CartHelper::syncToDb($userId);

            // notification
            NotificationHelper::create('order_new', $order->id, 'New Order', "Order {$orderNumber} placed by {$user->first_name}", $user->first_name . ' ' . $user->last_name);

            \DB::commit();

            return redirect()->route('order.confirmation', ['order'=>$orderNumber]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->with('error', 'Order failed: ' . $e->getMessage());
        }
    }

    private function validClaimedPromo(int $userId, ?string $code): ?Promotion
    {
        if (!$code) return null;
        $promo = Promotion::where('code', $code)->where('is_active', 1)->first();
        if (!$promo) return null;
        if ($promo->expires_at && $promo->expires_at < now()->toDateString()) return null;
        $claimedCoupon = ClaimedCoupon::where('user_id', $userId)->where('promotion_id', $promo->id)->first();
        if (!$claimedCoupon) return null;
        if (Schema::hasColumn('claimed_coupons', 'used_at') && !empty($claimedCoupon->used_at)) return null;
        return $promo;
    }

}
