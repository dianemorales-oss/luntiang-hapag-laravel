<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with(['user','product'])->orderByDesc('created_at')->get();
        return view('admin.reviews.index', compact('reviews'));
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
