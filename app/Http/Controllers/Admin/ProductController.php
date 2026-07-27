<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $editId = (int)$request->get('edit', 0);
        $editProduct = $editId ? Product::find($editId) : null;

        // Retrieve sorted products
        $products = Product::with('category')
            ->orderByDesc('is_active')
            ->orderByDesc('is_best_seller')
            ->orderBy('name')
            ->get();

        $categories = Category::where('is_active', 1)->orderBy('sort_order')->get();

        // Product Statistics
        $plantsAvailableSum = Product::where('is_active', 1)->sum('plants_available');
        $lowAvailability = Product::where('is_active', 1)->where('plants_available', '<=', 20)->where('plants_available', '>', 0)->count();
        $outOfStock = Product::where('is_active', 1)->where('plants_available', 0)->count();
        $activeCount = Product::where('is_active', 1)->count();

        return view('admin.products.index', compact(
            'products', 'categories', 'editProduct', 
            'plantsAvailableSum', 'lowAvailability', 'outOfStock', 'activeCount'
        ));
    }

    public function store(Request $request)
    {
        // Supporting both store (add product) and edit/toggle action triggers via index POST
        $action = $request->input('action', 'create');
        $id = (int)$request->input('id', 0);

        if ($action === 'update' && $id) {
            $product = Product::findOrFail($id);
            $product->name = $request->input('name');
            $product->variety = $request->input('variety');
            $product->description = $request->input('description');
            $product->price = (float)$request->input('price');
            $product->unit = $request->input('unit', 'per cup');
            $product->plants_available = (int)$request->input('plants_available', 0);
            $product->is_best_seller = $request->has('is_best_seller');
            $product->is_new = $request->has('is_new');
            $product->is_active = $request->has('is_active');
            $product->calories = $request->input('calories') ? (int)$request->input('calories') : null;
            $product->best_for = $request->input('best_for');
            $product->shelf_life = $request->input('shelf_life');
            $product->harvest_time = $request->input('harvest_time', '1-3 hours after order');
            $product->storage_instructions = $request->input('storage_instructions');
            $product->save();

            return redirect()->route('admin.products.index')->with('success', 'Product updated.');
        }

        if ($action === 'toggle' && $id) {
            $product = Product::findOrFail($id);
            $product->is_active = !$product->is_active;
            $product->save();

            return redirect()->route('admin.products.index')->with('success', 'Product status toggled.');
        }

        // Standard Add Product
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
            'image'=>$imagePath,
            'plants_available'=>$request->input('plants_available',0),
            'is_best_seller'=>$request->has('is_best_seller'),
            'is_new'=>$request->has('is_new'),
            'is_active'=>true,
        ]);

        return redirect()->route('admin.products.index')->with('success','Product created.');
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
