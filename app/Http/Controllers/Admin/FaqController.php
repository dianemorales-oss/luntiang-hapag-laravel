<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $editId = (int) $request->query('edit', 0);
        $editFaq = $editId > 0 ? Faq::find($editId) : null;

        $faqs = Faq::orderByDesc('created_at')->get();
        $totalFaqs = $faqs->count();

        $defaultCategories = [
            'General',
            'Products',
            'Orders',
            'Delivery',
            'Returns',
            'Care',
            'Freshness',
            'Quality',
            'Payment',
            'Account',
            'Technical Support',
        ];

        $existingCategories = Faq::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values()
            ->all();

        $categories = collect($defaultCategories)
            ->merge($existingCategories)
            ->unique()
            ->values()
            ->all();

        return view('admin.faqs.index', compact('faqs', 'totalFaqs', 'categories', 'editFaq'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['category'] = trim($validated['category'] ?? '') ?: 'General';

        Faq::create($validated);

        return redirect()->route('admin.faqs.index')->with('success', 'New FAQ added successfully.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['category'] = trim($validated['category'] ?? '') ?: 'General';

        $faq = Faq::findOrFail($id);
        $faq->update($validated);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated successfully.');
    }

    public function destroy($id)
    {
        Faq::findOrFail($id)->delete();

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ deleted.');
    }
}
