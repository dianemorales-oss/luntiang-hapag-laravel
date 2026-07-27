<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $ratingFilter = $request->query('rating', 'all');
        $validRatings = ['1', '2', '3', '4', '5'];

        if (!in_array((string) $ratingFilter, $validRatings, true)) {
            $ratingFilter = 'all';
        }

        $feedbackQuery = Feedback::with('user')->orderByDesc('created_at');

        if ($ratingFilter !== 'all') {
            $feedbackQuery->where('rating', (int) $ratingFilter);
        }

        $feedbacks = $feedbackQuery->get();
        $totalCount = Feedback::count();
        $avgRating = (float) (Feedback::avg('rating') ?? 0);

        return view('admin.feedback.index', compact('feedbacks', 'ratingFilter', 'totalCount', 'avgRating'));
    }

    public function destroy($id)
    {
        Feedback::findOrFail($id)->delete();
        return back()->with('success', 'Feedback entry deleted.');
    }
}
