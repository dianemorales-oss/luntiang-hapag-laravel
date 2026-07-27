<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class ReportController extends Controller
{
    public function index()
    {
        $totalSales = Order::sum('total');
        $totalOrders = Order::count();
        $topProducts = \DB::table('order_items')
            ->select('product_name', \DB::raw('SUM(quantity) as total_qty'), \DB::raw('SUM(price*quantity) as total_sales'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();
        return view('admin.reports.index', compact('totalSales','totalOrders','topProducts'));
    }
}
