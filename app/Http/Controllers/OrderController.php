<?php
namespace App\Http\Controllers;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function confirmation(Request $request)
    {
        $orderNumber = $request->get('order') ?? $request->input('order');
        if (!$orderNumber) return redirect()->route('home');
        $order = Order::where('order_number', $orderNumber)->with('items')->first();
        if (!$order) abort(404);
        // ensure owner
        if ($request->session()->get('user_id') != $order->user_id && !$request->session()->has('admin_id')) {
            abort(403);
        }
        return view('orders.confirmation', compact('order'));
    }

    public function tracking(Request $request)
    {
        if (!$request->session()->has('user_id')) return redirect()->route('login');
        $userId = $request->session()->get('user_id');
        $orders = Order::where('user_id', $userId)->orderByDesc('created_at')->with('items')->get();
        $query = $request->get('order');
        $single = null;
        if ($query) {
            $single = Order::where('order_number', $query)->where('user_id', $userId)->with('items')->first();
        }
        return view('orders.tracking', compact('orders','single'));
    }

    public function cancel(Request $request, $id)
    {
        $userId = $request->session()->get('user_id');
        $order = Order::where('id',$id)->where('user_id',$userId)->firstOrFail();
        // Cancel allowed only while order is in Active status, not once Preparing
        if ($order->status !== 'active') {
            return back()->with('error','Cannot cancel this order – it is already being prepared or processed.');
        }
        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->status = 'cancelled';
        $order->cancellation_reason = $request->input('reason');
        $order->cancellation_notes = $request->input('notes');
        $order->cancelled_at = now();
        $order->save();
        return back()->with('success','Order cancelled');
    }
}
