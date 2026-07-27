<?php
namespace App\Http\Controllers;
use App\Models\Faq;
use App\Models\Feedback;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function faq()
    {
        // Exclude Account category per requirements
        $faqs = Faq::whereRaw('LOWER(category) != ?', ['account'])
            ->orderByDesc('created_at')
            ->get();
        
        $categories = [];
        foreach ($faqs as $f) {
            $cat = $f->category ?: 'General';
            if (strtolower($cat) === 'account') continue;
            $slug = strtolower($cat);
            if (!isset($categories[$slug])) {
                $categories[$slug] = $cat;
            }
        }
        if (empty($categories)) {
            $categories = ['general' => 'General'];
        }

        return view('support.faq', compact('faqs', 'categories'));
    }

    public function about()
    {
        try {
            $customerCount = \App\Models\User::count();
            $totalOrders = \App\Models\Order::where('status','!=','cancelled')->count();
            $lettuceVarieties = \App\Models\Product::where('is_active',1)->count();
        } catch (\Exception $e) {
            $customerCount = 0;
            $totalOrders = 0;
            $lettuceVarieties = 8;
        }
        return view('support.about', compact('customerCount','totalOrders','lettuceVarieties'));
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
