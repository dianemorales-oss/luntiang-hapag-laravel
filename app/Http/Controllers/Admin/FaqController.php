<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('category')->orderBy('created_at')->get();
        return view('admin.faqs.index', compact('faqs'));
    }

    public function store(Request $request)
    {
        $request->validate(['question'=>'required','answer'=>'required']);
        Faq::create($request->only(['question','answer','category']));
        return back()->with('success','FAQ added');
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);
        $faq->update($request->only(['question','answer','category']));
        return back()->with('success','FAQ updated');
    }

    public function destroy($id)
    {
        Faq::findOrFail($id)->delete();
        return back()->with('success','Deleted');
    }
}
