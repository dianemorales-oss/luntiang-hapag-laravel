<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\ClaimedCoupon;
use App\Helpers\CartHelper;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $appliedPromo = $request->session()->get('applied_promo');

        if (empty($cart)) {
            return view('cart.index', ['cartItems'=>[], 'selectedSubtotal'=>0, 'deliveryFee'=>0, 'discount'=>0, 'total'=>0, 'isFreeDeliveryZone'=>false, 'isLoggedIn'=> $request->session()->has('user_id'), 'promo'=>$appliedPromo, 'claimedCoupons'=>[]]);
        }

        // Build cart items from session
        $productIds = array_column($cart, 'id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $cartItems = [];
        $selectedSubtotal = 0;
        foreach ($cart as $item) {
            $prod = $products[$item['id']] ?? null;
            if (!$prod) continue;
            $qty = (int)$item['qty'];
            $line = (float)$prod->price * $qty;
            $cartItems[] = [
                'id' => $prod->id,
                'name' => $prod->name,
                'slug' => $prod->slug,
                'image' => $prod->image,
                'price' => (float)$prod->price,
                'qty' => $qty,
                'line_total' => $line,
                'harvest_time' => $prod->harvest_time,
                'selected' => true,
            ];
            $selectedSubtotal += $line;
        }

        // Default selected
        $selectedCount = count($cartItems);
        $allSelected = true;

        // Delivery logic: free within Nostalji? We'll check session default zone false, but simplify: free if address contains Nostalji
        $isFreeDeliveryZone = false;
        $userId = $request->session()->get('user_id');
        if ($userId) {
            $addr = \App\Models\CustomerAddress::where('user_id', $userId)->where('is_default', true)->first();
            if ($addr && stripos($addr->address, 'Nostalji') !== false) {
                $isFreeDeliveryZone = true;
            }
        }

        $deliveryFee = $isFreeDeliveryZone ? 0 : ($selectedCount > 0 ? 50 : 0);
        $discount = 0;
        $promo = null;
        if ($appliedPromo) {
            $promo = Promotion::where('code', $appliedPromo)->first();
            if ($promo) {
                if ($promo->is_free_delivery) $deliveryFee = 0;
                if ($promo->discount_type === 'percentage') {
                    $discount = $selectedSubtotal * ($promo->discount_value / 100);
                } else {
                    $discount = (float)$promo->discount_value;
                }
            } else {
                $request->session()->forget('applied_promo');
            }
        }

        $total = max(0, $selectedSubtotal + $deliveryFee - $discount);

        // claimed coupons
        $claimedCoupons = [];
        if ($userId) {
            $claimedCoupons = ClaimedCoupon::where('user_id', $userId)->with('promotion')->get()->map(function($cc){
                return $cc->promotion;
            })->filter();
        }

        return view('cart.index', compact('cartItems','selectedSubtotal','deliveryFee','discount','total','isFreeDeliveryZone','claimedCoupons','promo','selectedCount','allSelected'));
    }

    public function add(Request $request)
    {
        $productId = (int)($request->get('id') ?? $request->input('id'));
        $qty = max(1, (int)($request->get('qty') ?? $request->input('qty', 1)));
        $product = Product::where('id', $productId)->where('is_active', 1)->first();
        if (!$product) {
            if ($request->expectsJson() || $request->is('api/*') || $request->header('Content-Type') === 'application/json') {
                return response()->json(['success'=>false,'message'=>'Product not available']);
            }
            return redirect()->back()->with('error', 'Product not available.');
        }

        $cart = $request->session()->get('cart', []);
        $found = false;
        foreach ($cart as &$item) {
            if ($item['id'] === $productId) {
                $item['qty'] = min($item['qty'] + $qty, $product->plants_available > 0 ? $product->plants_available : 999);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $cart[] = ['id'=>$productId, 'qty'=>$qty];
        }
        $request->session()->put('cart', $cart);

        // sync to DB if logged in
        if ($request->session()->has('user_id')) {
            CartHelper::syncToDb($request->session()->get('user_id'));
        }

        if ($request->input('redirect') === 'cart' || $request->get('redirect') === 'cart') {
            return redirect()->route('cart.index')->with('success', 'Added to cart');
        }

        $ref = $request->header('referer') ?? route('products.index');
        return redirect()->to($ref)->with('success', 'Added to cart');
    }

    public function addAjax(Request $request)
    {
        $data = $request->json()->all() ?: $request->all();
        $action = $data['action'] ?? $request->input('action');
        $productId = (int)($data['id'] ?? 0);
        $qty = (int)($data['qty'] ?? 1);
        $promoCode = $data['promo_code'] ?? null;

        $cart = $request->session()->get('cart', []);
        if (!is_array($cart)) $cart = [];

        switch($action) {
            case 'add':
                $product = Product::where('id', $productId)->where('is_active',1)->first();
                if (!$product) {
                    return response()->json(['success'=>false,'message'=>'Product not available']);
                }
                $found = false;
                foreach ($cart as &$item) {
                    if ($item['id'] === $productId) {
                        $item['qty'] = min($item['qty'] + $qty, $product->plants_available ?: 999);
                        $found = true;
                        break;
                    }
                }
                if (!$found) $cart[] = ['id'=>$productId,'qty'=>$qty];
                $request->session()->put('cart', $cart);
                if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
                $count = count(array_unique(array_column($cart,'id')));
                return response()->json(['success'=>true,'message'=>'Added to cart','count'=>$count]);

            case 'update':
                $newQty = max(1, $qty);
                foreach ($cart as &$item) {
                    if ($item['id'] === $productId) {
                        $item['qty'] = $newQty;
                        break;
                    }
                }
                $request->session()->put('cart',$cart);
                if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
                $product = Product::find($productId);
                $price = $product ? (float)$product->price : 0;
                $lineTotal = number_format($price * $newQty, 2);
                return response()->json(['success'=>true,'qty'=>$newQty,'line_total'=>$lineTotal]);

            case 'remove':
                $cart = array_values(array_filter($cart, fn($i)=> (int)$i['id'] !== $productId));
                $request->session()->put('cart',$cart);
                if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
                return response()->json(['success'=>true]);

            case 'clear':
                $request->session()->put('cart', []);
                if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
                return response()->json(['success'=>true]);

            case 'select_promo':
                $promo = Promotion::where('code',$promoCode)->where('is_active',1)->first();
                if (!$promo) {
                    return response()->json(['success'=>false,'message'=>'Invalid coupon']);
                }
                // check expiry
                if ($promo->expires_at && $promo->expires_at < now()->toDateString()) {
                    return response()->json(['success'=>false,'message'=>'Coupon expired']);
                }
                // min order check would be in checkout, but we allow selection
                $request->session()->put('applied_promo',$promo->code);
                return response()->json(['success'=>true,'promo'=>[
                    'code'=>$promo->code,
                    'discount_type'=>$promo->discount_type,
                    'discount_value'=>(float)$promo->discount_value,
                    'is_free_delivery'=>(bool)$promo->is_free_delivery
                ]]);

            case 'remove_promo':
                $request->session()->forget('applied_promo');
                return response()->json(['success'=>true]);

            default:
                return response()->json(['success'=>false,'message'=>'Invalid action']);
        }
    }

    public function clear(Request $request)
    {
        $request->session()->put('cart', []);
        if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
        return redirect()->route('cart.index');
    }
}
