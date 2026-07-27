<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('categories') || !Schema::hasTable('products')) {
            return;
        }

        $this->category('salad-mix-bundles', 'Salad Mix Bundles', 'Pre-mixed varieties with dressings and extras', 6);
        $this->category('wholesale', 'Wholesale', 'Bulk packs for restaurants, events, and resellers', 7);
        $this->category('green-lettuce', 'Green Lettuce', 'Crisp, classic green hydroponic lettuce varieties', 1);
        $this->category('red-lettuce', 'Red Lettuce', 'Vibrant red-tipped and burgundy lettuce varieties', 2);

        // Make sure the source/base lettuce products exist too. This helps existing
        // installs where migrations were run but db:seed was not run yet.
        foreach ($this->baseProducts() as $product) {
            $this->product($product);
        }

        foreach (array_merge($this->fiveCupBundles(), $this->fiftyCupWholesale()) as $product) {
            $sourceId = !empty($product['source_slug'])
                ? DB::table('products')->where('slug', $product['source_slug'])->value('id')
                : null;

            $sourceStock = $sourceId
                ? (int) DB::table('products')->where('id', $sourceId)->value('plants_available')
                : null;

            $product['stock_product_id'] = $sourceId;
            $product['plants_available'] = $sourceStock ?? ($product['plants_available'] ?? 100);

            $this->product($product);
        }
    }

    private function category(string $slug, string $name, string $description, int $sortOrder): void
    {
        $now = now();
        $data = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ];

        if (Schema::hasColumn('categories', 'updated_at')) {
            $data['updated_at'] = $now;
        }
        if (!DB::table('categories')->where('slug', $slug)->exists() && Schema::hasColumn('categories', 'created_at')) {
            $data['created_at'] = $now;
        }

        DB::table('categories')->updateOrInsert(['slug' => $slug], $data);
    }

    private function product(array $product): void
    {
        $now = now();
        $categoryId = DB::table('categories')->where('slug', $product['category_slug'])->value('id');
        if (!$categoryId) {
            return;
        }

        $data = [
            'category_id' => $categoryId,
            'name' => $product['name'],
            'slug' => $product['slug'],
            'variety' => $product['variety'] ?? null,
            'description' => $product['description'] ?? null,
            'price' => $product['price'],
            'unit' => $product['unit'] ?? 'per cup',
            'image' => $product['image'] ?? null,
            'calories' => $product['calories'] ?? null,
            'best_for' => $product['best_for'] ?? null,
            'shelf_life' => $product['shelf_life'] ?? '5-7 days refrigerated',
            'harvest_time' => $product['harvest_time'] ?? '1-3 hours after order',
            'plants_available' => $product['plants_available'] ?? 100,
            'is_best_seller' => $product['is_best_seller'] ?? false,
            'is_new' => $product['is_new'] ?? false,
            'is_active' => $product['is_active'] ?? true,
            'is_featured' => $product['is_featured'] ?? false,
        ];

        foreach (['image_2','image_3','protein','fiber','vitamin_a','vitamin_c','storage_instructions'] as $optionalColumn) {
            if (Schema::hasColumn('products', $optionalColumn) && array_key_exists($optionalColumn, $product)) {
                $data[$optionalColumn] = $product[$optionalColumn];
            }
        }

        if (Schema::hasColumn('products', 'stock_product_id')) {
            $data['stock_product_id'] = $product['stock_product_id'] ?? null;
        }
        if (Schema::hasColumn('products', 'stock_multiplier')) {
            $data['stock_multiplier'] = $product['stock_multiplier'] ?? 1;
        }
        if (Schema::hasColumn('products', 'updated_at')) {
            $data['updated_at'] = $now;
        }
        if (!DB::table('products')->where('slug', $product['slug'])->exists() && Schema::hasColumn('products', 'created_at')) {
            $data['created_at'] = $now;
        }

        DB::table('products')->updateOrInsert(['slug' => $product['slug']], $data);
    }

    private function baseProducts(): array
    {
        return [
            ['category_slug'=>'green-lettuce','slug'=>'romaine-lettuce','name'=>'Romaine Lettuce','variety'=>'Giulia NH & Grizzari NZ','description'=>'Tall, crisp dark green leaves — the essential Caesar salad green.','price'=>45.00,'unit'=>'per cup','image'=>'images/lettuce/romaine-lettuce.png','calories'=>17,'best_for'=>'Caesar salad, sandwiches, wraps','plants_available'=>150,'is_best_seller'=>true],
            ['category_slug'=>'green-lettuce','slug'=>'batavia-lettuce','name'=>'Batavia Lettuce','variety'=>'Graction NZ, Rijk Zwaan','description'=>'Broad, crunchy leaves with excellent texture.','price'=>40.00,'unit'=>'per cup','image'=>'images/lettuce/batavia-lettuce.png','calories'=>15,'best_for'=>'Sandwiches, burgers, wraps','plants_available'=>110,'is_best_seller'=>true],
            ['category_slug'=>'green-lettuce','slug'=>'bianca-lettuce','name'=>'Bianca Lettuce','variety'=>'Butterhead, NH','description'=>'Smooth, pliable leaves with mild sweet flavor.','price'=>45.00,'unit'=>'per cup','image'=>'images/lettuce/bianca-lettuce.png','calories'=>13,'best_for'=>'Lettuce wraps, delicate salads','plants_available'=>80,'is_best_seller'=>true],
            ['category_slug'=>'green-lettuce','slug'=>'dabi-lettuce','name'=>'Dabi Lettuce','variety'=>'Lollo Bionda, Frizz Zakken','description'=>'Frilly, crinkled bright green leaves.','price'=>40.00,'unit'=>'per cup','image'=>'images/lettuce/dabi-lettuce.png','calories'=>14,'best_for'=>'Garnishes, mixed salads','plants_available'=>70],
            ['category_slug'=>'red-lettuce','slug'=>'red-lettuce','name'=>'Red Lettuce','variety'=>'Lollo Rossa','description'=>'Vibrant red-tipped leaves with nutty flavor.','price'=>42.00,'unit'=>'per cup','image'=>'images/lettuce/red-lettuce.png','calories'=>16,'best_for'=>'Gourmet salads','plants_available'=>60],
            ['category_slug'=>'green-lettuce','slug'=>'estrosa-lettuce','name'=>'Estrosa Lettuce','variety'=>'Lollo Bionda, Frizz Zakken','description'=>'Intense green, firm crinkled leaves.','price'=>38.00,'unit'=>'per cup','image'=>'images/lettuce/estrosa-lettuce.png','calories'=>12,'best_for'=>'Artisan salads','plants_available'=>50],
            ['category_slug'=>'green-lettuce','slug'=>'olmetie-lettuce','name'=>'Olmetie Lettuce','variety'=>'Batavia, Rijk Zwaan','description'=>'Premium Batavia cultivar loved by chefs.','price'=>48.00,'unit'=>'per cup','image'=>'images/lettuce/olmetie-lettuce.png','calories'=>15,'best_for'=>'Chef-preferred salads','plants_available'=>40],
        ];
    }

    private function fiveCupBundles(): array
    {
        return [
            ['category_slug'=>'salad-mix-bundles','slug'=>'romaine-5-cup-bundle','source_slug'=>'romaine-lettuce','name'=>'Romaine 5-Cup Bundle','variety'=>'Giulia NH & Grizzari NZ · 5 cups','description'=>'Five cups of crisp Romaine lettuce, harvested on demand and packed fresh for family meals, salads, wraps, and Caesar salad prep.','price'=>225.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-romaine-lettuce.jpg','calories'=>85,'best_for'=>'Family Caesar salads, wraps, sandwiches, meal prep','stock_multiplier'=>5,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'salad-mix-bundles','slug'=>'batavia-5-cup-bundle','source_slug'=>'batavia-lettuce','name'=>'Batavia 5-Cup Bundle','variety'=>'Graction NZ, Rijk Zwaan · 5 cups','description'=>'Five cups of crunchy Batavia lettuce with broad leaves that hold up beautifully in burgers, sandwiches, wraps, and everyday salads.','price'=>200.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-batavia-lettuce.png','calories'=>75,'best_for'=>'Burgers, sandwiches, wraps, everyday salads','stock_multiplier'=>5,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'salad-mix-bundles','slug'=>'bianca-5-cup-bundle','source_slug'=>'bianca-lettuce','name'=>'Bianca 5-Cup Bundle','variety'=>'Butterhead, NH · 5 cups','description'=>'Five cups of smooth, tender Bianca lettuce with a mild sweet flavor, ideal for lettuce wraps and delicate fresh salads.','price'=>225.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-bianca-lettuce.jpg','calories'=>65,'best_for'=>'Lettuce wraps, delicate salads, garnish','stock_multiplier'=>5,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'salad-mix-bundles','slug'=>'dabi-5-cup-bundle','source_slug'=>'dabi-lettuce','name'=>'Dabi 5-Cup Bundle','variety'=>'Lollo Bionda, Frizz Zakken · 5 cups','description'=>'Five cups of bright green Dabi lettuce with frilly, crinkled leaves that add volume, texture, and freshness to any plate.','price'=>200.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-dabi-lettuce.jpg','calories'=>70,'best_for'=>'Garnishes, mixed salads, elegant plating','stock_multiplier'=>5,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'salad-mix-bundles','slug'=>'estrosa-5-cup-bundle','source_slug'=>'estrosa-lettuce','name'=>'Estrosa 5-Cup Bundle','variety'=>'Lollo Bionda, Frizz Zakken · 5 cups','description'=>'Five cups of intense green Estrosa lettuce with firm crinkled leaves, harvested fresh for artisan salads and restaurant-style garnish.','price'=>190.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-estrosa-lettuce.jpg','calories'=>60,'best_for'=>'Artisan salads, restaurant garnishes, fresh side salads','stock_multiplier'=>5,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'salad-mix-bundles','slug'=>'olmetie-5-cup-bundle','source_slug'=>'olmetie-lettuce','name'=>'Olmetie 5-Cup Bundle','variety'=>'Premium Batavia, Rijk Zwaan · 5 cups','description'=>'Five cups of premium Olmetie lettuce loved by chefs for its crisp bite, deep flavor, and beautiful presentation.','price'=>240.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-olmetie-lettuce.jpg','calories'=>75,'best_for'=>'Chef-preferred salads, fine dining, premium plating','stock_multiplier'=>5,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'salad-mix-bundles','slug'=>'red-lettuce-5-cup-bundle','source_slug'=>'red-lettuce','name'=>'Red Lettuce 5-Cup Bundle','variety'=>'Lollo Rossa · 5 cups','description'=>'Five cups of vibrant red-tipped lettuce with a nutty flavor and antioxidant-rich color for gourmet salads and colorful plating.','price'=>210.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-red-lettuce.jpg','calories'=>80,'best_for'=>'Gourmet salads, colorful plating, antioxidant boost','stock_multiplier'=>5,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'salad-mix-bundles','slug'=>'lalique-5-cup-bundle','source_slug'=>null,'name'=>'Lalique 5-Cup Bundle','variety'=>'Lalique Lettuce · 5 cups','description'=>'Five cups of fresh Lalique lettuce with bright green, ruffled leaves packed in individual cups for salads, wraps, and fresh garnish.','price'=>225.00,'unit'=>'per 5 cups','image'=>'images/lettuce/5-lalique-lettuce.jpg','calories'=>75,'best_for'=>'Fresh salads, wraps, garnish, family meals','plants_available'=>50,'stock_multiplier'=>5,'is_new'=>true,'is_featured'=>true],
        ];
    }

    private function fiftyCupWholesale(): array
    {
        return [
            ['category_slug'=>'wholesale','slug'=>'romaine-50-cup-wholesale','source_slug'=>'romaine-lettuce','name'=>'Romaine 50-Cup Wholesale Sack','variety'=>'Giulia NH & Grizzari NZ · 50 cups','description'=>'A 50-cup wholesale sack of crisp Romaine lettuce for restaurants, events, meal prep businesses, and resellers.','price'=>2250.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-romaine-lettuce.png','calories'=>850,'best_for'=>'Restaurants, events, Caesar salad prep, wraps, resale','stock_multiplier'=>50,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'wholesale','slug'=>'batavia-50-cup-wholesale','source_slug'=>'batavia-lettuce','name'=>'Batavia 50-Cup Wholesale Sack','variety'=>'Graction NZ, Rijk Zwaan · 50 cups','description'=>'A 50-cup wholesale sack of crunchy Batavia lettuce with broad leaves for burgers, sandwiches, wraps, and food-service use.','price'=>2000.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-batavia-lettuce.png','calories'=>750,'best_for'=>'Food stalls, burgers, sandwiches, wraps, resale','stock_multiplier'=>50,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'wholesale','slug'=>'bianca-50-cup-wholesale','source_slug'=>'bianca-lettuce','name'=>'Bianca 50-Cup Wholesale Sack','variety'=>'Butterhead, NH · 50 cups','description'=>'A 50-cup wholesale sack of tender Bianca lettuce with a mild sweet flavor for wraps, delicate salads, and catering.','price'=>2250.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-bianca-lettuce.png','calories'=>650,'best_for'=>'Catering, lettuce wraps, delicate salads, premium garnish','stock_multiplier'=>50,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'wholesale','slug'=>'dabi-50-cup-wholesale','source_slug'=>'dabi-lettuce','name'=>'Dabi 50-Cup Wholesale Sack','variety'=>'Lollo Bionda, Frizz Zakken · 50 cups','description'=>'A 50-cup wholesale sack of bright green Dabi lettuce with frilly, crinkled leaves for high-volume salad preparation.','price'=>2000.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-dabi-lettuce.png','calories'=>700,'best_for'=>'Bulk salads, garnishes, catering, food-service plating','stock_multiplier'=>50,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'wholesale','slug'=>'estrosa-50-cup-wholesale','source_slug'=>'estrosa-lettuce','name'=>'Estrosa 50-Cup Wholesale Sack','variety'=>'Lollo Bionda, Frizz Zakken · 50 cups','description'=>'A 50-cup wholesale sack of firm, crinkled Estrosa lettuce for restaurants, artisan salad service, and resellers.','price'=>1900.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-estrosa-lettuce.png','calories'=>600,'best_for'=>'Restaurant garnish, artisan salads, catering, resale','stock_multiplier'=>50,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'wholesale','slug'=>'olmetie-50-cup-wholesale','source_slug'=>'olmetie-lettuce','name'=>'Olmetie 50-Cup Wholesale Sack','variety'=>'Premium Batavia, Rijk Zwaan · 50 cups','description'=>'A 50-cup wholesale sack of premium Olmetie lettuce with a crisp bite and deep flavor for chef-preferred bulk orders.','price'=>2400.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-olmetie-lettuce.png','calories'=>750,'best_for'=>'Premium restaurants, chef salads, fine dining, resale','stock_multiplier'=>50,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'wholesale','slug'=>'red-lettuce-50-cup-wholesale','source_slug'=>'red-lettuce','name'=>'Red Lettuce 50-Cup Wholesale Sack','variety'=>'Lollo Rossa · 50 cups','description'=>'A 50-cup wholesale sack of red-tipped lettuce with vibrant color and nutty flavor for gourmet salads and colorful plating.','price'=>2100.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-red-lettuce.png','calories'=>800,'best_for'=>'Gourmet salad service, colorful plating, catering, resale','stock_multiplier'=>50,'is_new'=>true,'is_featured'=>true],
            ['category_slug'=>'wholesale','slug'=>'lalique-50-cup-wholesale','source_slug'=>null,'name'=>'Lalique 50-Cup Wholesale Sack','variety'=>'Lalique Lettuce · 50 cups','description'=>'A 50-cup wholesale sack of fresh Lalique lettuce with bright green ruffled leaves for events, bulk meals, and resellers.','price'=>2250.00,'unit'=>'per 50 cups','image'=>'images/lettuce/50-lalique-lettuce.png','calories'=>750,'best_for'=>'Wholesale salads, events, catering, resellers','plants_available'=>100,'stock_multiplier'=>50,'is_new'=>true,'is_featured'=>true],
        ];
    }

    public function down(): void
    {
        $slugs = array_merge(
            array_column($this->fiveCupBundles(), 'slug'),
            array_column($this->fiftyCupWholesale(), 'slug')
        );

        DB::table('products')->whereIn('slug', $slugs)->delete();
    }
};
