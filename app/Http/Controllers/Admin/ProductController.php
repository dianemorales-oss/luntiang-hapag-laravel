<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->orderByDesc('created_at')->get();
        $categories = Category::all();
        return view('admin.products.index', compact('products','categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'price'=>'required|numeric',
        ]);

        $slug = Str::slug($request->input('name')) . '-' . time();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $f = $request->file('image');
            $dest = public_path('images/lettuce');
            if (!is_dir($dest)) mkdir($dest,0755,true);
            $name = Str::slug($request->input('name')) . '.' . $f->getClientOriginalExtension();
            $f->move($dest, $name);
            $imagePath = 'images/lettuce/' . $name;
        }

        Product::create([
            'category_id'=>$request->input('category_id'),
            'name'=>$request->input('name'),
            'slug'=>$slug,
            'variety'=>$request->input('variety'),
            'description'=>$request->input('description'),
            'price'=>$request->input('price'),
            'unit'=>$request->input('unit','per cup'),
            'image'=>$imagePath ?? $request->input('image'),
            'plants_available'=>$request->input('plants_available',0),
            'is_best_seller'=>$request->has('is_best_seller'),
            'is_active'=>true,
        ]);

        return back()->with('success','Product created');
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->name = $request->input('name', $product->name);
        $product->price = $request->input('price', $product->price);
        $product->variety = $request->input('variety', $product->variety);
        $product->plants_available = $request->input('plants_available', $product->plants_available);
        $product->is_best_seller = $request->has('is_best_seller');
        $product->is_active = $request->has('is_active');
        $product->category_id = $request->input('category_id', $product->category_id);
        $product->save();
        return back()->with('success','Product updated');
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return back()->with('success','Deleted');
    }
}
