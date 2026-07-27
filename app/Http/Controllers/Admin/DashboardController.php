<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Ticket;
use App\Models\WarrantyRequest;
use App\Models\ReturnRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'orders' => Order::count(),
            'customers' => User::count(),
            'tickets' => Ticket::where('status','open')->count(),
            'products' => Product::count(),
            'warranty_pending' => WarrantyRequest::where('status','pending')->count(),
            'returns_pending' => ReturnRequest::where('status','pending')->count(),
        ];
        $recentOrders = Order::orderByDesc('created_at')->limit(5)->get();
        $recentTickets = Ticket::orderByDesc('created_at')->limit(5)->get();

        return view('admin.dashboard', compact('stats','recentOrders','recentTickets'));
    }
}
