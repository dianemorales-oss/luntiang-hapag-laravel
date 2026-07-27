<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Ticket;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $todayRevenue = Order::whereDate('created_at', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->sum('total') ?: 0;

        $todayOrders = Order::whereDate('created_at', now()->toDateString())->count();
        $totalCust = User::count();
        $newCustToday = User::whereDate('created_at', now()->toDateString())->count();
        $openTickets = Ticket::where('status', 'open')->count();
        $pendingReturns = ReturnRequest::where('status', 'pending')->count();

        $preparingCount = Order::where('status', 'preparing')->count();
        $readyCount = Order::where('status', 'ready')->count();
        $deliveredCount = Order::where('status', 'delivered')->count();
        $completedCount = Order::where('status', 'completed')->count();
        $cancelledCount = Order::where('status', 'cancelled')->count();

        $deliveryCount = Order::where('delivery_method', 'delivery')
            ->where('status', '!=', 'cancelled')
            ->count();

        $pickupCount = Order::where('delivery_method', 'pickup')
            ->where('status', '!=', 'cancelled')
            ->count();

        $freeDeliveryCount = Order::where('is_free_delivery', 1)
            ->where('delivery_method', 'delivery')
            ->where('status', '!=', 'cancelled')
            ->count();

        return view('admin.dashboard', compact(
            'todayRevenue', 'todayOrders', 'totalCust', 'newCustToday', 'openTickets',
            'pendingReturns', 'preparingCount', 'readyCount', 'deliveredCount', 'completedCount',
            'cancelledCount', 'deliveryCount', 'pickupCount', 'freeDeliveryCount'
        ));
    }
}
