<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\ClaimedCoupon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $featured = Product::where('is_active', 1)
                ->where('is_featured', 1)
                ->orderByDesc('is_best_seller')
                ->orderByDesc('created_at')
                ->limit(12)
                ->get()
                ->unique('slug')
                ->values();

            // Existing databases may not have featured flags set yet.
            // Keep the homepage populated while the repair migration is pending.
            if ($featured->isEmpty()) {
                $featured = Product::where('is_active', 1)
                    ->orderByDesc('is_best_seller')
                    ->orderByDesc('created_at')
                    ->limit(12)
                    ->get()
                    ->unique('slug')
                    ->values();
            }
        } catch (\Exception $e) {
            // fallback to catalog
            $catalog = \App\Helpers\LettuceCatalog::get();
            $featured = collect(array_slice($catalog, 0, 24))->map(function($p, $i){
                return (object)array_merge($p, ['id'=>$i+1, 'slug'=>\Str::slug($p['name']), 'is_best_seller'=>$p['bestSeller'] ?? false, 'plants_available'=>999]);
            });
        }

        $isLoggedIn = $request->session()->has('user_id');
        $activeCoupons = [];
        $claimedIds = [];
        try {
            $promotionQuery = Promotion::where('is_active', 1)->where(function($q){
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString());
            })->orderByDesc('created_at')->limit(10);

            $allPromos = $promotionQuery->get();

            if ($isLoggedIn) {
                $userId = $request->session()->get('user_id');
                $claimedIds = ClaimedCoupon::where('user_id', $userId)->pluck('promotion_id')->toArray();
                // Vanish once claimed: exclude claimed coupons from display
                $activeCoupons = $allPromos->whereNotIn('id', $claimedIds)->take(3)->values();
            } else {
                $activeCoupons = $allPromos->take(3);
            }
        } catch (\Exception $e) {
            $activeCoupons = collect([]);
        }

        return view('home', compact('featured', 'activeCoupons', 'claimedIds', 'isLoggedIn'));
    }
}
