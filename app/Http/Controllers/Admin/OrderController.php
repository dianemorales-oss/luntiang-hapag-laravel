<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::orderByDesc('created_at')->with('items')->get();
        return view('admin.orders.index', compact('orders'));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $status = $request->input('status');
        $allowed = ['preparing','ready','delivered','completed','cancelled'];
        if (!in_array($status, $allowed)) return back()->with('error','Invalid status');
        $order->status = $status;
        $order->save();

        NotificationHelper::create('order_status', $order->id, 'Order status updated', "Order {$order->order_number} is now {$status}", $order->customer_name);

        return back()->with('success','Status updated');
    }
}
