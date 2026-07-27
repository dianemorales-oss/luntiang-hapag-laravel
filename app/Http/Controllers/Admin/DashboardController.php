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

        $activeCount = Order::where('status', 'active')->count();
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

        // Customer Feedback Overview
        try {
            $feedbackModel = \App\Models\Feedback::query();
            $avgRating = $feedbackModel->avg('rating') ?: 0;
            $totalFeedback = \App\Models\Feedback::count();
            $ratingDistribution = [];
            for ($i=5; $i>=1; $i--) {
                $ratingDistribution[$i] = \App\Models\Feedback::where('rating', $i)->count();
            }
            $latestFeedback = \App\Models\Feedback::with('user')->orderByDesc('created_at')->limit(5)->get();
        } catch (\Exception $e) {
            $avgRating = 0;
            $totalFeedback = 0;
            $ratingDistribution = [5=>0,4=>0,3=>0,2=>0,1=>0];
            $latestFeedback = collect();
        }

        return view('admin.dashboard', compact(
            'todayRevenue', 'todayOrders', 'totalCust', 'newCustToday', 'openTickets',
            'pendingReturns', 'activeCount', 'preparingCount', 'readyCount', 'deliveredCount', 'completedCount',
            'cancelledCount', 'deliveryCount', 'pickupCount', 'freeDeliveryCount',
            'avgRating','totalFeedback','ratingDistribution','latestFeedback'
        ));
    }
}
