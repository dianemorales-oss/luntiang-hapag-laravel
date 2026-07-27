<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $query = Order::query();
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $allOrders = $query->orderByDesc('created_at')->with('items')->limit(50)->get();

        // Admin KPI counts
        $todayOrders = Order::whereDate('created_at', now()->toDateString())->count();
        
        $todayRevenue = Order::whereDate('created_at', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->sum('total') ?: 0;

        $preparingCount = Order::where('status', 'preparing')->count();
        $readyCount = Order::where('status', 'ready')->count();

        return view('admin.orders.index', compact(
            'allOrders', 'filter', 'todayOrders', 'todayRevenue', 'preparingCount', 'readyCount'
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $status = $request->input('status');
        $allowed = ['preparing','ready','delivered','completed','cancelled'];
        if (!in_array($status, $allowed)) return back()->with('error','Invalid status');
        $order->status = $status;
        $order->save();

        $labels = [
            'preparing'=>'Preparing Order',
            'ready'=>'Ready',
            'delivered'=>'Delivered/Picked Up',
            'completed'=>'Completed',
            'cancelled'=>'Cancelled'
        ];

        NotificationHelper::create('order_status', $order->id, 'Order status updated', "Order {$order->order_number} is now " . ($labels[$status] ?? $status), $order->customer_name);

        return back()->with('success', 'Order updated to: ' . ($labels[$status] ?? $status));
    }
}
