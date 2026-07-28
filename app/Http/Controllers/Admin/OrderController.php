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
        $search = trim($request->get('q', ''));

        $query = Order::query();
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%$search%")
                  ->orWhere('customer_name', 'like', "%$search%")
                  ->orWhere('customer_email', 'like', "%$search%")
                  ->orWhere('customer_phone', 'like', "%$search%");
            });
        }

        $allOrders = $query->orderByDesc('created_at')->with('items')->limit(100)->get();

        // Admin KPI counts
        $todayOrders = Order::whereDate('created_at', now()->toDateString())->count();
        
        $todayRevenue = Order::whereDate('created_at', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->sum('total') ?: 0;

        $activeCount = Order::where('status', 'active')->count();
        $preparingCount = Order::where('status', 'preparing')->count();
        $readyCount = Order::where('status', 'ready')->count();

        return view('admin.orders.index', compact(
            'allOrders', 'filter', 'search', 'todayOrders', 'todayRevenue', 'activeCount', 'preparingCount', 'readyCount'
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $status = $request->input('status');
        $allowed = ['active','preparing','harvesting','packing','ready','out_for_delivery','delivered','completed','cancelled'];
        if (!in_array($status, $allowed, true)) return back()->with('error','Invalid status');
        if ($order->status === $status) return back()->with('success', 'Order status is already up to date.');
        $order->status = $status;
        // Analytics groups completed sales by the actual status-update time.
        $order->updated_at = now();
        $order->save();

        $labels = [
            'active'=>'Active',
            'preparing'=>'Preparing for Harvest',
            'harvesting'=>'Harvesting',
            'packing'=>'Packing',
            'ready'=>'Ready for Delivery',
            'out_for_delivery'=>'Out for Delivery',
            'delivered'=>'Delivered/Picked Up',
            'completed'=>'Completed',
            'cancelled'=>'Cancelled'
        ];

        NotificationHelper::create('order_status', $order->id, 'Order status updated', "Order {$order->order_number} is now " . ($labels[$status] ?? $status), $order->customer_name);

        // Customer real-time notification
        try {
            \App\Helpers\CustomerNotificationHelper::orderStatusChanged($order->user_id, $order->id, $order->order_number, $status);
        } catch (\Exception $e) {}

        return back()->with('success', 'Order updated to: ' . ($labels[$status] ?? $status));
    }
}
