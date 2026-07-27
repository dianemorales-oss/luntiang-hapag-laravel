<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviewId = (int)$request->input('review_id', 0);

        // Handle POST Actions inside GET route (backwards compatibility)
        if ($request->isMethod('post')) {
            if ($request->has('save_reply') && $reviewId) {
                $review = Review::findOrFail($reviewId);
                $review->admin_reply = trim($request->input('admin_reply'));
                $review->admin_replied_at = now();
                $review->save();
                return back()->with('success', 'Reply saved.');
            }

            if ($request->has('delete_reply') && $reviewId) {
                $review = Review::findOrFail($reviewId);
                $review->admin_reply = null;
                $review->admin_replied_at = null;
                $review->save();
                return back()->with('success', 'Reply deleted.');
            }

            if ($request->has('delete_review') && $reviewId) {
                Review::findOrFail($reviewId)->delete();
                return back()->with('success', 'Review deleted.');
            }
        }

        $productId = (int)$request->get('product', 0);
        $search = $request->get('search', '');
        $ratingFilter = $request->get('rating', '');
        $filterReplied = $request->get('replied', '');

        // Fetch active products
        $allProducts = Product::where('is_active', 1)->orderBy('name')->get();

        // Build reviews query
        $query = Review::with(['user', 'product', 'order']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($uq) use ($search) {
                    $uq->where('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%");
                })
                ->orWhereHas('product', function($pq) use ($search) {
                    $pq->where('name', 'like', "%$search%");
                })
                ->orWhereHas('order', function($oq) use ($search) {
                    $oq->where('order_number', 'like', "%$search%");
                });
            });
        }

        if ($ratingFilter !== '') {
            $query->where('rating', (int)$ratingFilter);
        }

        if ($filterReplied === 'yes') {
            $query->whereNotNull('admin_reply');
        } elseif ($filterReplied === 'no') {
            $query->whereNull('admin_reply');
        }

        $reviews = $query->orderByDesc('created_at')->limit(100)->get();

        // Statistics
        $totalReviews = Review::count();
        $avgRating = round(Review::avg('rating') ?: 0, 1);
        $pendingReplies = Review::whereNull('admin_reply')->count();

        // Top products by rating
        $topProducts = DB::table('reviews')
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', DB::raw('ROUND(AVG(reviews.rating), 1) as avg_rating'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get();

        return view('admin.reviews.index', compact(
            'reviews', 'allProducts', 'productId', 'search', 'ratingFilter', 'filterReplied',
            'totalReviews', 'avgRating', 'pendingReplies', 'topProducts'
        ));
    }

    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $review->is_approved = $request->has('is_approved');
        $review->admin_reply = $request->input('admin_reply');
        if ($review->admin_reply) $review->admin_replied_at = now();
        $review->save();
        return back()->with('success','Updated');
    }

    public function destroy($id)
    {
        Review::findOrFail($id)->delete();
        return back()->with('success','Deleted');
    }
}
