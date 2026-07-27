<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::orderByDesc('created_at')->get();
        return view('admin.promotions.index', compact('promotions'));
    }

    public function store(Request $request)
    {
        $request->validate(['code'=>'required|unique:promotions,code','discount_value'=>'required|numeric']);
        Promotion::create([
            'code'=>strtoupper($request->input('code')),
            'description'=>$request->input('description'),
            'discount_type'=>$request->input('discount_type','percentage'),
            'discount_value'=>$request->input('discount_value'),
            'min_order'=>$request->input('min_order',0),
            'max_uses'=>$request->input('max_uses'),
            'is_active'=>true,
            'is_free_delivery'=>$request->has('is_free_delivery'),
            'expires_at'=>$request->input('expires_at'),
        ]);
        return back()->with('success','Promotion created');
    }

    public function update(Request $request, $id)
    {
        $promo = Promotion::findOrFail($id);
        $promo->update([
            'description'=>$request->input('description'),
            'discount_value'=>$request->input('discount_value'),
            'is_active'=>$request->has('is_active'),
            'is_free_delivery'=>$request->has('is_free_delivery'),
            'expires_at'=>$request->input('expires_at'),
        ]);
        return back()->with('success','Updated');
    }

    public function destroy($id)
    {
        Promotion::findOrFail($id)->delete();
        return back()->with('success','Deleted');
    }
}
