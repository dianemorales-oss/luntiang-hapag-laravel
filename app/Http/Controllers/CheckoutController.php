<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\CustomerAddress;
use App\Helpers\CartHelper;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            return redirect()->route('login');
        }

        $userId = $request->session()->get('user_id');
        $cart = $request->session()->get('cart', []);
        $selectedIds = $request->session()->get('selected_cart') ?? array_column($cart, 'id'); // fallback all selected
        // If cart-actions using session selected, but we have simple cart selection all
        $selectedIds = array_column($cart, 'id'); // for simplicity, all items are selected

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
            $promo = Promotion::where('code', $appliedPromoCode)->where('is_active',1)->first();
            if ($promo) {
                if ($promo->is_free_delivery) $deliveryFee = 0;
                if ($promo->discount_type === 'percentage') {
                    $discount = $subtotal * ($promo->discount_value/100);
                } else {
                    $discount = (float)$promo->discount_value;
                }
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

        $cartItems = [];
        $subtotal = 0;
        foreach ($cart as $item) {
            $prod = $products[$item['id']] ?? null;
            if (!$prod) continue;
            $qty = (int)$item['qty'];
            $line = (float)$prod->price * $qty;
            $cartItems[] = ['id'=>$prod->id,'name'=>$prod->name,'price'=>(float)$prod->price,'qty'=>$qty,'line_total'=>$line,'product'=>$prod];
            $subtotal += $line;
        }

        $deliveryMethod = $request->input('delivery_method', 'delivery');
        $paymentMethod = $request->input('payment_method', 'cod');
        $address = $request->input('address');
        $city = $request->input('city');
        $province = $request->input('province');
        $zip = $request->input('zip');
        $deliveryNotes = $request->input('delivery_notes');
        $giftNote = $request->input('gift_note');
        $preferredTime = $request->input('preferred_time');

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
            $promo = Promotion::where('code', $appliedPromoCode)->where('is_active',1)->first();
            if ($promo) {
                if ($promo->is_free_delivery) $deliveryFee = 0;
                if ($promo->discount_type === 'percentage') {
                    $discount = $subtotal * ($promo->discount_value/100);
                } else {
                    $discount = (float)$promo->discount_value;
                }
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
                if ($ci['product']->plants_available > 0) {
                    $ci['product']->decrement('plants_available', $ci['qty']);
                }
            }

            // promo usage
            if ($promo) {
                $promo->increment('used_count');
            }

            // clear cart (only purchased items)
            $request->session()->put('cart', []);
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
}
