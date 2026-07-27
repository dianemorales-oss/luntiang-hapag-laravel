<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\Faq;
use App\Models\Promotion;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        if (Admin::count() === 0) {
            Admin::create([
                'name' => 'Luntiang H.A.P.A.G. Admin',
                'email' => 'admin@luntianghapag.com',
                'password' => Hash::make('Admin@123'),
                'role' => 'Super Admin',
            ]);
        }

        // Categories
        if (Category::count() === 0) {
            $cats = [
                ['Green Lettuce','green-lettuce','Crisp, classic green hydroponic lettuce varieties',1],
                ['Red Lettuce','red-lettuce','Vibrant red-tipped and burgundy lettuce varieties',2],
                ['Whole Lettuce','whole-lettuce','Single head lettuce cups — harvest on demand',3],
                ['Twin Packs','twin-packs','Two cups of the same variety — perfect for couples',4],
                ['Family Packs','family-packs','Four cups — ideal for family meals',5],
                ['Salad Mix Bundles','salad-mix-bundles','Pre-mixed varieties with dressings and extras',6],
                ['Wholesale','wholesale','Bulk packs for restaurants, events, and resellers',7],
                ['Best Sellers','best-sellers','Our most popular lettuce varieties and bundles',8],
            ];
            foreach ($cats as $c) {
                Category::create(['name'=>$c[0],'slug'=>$c[1],'description'=>$c[2],'sort_order'=>$c[3],'is_active'=>true]);
            }
        }

        // Products - if empty, seed from catalog
        if (Product::count() === 0) {
            $products = [
                [1,'Romaine Lettuce','romaine-lettuce','Giulia NH & Grizzari NZ','Tall, crisp dark green leaves — the essential Caesar salad green.',45.00,'per cup','images/lettuce/romaine-lettuce.png',17,'Caesar salad, sandwiches, wraps', '5-7 days refrigerated','1-3 hours after order',150,1],
                [1,'Batavia Lettuce','batavia-lettuce','Graction NZ, Rijk Zwaan','Broad, crunchy leaves with excellent texture.',40.00,'per cup','images/lettuce/batavia-lettuce.png',15,'Sandwiches, burgers, wraps', '5-7 days refrigerated','1-3 hours after order',110,1],
                [1,'Bianca Lettuce','bianca-lettuce','Butterhead, NH','Smooth, pliable leaves with mild sweet flavor.',45.00,'per cup','images/lettuce/bianca-lettuce.png',13,'Lettuce wraps, delicate salads','5-7 days refrigerated','1-3 hours after order',80,1],
                [1,'Dabi Lettuce','dabi-lettuce','Lollo Bionda, Frizz Zakken','Frilly, crinkled bright green leaves.',40.00,'per cup','images/lettuce/dabi-lettuce.png',14,'Garnishes, mixed salads','5-7 days refrigerated','1-3 hours after order',70,0],
                [2,'Red Lettuce','red-lettuce','Lollo Rossa','Vibrant red-tipped leaves with nutty flavor.',42.00,'per cup','images/lettuce/red-lettuce.png',16,'Gourmet salads','5-7 days refrigerated','1-3 hours after order',60,0],
                [1,'Estrosa Lettuce','estrosa-lettuce','Lollo Bionda, Frizz Zakken','Intense green, firm crinkled leaves.',38.00,'per cup','images/lettuce/estrosa-lettuce.png',12,'Artisan salads','5-7 days refrigerated','1-3 hours after order',50,0],
                [1,'Olmetie Lettuce','olmetie-lettuce','Batavia, Rijk Zwaan','Premium Batavia cultivar loved by chefs.',48.00,'per cup','images/lettuce/olmetie-lettuce.png',15,'Chef-preferred salads','5-7 days refrigerated','1-3 hours after order',40,0],
                [3,'Mixed Greens Cup','mixed-greens','Butterhead + Lollo Rossa + Romaine','Instant colorful salads.',60.00,'per cup','images/lettuce/mixed-greens.png',18,'Instant salads','Best consumed immediately','1-3 hours after order',90,0],
                [3,'Garden Salad Mix','garden-salad-mix','Batavia + Estrosa + Red Leaf','Restaurant-style garden salads.',65.00,'per cup','images/lettuce/garden-salad.png',20,'Garden salads','Best consumed immediately','1-3 hours after order',85,1],
                [6,'Family Bundle (4 cups + Dressing)','family-bundle','Assorted Cups + House Dressing','Family dinner, 4 servings.',180.00,'per bundle','images/lettuce/family-bundle.png',75,'Family dinner','5-7 days refrigerated','1-3 hours after order',30,0],
                [6,'Weekend Bundle (6 cups + Dressing + Wrap Kit)','weekend-bundle','Assorted Cups + Dressing + Wrap Kit','Weekend gatherings.',260.00,'per bundle','images/lettuce/weekend-bundle.png',110,'Weekend gatherings','5-7 days refrigerated','1-3 hours after order',25,1],
                [7,'Wholesale Tray (20 cups)','wholesale-tray','Assorted Varieties, Bulk Pack','Resellers, canteens.',300.00,'per tray','images/lettuce/wholesale-tray.png',300,'Bulk','5-7 days refrigerated','2-4 hours after order',20,0],
            ];
            foreach ($products as $p) {
                Product::create([
                    'category_id'=>$p[0],
                    'name'=>$p[1],
                    'slug'=>$p[2],
                    'variety'=>$p[3],
                    'description'=>$p[4],
                    'price'=>$p[5],
                    'unit'=>$p[6],
                    'image'=>$p[7],
                    'calories'=>$p[8],
                    'best_for'=>$p[9],
                    'shelf_life'=>$p[10],
                    'harvest_time'=>$p[11],
                    'plants_available'=>$p[12],
                    'is_best_seller'=>$p[13],
                    'is_active'=>true,
                ]);
            }
        }


        // Five-cup bundle products. These are idempotent so they are added
        // both on fresh installs and on existing databases when db:seed runs.
        $bundleCategory = Category::firstOrCreate(
            ['slug' => 'salad-mix-bundles'],
            [
                'name' => 'Salad Mix Bundles',
                'description' => 'Pre-mixed varieties with dressings and extras',
                'sort_order' => 6,
                'is_active' => true,
            ]
        );

        $greenCategory = Category::firstOrCreate(
            ['slug' => 'green-lettuce'],
            [
                'name' => 'Green Lettuce',
                'description' => 'Crisp, classic green hydroponic lettuce varieties',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $fiveCupBundles = [
            [
                'slug' => 'romaine-5-cup-bundle',
                'source_slug' => 'romaine-lettuce',
                'name' => 'Romaine 5-Cup Bundle',
                'variety' => 'Giulia NH & Grizzari NZ · 5 cups',
                'description' => 'Five cups of crisp Romaine lettuce, harvested on demand and packed fresh for family meals, salads, wraps, and Caesar salad prep.',
                'price' => 225.00,
                'image' => 'images/lettuce/5-romaine-lettuce.jpg',
                'calories' => 85,
                'best_for' => 'Family Caesar salads, wraps, sandwiches, meal prep',
            ],
            [
                'slug' => 'batavia-5-cup-bundle',
                'source_slug' => 'batavia-lettuce',
                'name' => 'Batavia 5-Cup Bundle',
                'variety' => 'Graction NZ, Rijk Zwaan · 5 cups',
                'description' => 'Five cups of crunchy Batavia lettuce with broad leaves that hold up beautifully in burgers, sandwiches, wraps, and everyday salads.',
                'price' => 200.00,
                'image' => 'images/lettuce/5-batavia-lettuce.png',
                'calories' => 75,
                'best_for' => 'Burgers, sandwiches, wraps, everyday salads',
            ],
            [
                'slug' => 'bianca-5-cup-bundle',
                'source_slug' => 'bianca-lettuce',
                'name' => 'Bianca 5-Cup Bundle',
                'variety' => 'Butterhead, NH · 5 cups',
                'description' => 'Five cups of smooth, tender Bianca lettuce with a mild sweet flavor, ideal for lettuce wraps and delicate fresh salads.',
                'price' => 225.00,
                'image' => 'images/lettuce/5-bianca-lettuce.jpg',
                'calories' => 65,
                'best_for' => 'Lettuce wraps, delicate salads, garnish',
            ],
            [
                'slug' => 'dabi-5-cup-bundle',
                'source_slug' => 'dabi-lettuce',
                'name' => 'Dabi 5-Cup Bundle',
                'variety' => 'Lollo Bionda, Frizz Zakken · 5 cups',
                'description' => 'Five cups of bright green Dabi lettuce with frilly, crinkled leaves that add volume, texture, and freshness to any plate.',
                'price' => 200.00,
                'image' => 'images/lettuce/5-dabi-lettuce.jpg',
                'calories' => 70,
                'best_for' => 'Garnishes, mixed salads, elegant plating',
            ],
            [
                'slug' => 'estrosa-5-cup-bundle',
                'source_slug' => 'estrosa-lettuce',
                'name' => 'Estrosa 5-Cup Bundle',
                'variety' => 'Lollo Bionda, Frizz Zakken · 5 cups',
                'description' => 'Five cups of intense green Estrosa lettuce with firm crinkled leaves, harvested fresh for artisan salads and restaurant-style garnish.',
                'price' => 190.00,
                'image' => 'images/lettuce/5-estrosa-lettuce.jpg',
                'calories' => 60,
                'best_for' => 'Artisan salads, restaurant garnishes, fresh side salads',
            ],
            [
                'slug' => 'olmetie-5-cup-bundle',
                'source_slug' => 'olmetie-lettuce',
                'name' => 'Olmetie 5-Cup Bundle',
                'variety' => 'Premium Batavia, Rijk Zwaan · 5 cups',
                'description' => 'Five cups of premium Olmetie lettuce loved by chefs for its crisp bite, deep flavor, and beautiful presentation.',
                'price' => 240.00,
                'image' => 'images/lettuce/5-olmetie-lettuce.jpg',
                'calories' => 75,
                'best_for' => 'Chef-preferred salads, fine dining, premium plating',
            ],
            [
                'slug' => 'red-lettuce-5-cup-bundle',
                'source_slug' => 'red-lettuce',
                'name' => 'Red Lettuce 5-Cup Bundle',
                'variety' => 'Lollo Rossa · 5 cups',
                'description' => 'Five cups of vibrant red-tipped lettuce with a nutty flavor and antioxidant-rich color for gourmet salads and colorful plating.',
                'price' => 210.00,
                'image' => 'images/lettuce/5-red-lettuce.jpg',
                'calories' => 80,
                'best_for' => 'Gourmet salads, colorful plating, antioxidant boost',
            ],
            [
                'slug' => 'lalique-5-cup-bundle',
                'source_slug' => null,
                'name' => 'Lalique 5-Cup Bundle',
                'variety' => 'Lalique Lettuce · 5 cups',
                'description' => 'Five cups of fresh Lalique lettuce with bright green, ruffled leaves packed in individual cups for salads, wraps, and fresh garnish.',
                'price' => 225.00,
                'image' => 'images/lettuce/5-lalique-lettuce.jpg',
                'calories' => 75,
                'best_for' => 'Fresh salads, wraps, garnish, family meals',
            ],
        ];

        foreach ($fiveCupBundles as $bundle) {
            $sourceProduct = $bundle['source_slug']
                ? Product::where('slug', $bundle['source_slug'])->first()
                : null;
            $bundleAvailability = $sourceProduct
                ? (int) $sourceProduct->plants_available
                : 50;

            $bundleData = [
                'category_id' => $bundleCategory->id,
                'name' => $bundle['name'],
                'variety' => $bundle['variety'],
                'description' => $bundle['description'],
                'price' => $bundle['price'],
                'unit' => 'per 5 cups',
                'image' => $bundle['image'],
                'calories' => $bundle['calories'],
                'best_for' => $bundle['best_for'],
                'shelf_life' => '5-7 days refrigerated',
                'harvest_time' => '1-3 hours after order',
                'plants_available' => $bundleAvailability,
                'is_best_seller' => false,
                'is_new' => true,
                'is_active' => true,
                'is_featured' => true,
            ];

            if (Schema::hasColumn('products', 'stock_product_id')) {
                $bundleData['stock_product_id'] = $sourceProduct?->id;
            }
            if (Schema::hasColumn('products', 'stock_multiplier')) {
                $bundleData['stock_multiplier'] = 5;
            }

            Product::updateOrCreate(
                ['slug' => $bundle['slug']],
                $bundleData
            );
        }


        // Fifty-cup wholesale products. Each purchased unit deducts 50 from
        // its own inventory row and, when available, from the matching base lettuce row.
        $wholesaleCategory = Category::firstOrCreate(
            ['slug' => 'wholesale'],
            [
                'name' => 'Wholesale',
                'description' => 'Bulk packs for restaurants, events, and resellers',
                'sort_order' => 7,
                'is_active' => true,
            ]
        );

        $fiftyCupWholesale = [
            [
                'slug' => 'romaine-50-cup-wholesale',
                'source_slug' => 'romaine-lettuce',
                'name' => 'Romaine 50-Cup Wholesale Sack',
                'variety' => 'Giulia NH & Grizzari NZ · 50 cups',
                'description' => 'A 50-cup wholesale sack of crisp Romaine lettuce for restaurants, events, meal prep businesses, and resellers.',
                'price' => 1850.00,
                'image' => 'images/lettuce/50-romaine-lettuce.png',
                'calories' => 850,
                'best_for' => 'Restaurants, events, Caesar salad prep, wraps, resale',
            ],
            [
                'slug' => 'batavia-50-cup-wholesale',
                'source_slug' => 'batavia-lettuce',
                'name' => 'Batavia 50-Cup Wholesale Sack',
                'variety' => 'Graction NZ, Rijk Zwaan · 50 cups',
                'description' => 'A 50-cup wholesale sack of crunchy Batavia lettuce with broad leaves for burgers, sandwiches, wraps, and food-service use.',
                'price' => 1600.00,
                'image' => 'images/lettuce/50-batavia-lettuce.png',
                'calories' => 750,
                'best_for' => 'Food stalls, burgers, sandwiches, wraps, resale',
            ],
            [
                'slug' => 'bianca-50-cup-wholesale',
                'source_slug' => 'bianca-lettuce',
                'name' => 'Bianca 50-Cup Wholesale Sack',
                'variety' => 'Butterhead, NH · 50 cups',
                'description' => 'A 50-cup wholesale sack of tender Bianca lettuce with a mild sweet flavor for wraps, delicate salads, and catering.',
                'price' => 1850.00,
                'image' => 'images/lettuce/50-bianca-lettuce.png',
                'calories' => 650,
                'best_for' => 'Catering, lettuce wraps, delicate salads, premium garnish',
            ],
            [
                'slug' => 'dabi-50-cup-wholesale',
                'source_slug' => 'dabi-lettuce',
                'name' => 'Dabi 50-Cup Wholesale Sack',
                'variety' => 'Lollo Bionda, Frizz Zakken · 50 cups',
                'description' => 'A 50-cup wholesale sack of bright green Dabi lettuce with frilly, crinkled leaves for high-volume salad preparation.',
                'price' => 1600.00,
                'image' => 'images/lettuce/50-dabi-lettuce.png',
                'calories' => 700,
                'best_for' => 'Bulk salads, garnishes, catering, food-service plating',
            ],
            [
                'slug' => 'estrosa-50-cup-wholesale',
                'source_slug' => 'estrosa-lettuce',
                'name' => 'Estrosa 50-Cup Wholesale Sack',
                'variety' => 'Lollo Bionda, Frizz Zakken · 50 cups',
                'description' => 'A 50-cup wholesale sack of firm, crinkled Estrosa lettuce for restaurants, artisan salad service, and resellers.',
                'price' => 1500.00,
                'image' => 'images/lettuce/50-estrosa-lettuce.png',
                'calories' => 600,
                'best_for' => 'Restaurant garnish, artisan salads, catering, resale',
            ],
            [
                'slug' => 'olmetie-50-cup-wholesale',
                'source_slug' => 'olmetie-lettuce',
                'name' => 'Olmetie 50-Cup Wholesale Sack',
                'variety' => 'Premium Batavia, Rijk Zwaan · 50 cups',
                'description' => 'A 50-cup wholesale sack of premium Olmetie lettuce with a crisp bite and deep flavor for chef-preferred bulk orders.',
                'price' => 2000.00,
                'image' => 'images/lettuce/50-olmetie-lettuce.png',
                'calories' => 750,
                'best_for' => 'Premium restaurants, chef salads, fine dining, resale',
            ],
            [
                'slug' => 'red-lettuce-50-cup-wholesale',
                'source_slug' => 'red-lettuce',
                'name' => 'Red Lettuce 50-Cup Wholesale Sack',
                'variety' => 'Lollo Rossa · 50 cups',
                'description' => 'A 50-cup wholesale sack of red-tipped lettuce with vibrant color and nutty flavor for gourmet salads and colorful plating.',
                'price' => 1700.00,
                'image' => 'images/lettuce/50-red-lettuce.png',
                'calories' => 800,
                'best_for' => 'Gourmet salad service, colorful plating, catering, resale',
            ],
            [
                'slug' => 'lalique-50-cup-wholesale',
                'source_slug' => null,
                'name' => 'Lalique 50-Cup Wholesale Sack',
                'variety' => 'Lalique Lettuce · 50 cups',
                'description' => 'A 50-cup wholesale sack of fresh Lalique lettuce with bright green ruffled leaves for events, bulk meals, and resellers.',
                'price' => 1850.00,
                'image' => 'images/lettuce/50-lalique-lettuce.png',
                'calories' => 750,
                'best_for' => 'Wholesale salads, events, catering, resellers',
            ],
        ];

        foreach ($fiftyCupWholesale as $wholesale) {
            $sourceProduct = $wholesale['source_slug']
                ? Product::where('slug', $wholesale['source_slug'])->first()
                : null;
            $wholesaleAvailability = $sourceProduct
                ? (int) $sourceProduct->plants_available
                : 100;

            $wholesaleData = [
                'category_id' => $wholesaleCategory->id,
                'name' => $wholesale['name'],
                'variety' => $wholesale['variety'],
                'description' => $wholesale['description'],
                'price' => $wholesale['price'],
                'unit' => 'per 50 cups',
                'image' => $wholesale['image'],
                'calories' => $wholesale['calories'],
                'best_for' => $wholesale['best_for'],
                'shelf_life' => '5-7 days refrigerated',
                'harvest_time' => '2-4 hours after order',
                'plants_available' => $wholesaleAvailability,
                'is_best_seller' => false,
                'is_new' => true,
                'is_active' => true,
                'is_featured' => true,
            ];

            if (Schema::hasColumn('products', 'stock_product_id')) {
                $wholesaleData['stock_product_id'] = $sourceProduct?->id;
            }
            if (Schema::hasColumn('products', 'stock_multiplier')) {
                $wholesaleData['stock_multiplier'] = 50;
            }

            Product::updateOrCreate(
                ['slug' => $wholesale['slug']],
                $wholesaleData
            );
        }

        // FAQs
        if (Faq::count() === 0) {
            $faqs = [
                ['What is harvest-on-demand?','Our lettuce stays growing until you order. We harvest within 1-3 hours after confirmation, ensuring maximum freshness.','General'],
                ['How long does lettuce stay fresh?','5-7 days refrigerated. Mixed greens are best consumed immediately when refrigerated.','Freshness'],
                ['Do you offer delivery?','Yes! FREE delivery within Nostalji Subdivision. P50 fee for outside areas. Same-day delivery for orders before 2 PM.','Delivery'],
                ['What payment methods?','We accept COD, GCash, Maya, and Bank Transfer.','Payment'],
                ['How do I submit a support ticket?','Log into your account, go to Submit a Ticket page, fill in subject, category, description, and attach photos if needed.','Technical Support'],
                ['How do I submit a freshness guarantee request?','Go to Return & Refund, provide order number, product name, and photos of the issue. Our team reviews within 1-2 business days.','Freshness'],
                ['How do I request a return or refund?','Log in, go to Return Request, provide order number, product name, delivery date, reason, and photos.','Returns'],
                ['How do I create an account?','Go to Register page, enter full name, email, phone, and password. You\'ll be logged in automatically.','Account'],
                ['What does the freshness guarantee cover?','Wilted or damaged lettuce, wrong varieties, missing items, and quality below standards. Submit request with photos within 24 hours for free replacement or refund.','Freshness'],
                ['How does delivery work in my area?','FREE delivery within Nostalji Subdivision. Outside areas have delivery fee calculated. Same-day delivery before 2 PM. Pick-up ready 1-3 hours after confirmation.','Delivery'],
            ];
            foreach ($faqs as $f) {
                Faq::create(['question'=>$f[0],'answer'=>$f[1],'category'=>$f[2]]);
            }
        }

        // Promotions
        if (Promotion::count() === 0) {
            Promotion::create([
                'code'=>'FRESH10',
                'description'=>'10% off on first order',
                'discount_type'=>'percentage',
                'discount_value'=>10,
                'min_order'=>200,
                'is_active'=>true,
                'expires_at'=>now()->addMonths(2)->toDateString(),
            ]);
            Promotion::create([
                'code'=>'FREESHIP',
                'description'=>'Free delivery',
                'discount_type'=>'fixed',
                'discount_value'=>0,
                'is_free_delivery'=>true,
                'is_active'=>true,
                'expires_at'=>now()->addMonth()->toDateString(),
            ]);
        }
    }
}
