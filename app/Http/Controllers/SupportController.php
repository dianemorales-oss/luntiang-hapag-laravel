<?php
namespace App\Http\Controllers;
use App\Models\Faq;
use App\Models\Feedback;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function faq()
    {
        $faqs = Faq::orderBy('category')->orderBy('created_at')->get()->groupBy('category');
        return view('support.faq', compact('faqs'));
    }

    public function about()
    {
        return view('support.about');
    }
    public function privacy()
    {
        return view('support.privacy');
    }
    public function terms()
    {
        return view('support.terms');
    }
    public function contact()
    {
        return view('support.contact');
    }
    public function contactStore(Request $request)
    {
        $request->validate([
            'subject'=>'required',
            'message'=>'required',
            'rating'=>'required|integer|min:1|max:5',
        ]);

        $userId = $request->session()->get('user_id');
        Feedback::create([
            'user_id' => $userId,
            'guest_name' => $request->input('name'),
            'guest_email' => $request->input('email'),
            'subject' => $request->input('subject'),
            'rating' => $request->input('rating'),
            'comments' => $request->input('message'),
        ]);

        return back()->with('success','Thank you for your feedback!');
    }

    public function feedback()
    {
        return view('support.feedback');
    }

    public function feedbackStore(Request $request)
    {
        if (!$request->session()->has('user_id')) return redirect()->route('login');
        $request->validate([
            'rating'=>'required|integer|min:1|max:5',
        ]);
        Feedback::create([
            'user_id' => $request->session()->get('user_id'),
            'rating' => $request->input('rating'),
            'comments' => $request->input('comments'),
        ]);
        return back()->with('success','Feedback submitted!');
    }
}
