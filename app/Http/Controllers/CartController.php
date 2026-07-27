<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\ClaimedCoupon;
use App\Helpers\CartHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $selectedIds = array_map('intval', (array)$request->session()->get('selected_cart', []));
        $appliedPromo = $request->session()->get('applied_promo');

        if (empty($cart)) {
            $request->session()->forget(['applied_promo', 'selected_cart']);
            return view('cart.index', [
                'cartItems'=>[], 'selectedSubtotal'=>0, 'deliveryFee'=>0, 'discount'=>0, 'total'=>0,
                'isFreeDeliveryZone'=>false, 'isLoggedIn'=> $request->session()->has('user_id'),
                'promo'=>null, 'claimedCoupons'=>collect(), 'selectedCount'=>0, 'allSelected'=>false,
            ]);
        }

        $productIds = array_column($cart, 'id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $cartItems = [];
        $selectedSubtotal = 0;
        foreach ($cart as $item) {
            $prod = $products[$item['id']] ?? null;
            if (!$prod) continue;
            $qty = (int)$item['qty'];
            $line = (float)$prod->price * $qty;
            $isSelected = in_array((int)$prod->id, $selectedIds, true);
            $cartItems[] = [
                'id'=>$prod->id, 'name'=>$prod->name, 'slug'=>$prod->slug, 'image'=>$prod->image,
                'price'=>(float)$prod->price, 'qty'=>$qty, 'line_total'=>$line,
                'harvest_time'=>$prod->harvest_time, 'selected'=>$isSelected,
            ];
            if ($isSelected) $selectedSubtotal += $line;
        }

        $selectedCount = collect($cartItems)->where('selected', true)->count();
        $allSelected = $selectedCount > 0 && $selectedCount === count($cartItems);

        $isFreeDeliveryZone = false;
        $userId = $request->session()->get('user_id');
        if ($userId) {
            $addr = \App\Models\CustomerAddress::where('user_id', $userId)->where('is_default', true)->first();
            if ($addr && stripos($addr->address, 'Nostalji') !== false) $isFreeDeliveryZone = true;
        }

        $deliveryFee = $isFreeDeliveryZone ? 0 : ($selectedCount > 0 ? 50 : 0);
        $discount = 0;
        $promo = null;
        if ($appliedPromo) {
            $promo = $this->validClaimedPromo($userId, $appliedPromo);
            if ($promo) {
                if ($promo->is_free_delivery) $deliveryFee = 0;
                $discount = $promo->discount_type === 'percentage'
                    ? $selectedSubtotal * ($promo->discount_value / 100)
                    : (float)$promo->discount_value;
            } else {
                $request->session()->forget('applied_promo');
            }
        }

        $total = max(0, $selectedSubtotal + $deliveryFee - $discount);

        $claimedCoupons = collect();
        if ($userId) {
            $query = ClaimedCoupon::where('user_id', $userId)->with('promotion');
            if (Schema::hasColumn('claimed_coupons', 'used_at')) $query->whereNull('used_at');
            $claimedCoupons = $query->get()->map(fn($cc) => $cc->promotion)->filter();
        }

        return view('cart.index', compact('cartItems','selectedSubtotal','deliveryFee','discount','total','isFreeDeliveryZone','claimedCoupons','promo','selectedCount','allSelected'));
    }

    public function add(Request $request)
    {
        $productId = (int)($request->get('id') ?? $request->input('id'));
        $qty = max(1, (int)($request->get('qty') ?? $request->input('qty', 1)));
        $product = Product::where('id', $productId)->where('is_active', 1)->first();
        if (!$product) return redirect()->back()->with('error', 'Product not available.');

        $maxPurchasable = $this->purchasableUnits($product);
        if ($maxPurchasable <= 0) return redirect()->back()->with('error', 'Product is out of stock.');

        $cart = $request->session()->get('cart', []);
        $found = false;
        foreach ($cart as &$item) {
            if ((int)$item['id'] === $productId) {
                $item['qty'] = min(((int)$item['qty']) + $qty, $maxPurchasable);
                $found = true;
                break;
            }
        }
        unset($item);
        if (!$found) $cart[] = ['id'=>$productId, 'qty'=>min($qty, $maxPurchasable)];
        $request->session()->put('cart', $cart);

        if ($request->input('redirect') === 'checkout' || $request->get('redirect') === 'checkout') {
            $request->session()->put('selected_cart', [$productId]);
        }

        if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));

        if ($request->input('redirect') === 'checkout' || $request->get('redirect') === 'checkout') return redirect()->route('checkout.index');
        if ($request->input('redirect') === 'cart' || $request->get('redirect') === 'cart') return redirect()->route('cart.index')->with('success', 'Added to cart');
        return redirect()->to($request->header('referer') ?? route('products.index'))->with('success', 'Added to cart');
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
                if (!$product) return response()->json(['success'=>false,'message'=>'Product not available']);
                $maxPurchasable = $this->purchasableUnits($product);
                if ($maxPurchasable <= 0) return response()->json(['success'=>false,'message'=>'Product is out of stock']);
                $found = false;
                foreach ($cart as &$item) {
                    if ((int)$item['id'] === $productId) {
                        $item['qty'] = min(((int)$item['qty']) + max(1,$qty), $maxPurchasable);
                        $found = true;
                        break;
                    }
                }
                unset($item);
                if (!$found) $cart[] = ['id'=>$productId,'qty'=>min(max(1,$qty), $maxPurchasable)];
                $request->session()->put('cart', $cart);
                if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
                return response()->json(['success'=>true,'message'=>'Added to cart','count'=>count(array_unique(array_column($cart,'id')))]);

            case 'update':
                $product = Product::find($productId);
                if (!$product) return response()->json(['success'=>false,'message'=>'Product not found']);
                $newQty = min(max(1, $qty), max(1, $this->purchasableUnits($product)));
                foreach ($cart as &$item) if ((int)$item['id'] === $productId) { $item['qty'] = $newQty; break; }
                unset($item);
                $request->session()->put('cart',$cart);
                if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
                return response()->json(['success'=>true,'qty'=>$newQty,'line_total'=>number_format((float)$product->price * $newQty, 2)]);

            case 'remove':
                $cart = array_values(array_filter($cart, fn($i)=> (int)$i['id'] !== $productId));
                $selected = array_values(array_diff((array)$request->session()->get('selected_cart', []), [$productId]));
                $request->session()->put('cart',$cart);
                $request->session()->put('selected_cart',$selected);
                if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
                return response()->json(['success'=>true]);

            case 'clear':
                $request->session()->put('cart', []);
                $request->session()->forget(['selected_cart','applied_promo']);
                if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
                return response()->json(['success'=>true]);

            case 'select_promo':
                $userId = $request->session()->get('user_id');
                $promo = $this->validClaimedPromo($userId, $promoCode);
                if (!$promo) return response()->json(['success'=>false,'message'=>'Invalid, unclaimed, expired, or already used coupon']);
                $request->session()->put('applied_promo',$promo->code);
                return response()->json(['success'=>true,'promo'=>[
                    'code'=>$promo->code,'discount_type'=>$promo->discount_type,
                    'discount_value'=>(float)$promo->discount_value,'is_free_delivery'=>(bool)$promo->is_free_delivery
                ]]);

            case 'remove_promo':
                $request->session()->forget('applied_promo');
                return response()->json(['success'=>true]);

            default:
                return response()->json(['success'=>false,'message'=>'Invalid action']);
        }
    }

    private function validClaimedPromo($userId, ?string $code): ?Promotion
    {
        if (!$userId || !$code) return null;
        $promo = Promotion::where('code',$code)->where('is_active',1)->first();
        if (!$promo) return null;
        if ($promo->expires_at && $promo->expires_at < now()->toDateString()) return null;
        $claim = ClaimedCoupon::where('user_id',$userId)->where('promotion_id',$promo->id)->first();
        if (!$claim) return null;
        if (Schema::hasColumn('claimed_coupons', 'used_at') && !empty($claim->used_at)) return null;
        return $promo;
    }

    private function purchasableUnits(Product $product): int
    {
        $available = (int) $product->plants_available;
        if (!empty($product->stock_product_id)) {
            $sourceProduct = Product::find($product->stock_product_id);
            if ($sourceProduct) $available = min($available, (int)$sourceProduct->plants_available);
        }
        $multiplier = max(1, (int)($product->stock_multiplier ?? 1));
        return $multiplier > 1 ? intdiv(max(0, $available), $multiplier) : max(0, $available);
    }

    public function clear(Request $request)
    {
        $request->session()->put('cart', []);
        $request->session()->forget(['selected_cart','applied_promo']);
        if ($request->session()->has('user_id')) CartHelper::syncToDb($request->session()->get('user_id'));
        return redirect()->route('cart.index');
    }
}
