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
        $search = $request->get('search', '');
        $category = $request->get('category', '');
        $filter = $request->get('filter', '');
        $sort = $request->get('sort', 'featured');
        $viewMode = $request->get('view', 'all');
        if (!in_array($viewMode, ['all', 'retail', 'bundle', 'wholesale'], true)) {
            $viewMode = 'all';
        }

        // Eloquent query
        $query = Product::where('is_active', 1);

        if ($search) {
            $query->where(function($q) use ($search){
                $q->where('name', 'like', "%$search%")
                  ->orWhere('variety', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });

            // Store in recent searches (limit 5)
            $recent = $request->session()->get('recent_searches', []);
            array_unshift($recent, $search);
            $recent = array_unique(array_slice($recent, 0, 5));
            $request->session()->put('recent_searches', $recent);
        }

        if ($category) {
            $cat = Category::where('slug', $category)->first();
            if ($cat) {
                $query->where('category_id', $cat->id);
            } else if ($category === 'best-sellers') {
                $query->where('is_best_seller', 1);
            }
        }

        if ($viewMode === 'retail') {
            // Retail should only show single-cup lettuce varieties.
            // Exclude mixed cups, bundles, packs, trays, boxes, and wholesale sacks.
            $query->whereHas('category', function($q) {
                $q->whereIn('slug', ['green-lettuce', 'red-lettuce']);
            })->where(function($q) {
                $q->where('name', 'not like', '%Bundle%')
                  ->where('name', 'not like', '%Wholesale%')
                  ->where('name', 'not like', '%Pack%')
                  ->where('name', 'not like', '%Tray%')
                  ->where('name', 'not like', '%Box%')
                  ->where('name', 'not like', '%5-Cup%')
                  ->where('name', 'not like', '%50-Cup%')
                  ->where('name', 'not like', '%Mixed%')
                  ->where('name', 'not like', '%Mix%');
            });
        } elseif ($viewMode === 'bundle') {
            $query->where(function($q) {
                $q->whereHas('category', function($c) {
                    $c->whereIn('slug', ['salad-mix-bundles', 'family-packs', 'twin-packs']);
                })
                ->orWhere('name', 'like', '%Bundle%')
                ->orWhere('name', 'like', '%5-Cup%')
                ->orWhere('unit', 'like', '%bundle%')
                ->orWhere('unit', 'like', '%5 cups%');
            });
        } elseif ($viewMode === 'wholesale') {
            $query->where(function($q) {
                $q->whereHas('category', function($c) {
                    $c->where('slug', 'wholesale');
                })
                ->orWhere('name', 'like', '%Wholesale%')
                ->orWhere('name', 'like', '%50-Cup%')
                ->orWhere('name', 'like', '%Tray%')
                ->orWhere('name', 'like', '%Box%')
                ->orWhere('name', 'like', '%Restaurant Pack%')
                ->orWhere('unit', 'like', '%tray%')
                ->orWhere('unit', 'like', '%box%')
                ->orWhere('unit', 'like', '%50 cups%');
            });
        }

        if ($filter === 'best_seller') {
            $query->where('is_best_seller', 1);
        }
        if ($filter === 'available') {
            $query->where('plants_available', '>', 0);
        }

        // Add sorting
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'newest') {
            $query->orderBy('created_at', 'desc');
        } elseif ($sort === 'name') {
            $query->orderBy('name', 'asc');
        } else {
            // featured (default): show newly added bundles/wholesale products first.
            $query->orderByDesc('is_new')
                ->orderByDesc('is_featured')
                ->orderByDesc('is_best_seller')
                ->orderByDesc('created_at');
        }

        // Load reviews with eager-loading subqueries or attributes
        $products = $query->get()->map(function($product) {
            // Fetch avg_rating and review_count exactly like original SQL
            $reviews = Review::where('product_id', $product->id)->where('is_approved', 1)->get();
            $product->avg_rating = $reviews->avg('rating') ?: 0;
            $product->review_count = $reviews->count();
            return $product;
        });

        $categories = Category::where('is_active', 1)->orderBy('sort_order')->get();
        $recentSearches = $request->session()->get('recent_searches', []);

        return view('products.index', compact('products', 'categories', 'search', 'category', 'filter', 'sort', 'viewMode', 'recentSearches'));
    }

    public function show(Request $request, $slug = null)
    {
        $slugParam = $slug ?? $request->get('slug');
        if (!$slugParam) {
            abort(404);
        }

        $product = Product::where('slug', $slugParam)->where('is_active', 1)->first();
        if (!$product) {
            $product = Product::where('id', $slugParam)->first();
        }
        if (!$product) {
            abort(404);
        }

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', 1)
            ->limit(4)->get();

        $productReviews = Review::where('product_id', $product->id)
            ->where('is_approved', 1)
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
            $canReview = \App\Models\Order::where('user_id', $userId)
                ->whereIn('status', ['delivered', 'completed'])
                ->whereHas('items', function($q) use ($product){
                    $q->where('product_id', $product->id);
                })->exists();
        }

        return view('products.show', compact('product', 'relatedProducts', 'productReviews', 'avg', 'totalReviews', 'canReview'));
    }
}
