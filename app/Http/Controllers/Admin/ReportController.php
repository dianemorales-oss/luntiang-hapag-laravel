<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', now()->toDateString());
        $ts = strtotime($selectedDate);
        $selectedYear = date('Y', $ts);
        $selectedMonth = date('m', $ts);

        // Day: selected date
        $daySales = Order::whereDate('created_at', $selectedDate)
            ->where('status', '!=', 'cancelled')
            ->sum('total') ?: 0;

        $dayOrders = Order::whereDate('created_at', $selectedDate)
            ->where('status', '!=', 'cancelled')
            ->count();

        // Week: Monday-Sunday of the week containing selected date
        $weekStart = date('Y-m-d', strtotime('monday this week', $ts));
        $weekEnd = date('Y-m-d', strtotime('sunday this week', $ts));
        $weekSales = Order::whereBetween(DB::raw('DATE(created_at)'), [$weekStart, $weekEnd])
            ->where('status', '!=', 'cancelled')
            ->sum('total') ?: 0;

        // Month: selected month/year
        $monthStart = "$selectedYear-$selectedMonth-01";
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $monthSales = Order::whereBetween(DB::raw('DATE(created_at)'), [$monthStart, $monthEnd])
            ->where('status', '!=', 'cancelled')
            ->sum('total') ?: 0;

        // Total orders (all time)
        $totalOrders = Order::where('status', '!=', 'cancelled')->count();
        $avgOrder = Order::where('status', '!=', 'cancelled')->avg('total') ?: 0;

        // Delivery vs Pickup for selected month
        $deliveryCount = Order::where('delivery_method', 'delivery')
            ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart, $monthEnd])
            ->where('status', '!=', 'cancelled')
            ->count();

        $pickupCount = Order::where('delivery_method', 'pickup')
            ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart, $monthEnd])
            ->where('status', '!=', 'cancelled')
            ->count();

        // Customers
        $newCust = User::whereDate('created_at', $selectedDate)->count();
        $totalCust = User::count();

        // Best selling (all time)
        $bestSellers = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('SUM(order_items.quantity * order_items.price) as revenue'))
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Daily sales for chart: 7 days ending selected date (original)
        $chartStart = date('Y-m-d', strtotime('-6 days', $ts));
        $chartEnd = $selectedDate;
        $dailyData = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COALESCE(SUM(total),0) as rev'), DB::raw('COUNT(*) as cnt'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$chartStart, $chartEnd])
            ->where('status', '!=', 'cancelled')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('d')
            ->get();

        // Enhanced: 30-day daily revenue & orders
        $chart30Start = date('Y-m-d', strtotime('-29 days', $ts));
        $daily30Raw = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COALESCE(SUM(total),0) as rev'), DB::raw('COUNT(*) as cnt'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$chart30Start, $chartEnd])
            ->where('status', '!=', 'cancelled')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $daily30Filled = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days", $ts));
            $row = $daily30Raw[$d] ?? null;
            $daily30Filled[] = [
                'd' => $d,
                'label' => date('M j', strtotime($d)),
                'rev' => $row ? (float)$row->rev : 0,
                'cnt' => $row ? (int)$row->cnt : 0,
            ];
        }

        // 12-month monthly revenue
        $monthly12Raw = DB::table('orders')
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as ym'), DB::raw('COALESCE(SUM(total),0) as rev'), DB::raw('COUNT(*) as cnt'))
            ->where('created_at', '>=', date('Y-m-01', strtotime('-11 months', $ts)))
            ->where('status', '!=', 'cancelled')
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $monthly12Filled = [];
        for ($i = 11; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months", $ts));
            $row = $monthly12Raw[$ym] ?? null;
            $monthly12Filled[] = [
                'ym' => $ym,
                'label' => date('M Y', strtotime($ym . '-01')),
                'short' => date('M', strtotime($ym . '-01')),
                'rev' => $row ? (float)$row->rev : 0,
                'cnt' => $row ? (int)$row->cnt : 0,
            ];
        }

        // Order status breakdown (all time or selected month)
        $statusBreakdown = DB::table('orders')
            ->select('status', DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(total),0) as rev'))
            ->where('status', '!=', 'cancelled')
            ->groupBy('status')
            ->orderByDesc('cnt')
            ->get();

        // Customer growth last 30 days
        $cust30Raw = DB::table('users')
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as cnt'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$chart30Start, $chartEnd])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $cust30Filled = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days", $ts));
            $row = $cust30Raw[$d] ?? null;
            $cust30Filled[] = [
                'd' => $d,
                'label' => date('M j', strtotime($d)),
                'cnt' => $row ? (int)$row->cnt : 0,
            ];
        }

        // Category sales (if categories exist)
        try {
            $categorySales = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select('categories.name', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.quantity * order_items.price) as rev'))
                ->where('orders.status', '!=', 'cancelled')
                ->groupBy('categories.name')
                ->orderByDesc('rev')
                ->limit(6)
                ->get();
        } catch (\Exception $e) {
            $categorySales = collect();
        }

        // Fill missing daily 7 for chart consistency
        $daily7Filled = [];
        $dailyMap = $dailyData->keyBy('d');
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days", $ts));
            $row = $dailyMap[$d] ?? null;
            $daily7Filled[] = [
                'd' => $d,
                'label' => date('D M j', strtotime($d)),
                'short' => date('M j', strtotime($d)),
                'rev' => $row ? (float)$row->rev : 0,
                'cnt' => $row ? (int)$row->cnt : 0,
            ];
        }

        // Low Selling Products - lowest sales performance
        try {
            $lowSelling = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'order_items.product_name',
                    'order_items.product_id',
                    DB::raw('SUM(order_items.quantity) as total_qty'),
                    DB::raw('SUM(order_items.quantity * order_items.price) as revenue'),
                    DB::raw('COALESCE(products.plants_available, 0) as stock'),
                    DB::raw('COALESCE(products.image, "") as image')
                )
                ->where('orders.status', '!=', 'cancelled')
                ->groupBy('order_items.product_name','order_items.product_id','products.plants_available','products.image')
                ->orderBy('total_qty', 'asc')
                ->orderBy('revenue', 'asc')
                ->limit(8)
                ->get();

            // Also include products with zero sales
            $soldProductIds = DB::table('order_items')->distinct()->pluck('product_id')->toArray();
            $zeroSales = DB::table('products')
                ->whereNotIn('id', $soldProductIds)
                ->where('is_active', 1)
                ->select('name as product_name','id as product_id', DB::raw('0 as total_qty'), DB::raw('0 as revenue'), 'plants_available as stock', 'image')
                ->limit(5)
                ->get();

            $lowSelling = $lowSelling->merge($zeroSales)->sortBy('total_qty')->take(8);
        } catch (\Exception $e) {
            $lowSelling = collect();
        }

        return view('admin.reports.index', compact(
            'selectedDate', 'ts', 'weekStart', 'weekEnd', 'daySales', 'dayOrders',
            'weekSales', 'monthSales', 'totalOrders', 'avgOrder', 'deliveryCount',
            'pickupCount', 'newCust', 'totalCust', 'bestSellers', 'chartStart', 'chartEnd', 'dailyData',
            'daily7Filled', 'daily30Filled', 'monthly12Filled', 'statusBreakdown', 'cust30Filled', 'categorySales',
            'lowSelling'
        ));
    }
}
