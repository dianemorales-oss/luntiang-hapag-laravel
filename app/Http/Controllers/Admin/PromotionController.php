<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $promos = Promotion::orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.promotions.index', compact('promos'));
    }

    public function store(Request $request)
    {
        $action = $request->input('action', 'create');
        $id = (int)$request->input('id', 0);

        if ($action === 'toggle' && $id) {
            $promo = Promotion::findOrFail($id);
            $promo->is_active = !$promo->is_active;
            $promo->save();
            return redirect()->route('admin.promotions.index')->with('success', 'Promo status toggled.');
        }

        if ($action === 'delete' && $id) {
            Promotion::findOrFail($id)->delete();
            return redirect()->route('admin.promotions.index')->with('success', 'Promo deleted.');
        }

        // Standard Create
        $request->validate([
            'code' => 'required',
            'discount_value' => 'required|numeric'
        ]);

        Promotion::create([
            'code' => strtoupper(trim($request->input('code'))),
            'description' => $request->input('description'),
            'discount_type' => $request->input('discount_type', 'percentage'),
            'discount_value' => (float)$request->input('discount_value'),
            'min_order' => (float)$request->input('min_order', 0),
            'is_active' => $request->has('is_active'),
            'is_free_delivery' => $request->has('is_free_delivery'),
            'expires_at' => $request->input('expires_at')
        ]);

        return redirect()->route('admin.promotions.index')->with('success', 'Promo created successfully.');
    }

    public function destroy($id)
    {
        Promotion::findOrFail($id)->delete();
        return back()->with('success', 'Deleted');
    }
}
