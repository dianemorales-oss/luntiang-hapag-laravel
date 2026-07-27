<?php
namespace App\Http\Controllers;
use App\Models\Review;
use App\Models\Product;
use App\Helpers\FormHelper;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        if (!$request->session()->has('user_id')) return redirect()->route('login');
        $userId = $request->session()->get('user_id');
        $productId = (int)$request->input('product_id');
        $rating = (int)$request->input('rating');
        $comment = trim($request->input('comment',''));

        if ($rating < 1 || $rating > 5) return back()->with('error','Invalid rating');
        $product = Product::find($productId);
        if (!$product) return back()->with('error','Product not found');

        // handle photos
        $upload = FormHelper::handleUpload($request->file('review_photos'), ['jpg','jpeg','png'], storage_path('app/public/reviews'), 'uploads/reviews', false);
        if (!$upload['ok']) return back()->with('error',$upload['error']);

        $isVerified = \App\Models\Order::where('user_id',$userId)
            ->whereIn('status', ['delivered', 'completed'])
            ->whereHas('items', fn($q)=>$q->where('product_id',$productId))
            ->exists();

        if (!$isVerified) {
            return back()->with('error', 'You can only review products that you have purchased.');
        }

        Review::create([
            'user_id'=>$userId,
            'product_id'=>$productId,
            'rating'=>$rating,
            'comment'=>$comment,
            'photos'=>FormHelper::encodeAttachmentPaths($upload['paths']),
            'is_verified'=>$isVerified,
            'is_approved'=>true,
        ]);

        return back()->with('success','Review submitted');
    }

    public function reply(Request $request)
    {
        // admin only
        if (!$request->session()->has('admin_id')) return redirect()->route('admin.login');
        $reviewId = (int)$request->input('review_id');
        $reply = trim($request->input('admin_reply',''));
        $review = Review::findOrFail($reviewId);
        $review->admin_reply = $reply;
        $review->admin_replied_at = now();
        $review->save();
        return back()->with('success','Reply saved');
    }
}
