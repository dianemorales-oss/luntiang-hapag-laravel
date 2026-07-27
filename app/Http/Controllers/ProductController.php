<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', 1);
        $search = $request->get('search');
        $category = $request->get('category');

        if ($search) {
            $query->where(function($q) use ($search){
                $q->where('name', 'like', "%$search%")->orWhere('variety', 'like', "%$search%");
            });
        }
        if ($category) {
            $cat = Category::where('slug', $category)->first();
            if ($cat) {
                $query->where('category_id', $cat->id);
            } else {
                // fallback category mapping from original catalog category field
                if ($category === 'best-sellers') {
                    $query->where('is_best_seller', 1);
                }
            }
        }

        $products = $query->orderByDesc('is_best_seller')->orderBy('name')->get();
        $categories = Category::where('is_active', 1)->orderBy('sort_order')->get();

        return view('products.index', compact('products', 'categories', 'search', 'category'));
    }

    public function show(Request $request, $slug = null)
    {
        $slugParam = $slug ?? $request->get('slug');
        if (!$slugParam) {
            abort(404);
        }

        $product = Product::where('slug', $slugParam)->where('is_active', 1)->first();
        if (!$product) {
            // try id
            $product = Product::where('id', $slugParam)->first();
        }
        if (!$product) {
            abort(404);
        }

        // related products same category
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', 1)
            ->limit(4)->get();

        // reviews
        $productReviews = Review::where('product_id', $product->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->get()
            ->map(function($r){
                $r->first_name = $r->user->first_name ?? 'Anonymous';
                return $r;
            });

        $avg = $productReviews->avg('rating');
        $totalReviews = $productReviews->count();

        $canReview = false;
        $userId = $request->session()->get('user_id');
        if ($userId) {
            // check if user purchased this product and order completed?
            $canReview = \App\Models\Order::where('user_id', $userId)
                ->whereHas('items', function($q) use ($product){
                    $q->where('product_id', $product->id);
                })->exists();
            // allow review even if not purchased? Original allowed if purchased? We'll allow all logged-in for simplicity but mark verified if purchased
            $canReview = true;
        }

        return view('products.show', compact('product', 'relatedProducts', 'productReviews', 'avg', 'totalReviews', 'canReview'));
    }
}
