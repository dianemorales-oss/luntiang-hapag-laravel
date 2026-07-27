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

        // Daily sales for chart: 7 days starting from selected date
        $chartStart = date('Y-m-d', strtotime('-6 days', $ts));
        $chartEnd = $selectedDate;
        $dailyData = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COALESCE(SUM(total),0) as rev'), DB::raw('COUNT(*) as cnt'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$chartStart, $chartEnd])
            ->where('status', '!=', 'cancelled')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('d')
            ->get();

        return view('admin.reports.index', compact(
            'selectedDate', 'ts', 'weekStart', 'weekEnd', 'daySales', 'dayOrders',
            'weekSales', 'monthSales', 'totalOrders', 'avgOrder', 'deliveryCount',
            'pickupCount', 'newCust', 'totalCust', 'bestSellers', 'chartStart', 'chartEnd', 'dailyData'
        ));
    }
}
