<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Feedback;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedbacks = Feedback::with('user')->orderByDesc('created_at')->get();
        return view('admin.feedback.index', compact('feedbacks'));
    }

    public function destroy($id)
    {
        Feedback::findOrFail($id)->delete();
        return back()->with('success','Deleted');
    }
}
