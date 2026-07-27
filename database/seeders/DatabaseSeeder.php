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
                [7,'Wholesale Tray (20 cups)','wholesale-tray','Assorted Varieties, Bulk Pack','Resellers, canteens.',700.00,'per tray','images/lettuce/wholesale-tray.png',300,'Bulk','5-7 days refrigerated','2-4 hours after order',20,0],
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
