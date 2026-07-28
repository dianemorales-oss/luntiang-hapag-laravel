<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Feedback;
use App\Models\CustomerNotification;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $emailParam = trim($request->get('email', ''));
        $search = trim($request->get('q', ''));
        
        $customer = null;
        $tickets = collect();
        $orders = collect();
        $returns = collect();
        $feedbacks = collect();
        $activity = collect();
        $stats = [];
        $message = '';
        $customers = collect();

        if ($emailParam !== '') {
            $customer = User::where('email', $emailParam)->first();
            if ($customer) {
                $uid = $customer->id;
                $tickets = Ticket::where('user_id', $uid)->orderByDesc('created_at')->get();
                $orders = Order::where('user_id', $uid)->with('items')->orderByDesc('created_at')->get();
                $returns = ReturnRequest::where('user_id', $uid)->orderByDesc('created_at')->get();
                $feedbacks = Feedback::where('user_id', $uid)->orderByDesc('created_at')->get();
                $activity = CustomerNotification::where('user_id', $uid)->orderByDesc('created_at')->limit(50)->get();
                $stats = [
                    'total_orders' => $orders->count(),
                    'completed_orders' => $orders->where('status', 'completed')->count(),
                    'pending_orders' => $orders->whereNotIn('status', ['completed', 'cancelled'])->count(),
                    'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
                    'total_spent' => $orders->where('status', 'completed')->sum('total'),
                ];
            }

            // Handle customer edit via POST on same page
            if ($request->isMethod('post') && $request->has('save_customer')) {
                $request->validate([
                    'first_name' => 'required',
                    'last_name' => 'required',
                    'email' => 'required|email|unique:users,email,'.$customer->id
                ]);

                if ($customer) {
                    $customer->first_name = trim($request->input('first_name'));
                    $customer->last_name = trim($request->input('last_name'));
                    $customer->email = trim($request->input('email'));
                    $customer->phone = trim($request->input('phone'));
                    $customer->address = trim($request->input('address'));
                    $customer->save();

                    return redirect()->route('admin.customers.index', ['email' => $customer->email])->with('success', 'Customer updated.');
                }
            }
        } else {
            $query = User::query();
            if ($search !== '') {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
                });
            }
            $customers = $query->orderByDesc('created_at')->get()->map(function($c) {
                $c->ticket_count = Ticket::where('user_id', $c->id)->count();
                $c->order_count = Order::where('user_id', $c->id)->count();
                $c->return_count = ReturnRequest::where('user_id', $c->id)->count();
                return $c;
            });
        }

        return view('admin.customers.index', compact(
            'customer', 'tickets', 'orders', 'returns', 'feedbacks', 'activity', 'stats', 'emailParam', 'search', 'customers'
        ));
    }
}
