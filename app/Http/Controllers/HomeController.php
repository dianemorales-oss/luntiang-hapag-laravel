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
                ->orderByDesc('is_new')
                ->orderByDesc('is_featured')
                ->orderByDesc('is_best_seller')
                ->orderByDesc('created_at')
                ->limit(24)
                ->get();
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
            $activeCoupons = Promotion::where('is_active', 1)->where(function($q){
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString());
            })->orderByDesc('created_at')->limit(3)->get();
            if ($isLoggedIn) {
                $claimedIds = ClaimedCoupon::where('user_id', $request->session()->get('user_id'))->pluck('promotion_id')->toArray();
            }
        } catch (\Exception $e) {
            $activeCoupons = collect([]);
        }

        return view('home', compact('featured', 'activeCoupons', 'claimedIds', 'isLoggedIn'));
    }
}
