<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('categories') || !Schema::hasTable('products')) {
            return;
        }

        $this->category('salad-mix-bundles', 'Salad Mix Bundles', 6);
        $this->category('wholesale', 'Wholesale', 7);
        $this->category('green-lettuce', 'Green Lettuce', 1);
        $this->category('red-lettuce', 'Red Lettuce', 2);

        // Make current products visible on the homepage even on existing databases.
        if (Schema::hasColumn('products', 'is_featured')) {
            DB::table('products')->where('is_active', 1)->update(['is_featured' => 1]);
        }

        foreach ($this->products() as $product) {
            $this->upsertProduct($product);
        }
    }

    private function category(string $slug, string $name, int $sortOrder): void
    {
        $data = [
            'slug' => $slug,
            'name' => $name,
            'description' => $slug === 'wholesale'
                ? 'Bulk packs for restaurants, events, and resellers'
                : ($slug === 'salad-mix-bundles'
                    ? 'Pre-mixed varieties with dressings and extras'
                    : 'Fresh hydroponic lettuce varieties'),
            'is_active' => 1,
            'sort_order' => $sortOrder,
        ];
        if (Schema::hasColumn('categories', 'updated_at')) $data['updated_at'] = now();
        if (!DB::table('categories')->where('slug', $slug)->exists() && Schema::hasColumn('categories', 'created_at')) $data['created_at'] = now();
        DB::table('categories')->updateOrInsert(['slug' => $slug], $data);
    }

    private function upsertProduct(array $p): void
    {
        $categoryId = DB::table('categories')->where('slug', $p['category_slug'])->value('id');
        if (!$categoryId) return;

        $sourceId = !empty($p['source_slug']) ? DB::table('products')->where('slug', $p['source_slug'])->value('id') : null;
        $sourceStock = $sourceId ? (int) DB::table('products')->where('id', $sourceId)->value('plants_available') : null;

        $data = [
            'category_id' => $categoryId,
            'name' => $p['name'],
            'slug' => $p['slug'],
            'variety' => $p['variety'],
            'description' => $p['description'],
            'price' => $p['price'],
            'unit' => $p['unit'],
            'image' => $p['image'],
            'calories' => $p['calories'],
            'best_for' => $p['best_for'],
            'shelf_life' => '5-7 days refrigerated',
            'harvest_time' => $p['multiplier'] >= 50 ? '2-4 hours after order' : '1-3 hours after order',
            'plants_available' => $sourceStock ?? ($p['stock'] ?? 100),
            'is_best_seller' => 0,
            'is_new' => 1,
            'is_active' => 1,
            'is_featured' => 1,
        ];

        if (Schema::hasColumn('products', 'stock_product_id')) $data['stock_product_id'] = $sourceId;
        if (Schema::hasColumn('products', 'stock_multiplier')) $data['stock_multiplier'] = $p['multiplier'];
        if (Schema::hasColumn('products', 'updated_at')) $data['updated_at'] = now();
        if (!DB::table('products')->where('slug', $p['slug'])->exists() && Schema::hasColumn('products', 'created_at')) $data['created_at'] = now();

        DB::table('products')->updateOrInsert(['slug' => $p['slug']], $data);
    }

    private function products(): array
    {
        return [
            ['category_slug'=>'salad-mix-bundles','slug'=>'romaine-5-cup-bundle','source_slug'=>'romaine-lettuce','name'=>'Romaine 5-Cup Bundle','variety'=>'Giulia NH & Grizzari NZ · 5 cups','description'=>'Five cups of crisp Romaine lettuce, harvested on demand and packed fresh.','price'=>225.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-romaine-lettuce.jpg','calories'=>85,'best_for'=>'Family Caesar salads, wraps, sandwiches, meal prep','multiplier'=>5,'stock'=>150],
            ['category_slug'=>'salad-mix-bundles','slug'=>'batavia-5-cup-bundle','source_slug'=>'batavia-lettuce','name'=>'Batavia 5-Cup Bundle','variety'=>'Graction NZ, Rijk Zwaan · 5 cups','description'=>'Five cups of crunchy Batavia lettuce for burgers, sandwiches, wraps, and salads.','price'=>200.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-batavia-lettuce.png','calories'=>75,'best_for'=>'Burgers, sandwiches, wraps, everyday salads','multiplier'=>5,'stock'=>110],
            ['category_slug'=>'salad-mix-bundles','slug'=>'bianca-5-cup-bundle','source_slug'=>'bianca-lettuce','name'=>'Bianca 5-Cup Bundle','variety'=>'Butterhead, NH · 5 cups','description'=>'Five cups of smooth, tender Bianca lettuce with mild sweet flavor.','price'=>225.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-bianca-lettuce.jpg','calories'=>65,'best_for'=>'Lettuce wraps, delicate salads, garnish','multiplier'=>5,'stock'=>80],
            ['category_slug'=>'salad-mix-bundles','slug'=>'dabi-5-cup-bundle','source_slug'=>'dabi-lettuce','name'=>'Dabi 5-Cup Bundle','variety'=>'Lollo Bionda, Frizz Zakken · 5 cups','description'=>'Five cups of bright green Dabi lettuce with frilly leaves.','price'=>200.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-dabi-lettuce.jpg','calories'=>70,'best_for'=>'Garnishes, mixed salads, elegant plating','multiplier'=>5,'stock'=>70],
            ['category_slug'=>'salad-mix-bundles','slug'=>'estrosa-5-cup-bundle','source_slug'=>'estrosa-lettuce','name'=>'Estrosa 5-Cup Bundle','variety'=>'Lollo Bionda, Frizz Zakken · 5 cups','description'=>'Five cups of intense green Estrosa lettuce with firm crinkled leaves.','price'=>190.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-estrosa-lettuce.jpg','calories'=>60,'best_for'=>'Artisan salads, restaurant garnishes','multiplier'=>5,'stock'=>50],
            ['category_slug'=>'salad-mix-bundles','slug'=>'olmetie-5-cup-bundle','source_slug'=>'olmetie-lettuce','name'=>'Olmetie 5-Cup Bundle','variety'=>'Premium Batavia, Rijk Zwaan · 5 cups','description'=>'Five cups of premium Olmetie lettuce loved by chefs.','price'=>240.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-olmetie-lettuce.jpg','calories'=>75,'best_for'=>'Chef-preferred salads, fine dining','multiplier'=>5,'stock'=>40],
            ['category_slug'=>'salad-mix-bundles','slug'=>'red-lettuce-5-cup-bundle','source_slug'=>'red-lettuce','name'=>'Red Lettuce 5-Cup Bundle','variety'=>'Lollo Rossa · 5 cups','description'=>'Five cups of red-tipped lettuce with vibrant color and nutty flavor.','price'=>210.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-red-lettuce.jpg','calories'=>80,'best_for'=>'Gourmet salads, colorful plating','multiplier'=>5,'stock'=>60],
            ['category_slug'=>'salad-mix-bundles','slug'=>'lalique-5-cup-bundle','source_slug'=>null,'name'=>'Lalique 5-Cup Bundle','variety'=>'Lalique Lettuce · 5 cups','description'=>'Five cups of fresh Lalique lettuce with bright green ruffled leaves.','price'=>225.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-lalique-lettuce.jpg','calories'=>75,'best_for'=>'Fresh salads, wraps, garnish','multiplier'=>5,'stock'=>50],

            ['category_slug'=>'wholesale','slug'=>'romaine-50-cup-wholesale','source_slug'=>'romaine-lettuce','name'=>'Romaine 50-Cup Wholesale Sack','variety'=>'Giulia NH & Grizzari NZ · 50 cups','description'=>'A 50-cup wholesale sack of crisp Romaine lettuce.','price'=>2250.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-romaine-lettuce.png','calories'=>850,'best_for'=>'Restaurants, events, resale','multiplier'=>50,'stock'=>150],
            ['category_slug'=>'wholesale','slug'=>'batavia-50-cup-wholesale','source_slug'=>'batavia-lettuce','name'=>'Batavia 50-Cup Wholesale Sack','variety'=>'Graction NZ, Rijk Zwaan · 50 cups','description'=>'A 50-cup wholesale sack of crunchy Batavia lettuce.','price'=>2000.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-batavia-lettuce.png','calories'=>750,'best_for'=>'Food stalls, burgers, resale','multiplier'=>50,'stock'=>110],
            ['category_slug'=>'wholesale','slug'=>'bianca-50-cup-wholesale','source_slug'=>'bianca-lettuce','name'=>'Bianca 50-Cup Wholesale Sack','variety'=>'Butterhead, NH · 50 cups','description'=>'A 50-cup wholesale sack of tender Bianca lettuce.','price'=>2250.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-bianca-lettuce.png','calories'=>650,'best_for'=>'Catering, lettuce wraps','multiplier'=>50,'stock'=>80],
            ['category_slug'=>'wholesale','slug'=>'dabi-50-cup-wholesale','source_slug'=>'dabi-lettuce','name'=>'Dabi 50-Cup Wholesale Sack','variety'=>'Lollo Bionda, Frizz Zakken · 50 cups','description'=>'A 50-cup wholesale sack of bright green Dabi lettuce.','price'=>2000.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-dabi-lettuce.png','calories'=>700,'best_for'=>'Bulk salads, garnishes','multiplier'=>50,'stock'=>70],
            ['category_slug'=>'wholesale','slug'=>'estrosa-50-cup-wholesale','source_slug'=>'estrosa-lettuce','name'=>'Estrosa 50-Cup Wholesale Sack','variety'=>'Lollo Bionda, Frizz Zakken · 50 cups','description'=>'A 50-cup wholesale sack of firm Estrosa lettuce.','price'=>1900.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-estrosa-lettuce.png','calories'=>600,'best_for'=>'Restaurants, artisan salads','multiplier'=>50,'stock'=>50],
            ['category_slug'=>'wholesale','slug'=>'olmetie-50-cup-wholesale','source_slug'=>'olmetie-lettuce','name'=>'Olmetie 50-Cup Wholesale Sack','variety'=>'Premium Batavia, Rijk Zwaan · 50 cups','description'=>'A 50-cup wholesale sack of premium Olmetie lettuce.','price'=>2400.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-olmetie-lettuce.png','calories'=>750,'best_for'=>'Premium restaurants, chef salads','multiplier'=>50,'stock'=>40],
            ['category_slug'=>'wholesale','slug'=>'red-lettuce-50-cup-wholesale','source_slug'=>'red-lettuce','name'=>'Red Lettuce 50-Cup Wholesale Sack','variety'=>'Lollo Rossa · 50 cups','description'=>'A 50-cup wholesale sack of red-tipped lettuce.','price'=>2100.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-red-lettuce.png','calories'=>800,'best_for'=>'Gourmet salad service, catering','multiplier'=>50,'stock'=>60],
            ['category_slug'=>'wholesale','slug'=>'lalique-50-cup-wholesale','source_slug'=>null,'name'=>'Lalique 50-Cup Wholesale Sack','variety'=>'Lalique Lettuce · 50 cups','description'=>'A 50-cup wholesale sack of fresh Lalique lettuce.','price'=>2250.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-lalique-lettuce.png','calories'=>750,'best_for'=>'Wholesale salads, events, resellers','multiplier'=>50,'stock'=>100],
        ];
    }
};
